<?php
require_once 'config.php';

class AgenteFeedbackRechazo {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    /**
     * Genera y envía feedback de rechazo
     */
    public function generarYEnviar($datos) {
        $id_aplicacion = $datos['id_aplicacion'];
        $razones = $datos['razones'] ?? ['No cumple con los requisitos mínimos'];
        
        // Obtener información del candidato y evaluación
        $sql = "SELECT 
                    a.id_aplicacion,
                    c.nombre,
                    c.email,
                    v.titulo AS vacante,
                    e.score_tecnico,
                    e.score_blando,
                    e.score_global,
                    e.comentarios_tecnicos
                FROM aplicaciones a
                JOIN candidatos c ON a.id_candidato = c.id
                JOIN vacantes v ON a.id_vacante = v.id_vacante
                LEFT JOIN evaluaciones e ON a.id_aplicacion = e.id_aplicacion
                WHERE a.id_aplicacion = ?";
        
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("i", $id_aplicacion);
        $stmt->execute();
        $result = $stmt->get_result();
        $info = $result->fetch_assoc();
        
        if (!$info) {
            return ['success' => false, 'mensaje' => 'Aplicación no encontrada'];
        }
        
        // Generar mensaje personalizado
        $mensaje = $this->generarMensaje($info, $razones);
        
        // Generar sugerencias de mejora
        $sugerencias = $this->generarSugerencias($info);
        
        // Guardar en base de datos (usar INSERT IGNORE o verificar duplicados)
        $sql_insert = "INSERT INTO feedback_rechazo 
                      (id_aplicacion, mensaje_generado, razones_rechazo, sugerencias_mejora, estado_envio) 
                      VALUES (?, ?, ?, ?, 'pendiente')
                      ON DUPLICATE KEY UPDATE 
                      mensaje_generado = VALUES(mensaje_generado),
                      razones_rechazo = VALUES(razones_rechazo),
                      sugerencias_mejora = VALUES(sugerencias_mejora)";
        $stmt_insert = $this->mysqli->prepare($sql_insert);
        $razones_json = json_encode($razones);
        $stmt_insert->bind_param("isss", $id_aplicacion, $mensaje, $razones_json, $sugerencias);
        $stmt_insert->execute();
        $stmt_insert->close();
        
        // Obtener id_candidato para guardar en canal_comunicacion
        $sql_candidato = "SELECT id_candidato FROM aplicaciones WHERE id_aplicacion = ?";
        $stmt_candidato = $this->mysqli->prepare($sql_candidato);
        $stmt_candidato->bind_param("i", $id_aplicacion);
        $stmt_candidato->execute();
        $result_candidato = $stmt_candidato->get_result();
        $row_candidato = $result_candidato->fetch_assoc();
        $id_candidato = $row_candidato['id_candidato'] ?? null;
        $stmt_candidato->close();
        
        // Guardar en canal_comunicacion si no existe ya
        if ($id_candidato) {
            require_once 'config.php';
            $conn = connection();
            $this->guardarLogComunicacion($conn, $id_candidato, $mensaje, 'Email', 'Enviado');
        }
        
        // Aquí podrías enviar el email real
        // mail($info['email'], 'Resultado de tu aplicación', $mensaje);
        
        // Actualizar estado a enviado
        $sql_update = "UPDATE feedback_rechazo SET estado_envio = 'enviado', fecha_envio = NOW() WHERE id_aplicacion = ?";
        $stmt_update = $this->mysqli->prepare($sql_update);
        $stmt_update->bind_param("i", $id_aplicacion);
        $stmt_update->execute();
        $stmt_update->close();
        
        return [
            'success' => true,
            'mensaje' => 'Feedback generado y enviado',
            'email' => $info['email']
        ];
    }
    
    /**
     * Genera el mensaje personalizado
     */
    private function generarMensaje($info, $razones) {
        $mensaje = "Hola " . $info['nombre'] . ",\n\n";
        $mensaje .= "Gracias por tu interés en la posición de " . $info['vacante'] . ".\n\n";
        $mensaje .= "Después de revisar cuidadosamente tu perfil y aplicación, lamentamos informarte que en esta ocasión no podremos avanzar con tu candidatura.\n\n";
        
        $mensaje .= "Razones principales:\n";
        foreach ($razones as $razon) {
            $mensaje .= "- " . $razon . "\n";
        }
        
        if ($info['score_global']) {
            $mensaje .= "\nTu evaluación general fue de " . $info['score_global'] . "%.\n";
        }
        
        $mensaje .= "\nAgradecemos el tiempo que invertiste en aplicar y te deseamos éxito en tu búsqueda profesional.\n\n";
        $mensaje .= "Saludos cordiales,\nEquipo de RRHH";
        
        return $mensaje;
    }
    
    /**
     * Genera sugerencias de mejora
     */
    private function generarSugerencias($info) {
        $sugerencias = [];
        
        if ($info['score_tecnico'] && $info['score_tecnico'] < 50) {
            $sugerencias[] = "Considera fortalecer tus habilidades técnicas relacionadas con el puesto";
        }
        
        if ($info['score_blando'] && $info['score_blando'] < 50) {
            $sugerencias[] = "Desarrolla más tus habilidades blandas y de comunicación";
        }
        
        if (empty($sugerencias)) {
            $sugerencias[] = "Continúa desarrollando tu experiencia y habilidades";
        }
        
        return implode(". ", $sugerencias);
    }
    
    /**
     * Guarda log de comunicación en canal_comunicacion
     */
    private function guardarLogComunicacion($conn, $idCliente, $mensaje, $canal, $estado = 'Enviado') {
        // Verificar si ya existe un log similar para evitar duplicados
        $sql_check = "SELECT id_comunicacion FROM canal_comunicacion 
                     WHERE idClientes = ? AND mensaje = ? AND canal = ? AND fecha = CURDATE()";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("iss", $idCliente, $mensaje, $canal);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $existe = $result_check->num_rows > 0;
        $stmt_check->close();
        
        if (!$existe) {
            $q = $conn->prepare("
                INSERT INTO canal_comunicacion
                (idClientes, tipo_origen, id_origen, tipo_destino, id_destino, canal, fecha, hora, mensaje, estado, automatica)
                VALUES (?, 'Sistema', 1, 'Candidato', ?, ?, CURDATE(), CURTIME(), ?, ?, 1)
            ");
            
            $q->bind_param(
                "iisss",
                $idCliente,
                $idCliente,
                $canal,
                $mensaje,
                $estado
            );
            $q->execute();
            $q->close();
        }
    }
}
?>
