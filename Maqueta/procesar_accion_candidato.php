<?php
require_once 'config.php';

// Verificar sesión de admin (agregar tu lógica de autenticación aquí)
session_start();

$id_aplicacion = $_POST['id_aplicacion'] ?? null;
$accion = $_POST['accion'] ?? null;

if (!$id_aplicacion || !$accion) {
    header("Location: admin_evaluacion.php?error=datos_faltantes");
    exit;
}

require_once 'agente_orquestador.php';
$orquestador = new AgenteOrquestador($mysqli);

switch($accion) {
    case 'programar_entrevista':
        // Ejecutar agente de calendarización
        $resultado = $orquestador->ejecutarAgente('calendarizacion', [
            'accion' => 'crear',
            'id_aplicacion' => $id_aplicacion,
            'fecha_base' => date('Y-m-d', strtotime('+3 days'))
        ]);
        
        if ($resultado['success']) {
            // Actualizar estado de la aplicación
            $sql = "UPDATE aplicaciones SET status_aplicacion = 'Entrevista' WHERE id_aplicacion = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $id_aplicacion);
            $stmt->execute();
            
            header("Location: admin_evaluacion.php?success=entrevista_programada");
        } else {
            header("Location: admin_evaluacion.php?error=no_se_pudo_programar");
        }
        break;
        case 'contratar':
            require_once 'agente_orquestador.php';
            $orquestador = new AgenteOrquestador($mysqli);
            $resultado = $orquestador->ejecutarAgente('seguimiento_ingreso', [
                'accion' => 'iniciar',
                'id_aplicacion' => $id_aplicacion,
                'fecha_ingreso' => $_POST['fecha_ingreso'] ?? null
            ]);
            
            if ($resultado['success']) {
                $sql = "UPDATE aplicaciones SET status_aplicacion = 'Contratado' WHERE id_aplicacion = ?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("i", $id_aplicacion);
                $stmt->execute();
                header("Location: admin_evaluacion.php?success=contratado");
            } else {
                header("Location: admin_evaluacion.php?error=no_contratado");
            }
            break;
        
        
    case 'rechazar':
        // PRIMERO actualizar el status a Rechazado (esto debe hacerse siempre)
        $sql = "UPDATE aplicaciones SET status_aplicacion = 'Rechazado' WHERE id_aplicacion = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $id_aplicacion);
        $stmt->execute();
        $stmt->close();
        
        // LUEGO ejecutar agente de feedback de rechazo (opcional, no bloquea el rechazo)
        $razones = $_POST['razones'] ?? ['Score bajo', 'No cumple requisitos'];
        
        try {
            $resultado = $orquestador->ejecutarAgente('feedback_rechazo', [
                'id_aplicacion' => $id_aplicacion,
                'razones' => $razones
            ]);
        } catch (Exception $e) {
            // Si el agente falla, igual se marca como rechazado
            error_log("Error en agente feedback_rechazo: " . $e->getMessage());
        }
        
        header("Location: admin_evaluacion.php?success=candidato_rechazado");
        break;
        
    default:
        header("Location: admin_evaluacion.php?error=accion_no_valida");
        break;
}

$mysqli->close();
?>
