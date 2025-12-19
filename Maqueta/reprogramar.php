<?php
// Incluir la configuración de la base de datos y la sesión
require_once 'config.php';

// 1. VERIFICAR SESIÓN
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.html"); 
    exit;
}

// 2. VERIFICAR MÉTODO POST y datos
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: entrevistas.php");
    exit;
}

$id_entrevista = $_POST['id_entrevista'] ?? null;
$fecha_seleccionada = $_POST['fecha'] ?? null;
$hora_seleccionada = $_POST['hora'] ?? null;

if (!$id_entrevista || !$fecha_seleccionada || !$hora_seleccionada) {
    // Si falta algún dato, redirigir con un error.
    header("Location: entrevistas.php?status=error_data");
    exit;
}

// 3. DETERMINAR EL NUEVO ESTADO
// Asumiremos que si el usuario selecciona una fecha, está solicitando una acción:
// 'Confirmada': Si eligió una de las 3 opciones propuestas (esto es solo una suposición lógica, 
//                la empresa podría tener que confirmar si la nueva fecha está ok).
// 'Reprogramación solicitada': Si eligió una fecha totalmente nueva.
// Por simplicidad, estableceremos un estado unificado de "Reprogramación solicitada" 
// para que la empresa lo revise, a menos que quieras distinguir si fue una de las 3 opciones.

$nuevo_status_aplicacion = 'Entrevista'; // Mantener el estado de la aplicación
$nuevo_status_confirmacion = 'Reprogramación solicitada'; // Estado para la tabla 'entrevistas'

// 4. ACTUALIZAR LA TABLA 'ENTREVISTAS'
// Actualizamos la fecha/hora final seleccionada por el candidato y el estado de confirmación.
$sql_update_entrevista = "UPDATE entrevistas SET 
                          fecha_final = ?, 
                          hora_final = ?, 
                          status_confirmacion = ? 
                          WHERE id_entrevista = ?";

$stmt_entrevista = $mysqli->prepare($sql_update_entrevista);

if ($stmt_entrevista) {
    $stmt_entrevista->bind_param("sssi", $fecha_seleccionada, $hora_seleccionada, $nuevo_status_confirmacion, $id_entrevista);
    
    if ($stmt_entrevista->execute()) {
        // Éxito: Ahora, actualizar el estado en la tabla 'aplicaciones' para que refleje 'Entrevista'
        
        // 5. OBTENER EL id_aplicacion para actualizar la tabla 'aplicaciones'
        $sql_get_app_id = "SELECT id_aplicacion FROM entrevistas WHERE id_entrevista = ?";
        $stmt_get_app_id = $mysqli->prepare($sql_get_app_id);
        $stmt_get_app_id->bind_param("i", $id_entrevista);
        $stmt_get_app_id->execute();
        $result_app_id = $stmt_get_app_id->get_result();
        $row_app_id = $result_app_id->fetch_assoc();
        $id_aplicacion = $row_app_id['id_aplicacion'] ?? null;
        $stmt_get_app_id->close();

        if ($id_aplicacion) {
             // 6. ACTUALIZAR LA TABLA 'APLICACIONES'
             $sql_update_aplicacion = "UPDATE aplicaciones SET status_aplicacion = ? WHERE id_aplicacion = ?";
             $stmt_aplicacion = $mysqli->prepare($sql_update_aplicacion);

             if ($stmt_aplicacion) {
                 $stmt_aplicacion->bind_param("si", $nuevo_status_aplicacion, $id_aplicacion);
                 $stmt_aplicacion->execute();
                 $stmt_aplicacion->close();
             }
        }

        // Redirigir con éxito
        header("Location: entrevistas.php?status=reprogramado");
        exit;
    } else {
        // Error en la base de datos (Entrevistas)
        header("Location: entrevistas.php?status=error_db_entrevistas");
        exit;
    }
    $stmt_entrevista->close();
} else {
    // Error al preparar la consulta
   // Notificar al candidato sobre la reprogramación usando el agente
require_once 'agente_orquestador.php';
$orquestador = new AgenteOrquestador($mysqli);
$orquestador->ejecutarAgente('calendarizacion', [
    'accion' => 'notificar_reprogramacion',
    'id_entrevista' => $id_entrevista
]);

}

$mysqli->close();
?>