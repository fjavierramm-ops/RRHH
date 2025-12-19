<?php
// Incluir la configuración de la base de datos y la sesión
require_once 'config.php';

// Establecer cabeceras para una respuesta JSON y permitir peticiones AJAX
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// =================================================================
// 1. VERIFICAR SESIÓN Y OBTENER IDs
// =================================================================
$usuario_logueado = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$id_candidato = $_SESSION['id'] ?? null;
$id_vacante = $_POST['id_vacante'] ?? null; // Esperamos el ID de la vacante por POST

if (!$usuario_logueado || !$id_candidato) {
    $response['message'] = "Error de autenticación. Por favor, inicie sesión de nuevo.";
    echo json_encode($response);
    exit;
}

if (!$id_vacante || !is_numeric($id_vacante)) {
    $response['message'] = "Error: ID de vacante inválido.";
    echo json_encode($response);
    exit;
}

// =================================================================
// 2. VERIFICAR SI YA EXISTE UNA APLICACIÓN (Evitar duplicados)
// =================================================================
$sql_check = "SELECT COUNT(*) FROM aplicaciones WHERE id_candidato = ? AND id_vacante = ?";
$stmt_check = $mysqli->prepare($sql_check);
if ($stmt_check) {
    $stmt_check->bind_param("ii", $id_candidato, $id_vacante);
    $stmt_check->execute();
    $stmt_check->bind_result($count);
    $stmt_check->fetch();
    $stmt_check->close();

    if ($count > 0) {
        $response['message'] = "Ya te has postulado a esta vacante.";
        $response['success'] = true; // Se considera "exitoso" visualmente para no mostrar error rojo
        echo json_encode($response);
        exit;
    }
} else {
    $response['message'] = "Error al preparar la consulta de verificación: " . $mysqli->error;
    echo json_encode($response);
    exit;
}

// =================================================================
// 3. INSERTAR LA NUEVA APLICACIÓN
// =================================================================
// status_aplicacion se establece por defecto como 'En revisión'
$sql_insert = "
    INSERT INTO aplicaciones (id_candidato, id_vacante, fecha_aplicacion, status_aplicacion) 
    VALUES (?, ?, NOW(), 'En revisión')
";

$stmt_insert = $mysqli->prepare($sql_insert);

if ($stmt_insert) {
    $stmt_insert->bind_param("ii", $id_candidato, $id_vacante);

    if ($stmt_insert->execute()) {
        $response['success'] = true;
        $response['message'] = "¡Postulación enviada con éxito!";

        // =========================================================
        // 4. INTEGRACIÓN DEL AGENTE DE SEGMENTACIÓN (IA / LÓGICA)
        // =========================================================
        // Obtenemos el ID de la aplicación que acabamos de crear
        $id_app_internal = $stmt_insert->insert_id;
        
        // Ejecutar agentes automáticamente usando el orquestador
require_once 'agente_orquestador.php';
$orquestador = new AgenteOrquestador($mysqli);

// Ejecutar segmentación (Fit Score)
$resultado_segmentacion = $orquestador->ejecutarAgente('segmentacion', [
    'id_aplicacion' => $id_app_internal
]);

// Ejecutar detección de riesgos
$resultado_riesgos = $orquestador->ejecutarAgente('deteccion_riesgos', [
    'id_aplicacion' => $id_app_internal
]);

// Si hay riesgos altos, se pueden enviar alertas aquí (opcional)
if ($resultado_riesgos['success'] && $resultado_riesgos['score_riesgo'] > 70) {
    // Aquí podrías enviar un email a RRHH o registrar una alerta
    error_log("ALERTA: Aplicación #$id_app_internal tiene score de riesgo alto: " . $resultado_riesgos['score_riesgo']);
}

        // =========================================================

    } else {
        $response['message'] = "Error al insertar la aplicación: " . $stmt_insert->error;
    }
    $stmt_insert->close();
} else {
    $response['message'] = "Error al preparar la consulta de inserción: " . $mysqli->error;
}

// Cerrar conexión general
$mysqli->close();

// Enviar respuesta final al Javascript (detalle_vacante.php)
echo json_encode($response);
?>