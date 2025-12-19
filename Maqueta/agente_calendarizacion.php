<?php
require_once 'config.php';

class AgenteCalendarizacion {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    /**
     * Procesa la calendarización de una entrevista
     */
    public function procesar($datos) {
        $accion = $datos['accion'] ?? 'crear';
        
        switch($accion) {
            case 'crear':
                return $this->crearEntrevista($datos);
            case 'notificar_reprogramacion':
                return $this->notificarReprogramacion($datos['id_entrevista']);
            default:
                return ['success' => false, 'mensaje' => 'Acción no reconocida'];
        }
    }
    
    /**
     * Crea una entrevista con 3 opciones de fecha/hora
     */
    private function crearEntrevista($datos) {
        $id_aplicacion = $datos['id_aplicacion'];
        $fecha_base = $datos['fecha_base'] ?? date('Y-m-d', strtotime('+3 days'));
        
        // Generar 3 opciones de fecha/hora (días diferentes, horarios diferentes)
        $fecha1 = date('Y-m-d', strtotime($fecha_base));
        $hora1 = '10:00:00';
        
        $fecha2 = date('Y-m-d', strtotime($fecha_base . ' +1 day'));
        $hora2 = '14:00:00';
        
        $fecha3 = date('Y-m-d', strtotime($fecha_base . ' +2 days'));
        $hora3 = '16:00:00';
        
        // Insertar en tabla entrevistas
        $sql = "INSERT INTO entrevistas 
                (id_aplicacion, fecha_propuesta_1, hora_propuesta_1, 
                 fecha_propuesta_2, hora_propuesta_2, 
                 fecha_propuesta_3, hora_propuesta_3, 
                 status_confirmacion) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Pendiente de confirmación')
                ON DUPLICATE KEY UPDATE
                fecha_propuesta_1 = VALUES(fecha_propuesta_1),
                hora_propuesta_1 = VALUES(hora_propuesta_1),
                fecha_propuesta_2 = VALUES(fecha_propuesta_2),
                hora_propuesta_2 = VALUES(hora_propuesta_2),
                fecha_propuesta_3 = VALUES(fecha_propuesta_3),
                hora_propuesta_3 = VALUES(hora_propuesta_3),
                status_confirmacion = 'Pendiente de confirmación'";
        
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("issssss", $id_aplicacion, $fecha1, $hora1, $fecha2, $hora2, $fecha3, $hora3);
        
        if ($stmt->execute()) {
            $id_entrevista = $stmt->insert_id ?: $this->obtenerIdEntrevista($id_aplicacion);
            
            // Enviar notificación al candidato
            $this->enviarNotificacionConfirmacion($id_entrevista);
            
            return [
                'success' => true,
                'id_entrevista' => $id_entrevista,
                'opciones' => [
                    ['fecha' => $fecha1, 'hora' => $hora1],
                    ['fecha' => $fecha2, 'hora' => $hora2],
                    ['fecha' => $fecha3, 'hora' => $hora3]
                ]
            ];
        } else {
            return ['success' => false, 'mensaje' => 'Error al crear entrevista: ' . $stmt->error];
        }
    }
    
    /**
     * Obtiene el ID de entrevista si ya existe
     */
    private function obtenerIdEntrevista($id_aplicacion) {
        $sql = "SELECT id_entrevista FROM entrevistas WHERE id_aplicacion = ?";
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("i", $id_aplicacion);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['id_entrevista'] ?? null;
    }
    
    /**
     * Envía notificación de confirmación al candidato
     */
    private function enviarNotificacionConfirmacion($id_entrevista) {
        // Obtener datos del candidato y entrevista
        $sql = "SELECT 
                    e.id_entrevista,
                    e.fecha_propuesta_1, e.hora_propuesta_1,
                    e.fecha_propuesta_2, e.hora_propuesta_2,
                    e.fecha_propuesta_3, e.hora_propuesta_3,
                    c.email,
                    c.nombre,
                    v.titulo AS vacante
                FROM entrevistas e
                JOIN aplicaciones a ON e.id_aplicacion = a.id_aplicacion
                JOIN candidatos c ON a.id_candidato = c.id
                JOIN vacantes v ON a.id_vacante = v.id_vacante
                WHERE e.id_entrevista = ?";
        
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("i", $id_entrevista);
        $stmt->execute();
        $result = $stmt->get_result();
        $datos = $result->fetch_assoc();
        
        if (!$datos) {
            return false;
        }
        
        // Generar mensaje
        $mensaje = "Hola " . $datos['nombre'] . ",\n\n";
        $mensaje .= "Te hemos programado una entrevista para la vacante: " . $datos['vacante'] . "\n\n";
        $mensaje .= "Opciones disponibles:\n";
        $mensaje .= "1. " . $datos['fecha_propuesta_1'] . " a las " . substr($datos['hora_propuesta_1'], 0, 5) . "\n";
        $mensaje .= "2. " . $datos['fecha_propuesta_2'] . " a las " . substr($datos['hora_propuesta_2'], 0, 5) . "\n";
        $mensaje .= "3. " . $datos['fecha_propuesta_3'] . " a las " . substr($datos['hora_propuesta_3'], 0, 5) . "\n\n";
        $mensaje .= "Por favor, confirma tu disponibilidad ingresando a tu portal de candidatos.\n\n";
        $mensaje .= "Saludos,\nEquipo de RRHH";
        
        // Guardar notificación en base de datos
        $sql_notif = "INSERT INTO notificaciones_entrevista 
                     (id_entrevista, tipo, canal, destinatario, mensaje, estado) 
                     VALUES (?, 'confirmacion', 'email', ?, ?, 'pendiente')";
        $stmt_notif = $this->mysqli->prepare($sql_notif);
        $stmt_notif->bind_param("iss", $id_entrevista, $datos['email'], $mensaje);
        $stmt_notif->execute();
        
        // Aquí podrías enviar el email real usando mail() o PHPMailer
        // mail($datos['email'], 'Entrevista Programada', $mensaje);
        
        return true;
    }
    
    /**
     * Notifica sobre una reprogramación
     */
    private function notificarReprogramacion($id_entrevista) {
        // Similar a enviarNotificacionConfirmacion pero con mensaje de reprogramación
        $sql = "SELECT 
                    e.fecha_final, e.hora_final,
                    c.email, c.nombre,
                    v.titulo AS vacante
                FROM entrevistas e
                JOIN aplicaciones a ON e.id_aplicacion = a.id_aplicacion
                JOIN candidatos c ON a.id_candidato = c.id
                JOIN vacantes v ON a.id_vacante = v.id_vacante
                WHERE e.id_entrevista = ?";
        
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("i", $id_entrevista);
        $stmt->execute();
        $result = $stmt->get_result();
        $datos = $result->fetch_assoc();
        
        if ($datos && $datos['fecha_final']) {
            $mensaje = "Hola " . $datos['nombre'] . ",\n\n";
            $mensaje .= "Tu entrevista para " . $datos['vacante'] . " ha sido reprogramada.\n\n";
            $mensaje .= "Nueva fecha: " . $datos['fecha_final'] . " a las " . substr($datos['hora_final'], 0, 5) . "\n\n";
            $mensaje .= "Saludos,\nEquipo de RRHH";
            
            $sql_notif = "INSERT INTO notificaciones_entrevista 
                         (id_entrevista, tipo, canal, destinatario, mensaje, estado) 
                         VALUES (?, 'reprogramacion', 'email', ?, ?, 'pendiente')";
            $stmt_notif = $this->mysqli->prepare($sql_notif);
            $stmt_notif->bind_param("iss", $id_entrevista, $datos['email'], $mensaje);
            $stmt_notif->execute();
            
            return ['success' => true, 'mensaje' => 'Notificación de reprogramación registrada'];
        }
        
        return ['success' => false, 'mensaje' => 'No se encontró información de la entrevista'];
    }
}
?>
