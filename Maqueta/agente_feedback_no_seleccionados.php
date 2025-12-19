<?php
// agente_feedback_no_seleccionados.php
require_once 'config.php';

class AgenteFeedbackNoSeleccionados {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    /**
     * Procesa candidatos rechazados y genera feedback
     */
    public function procesarRechazados() {
        // Buscar aplicaciones rechazadas sin feedback (case insensitive para status)
        $sql = "SELECT 
                    a.id_aplicacion,
                    a.id_candidato,
                    c.nombre,
                    c.email,
                    v.titulo AS vacante,
                    e.score_global,
                    e.score_tecnico,
                    e.score_blando
                FROM aplicaciones a
                JOIN candidatos c ON a.id_candidato = c.id
                JOIN vacantes v ON a.id_vacante = v.id_vacante
                LEFT JOIN evaluaciones e ON a.id_aplicacion = e.id_aplicacion
                LEFT JOIN feedback_rechazo f ON a.id_aplicacion = f.id_aplicacion
                WHERE LOWER(TRIM(a.status_aplicacion)) = 'rechazado'
                  AND f.id_feedback IS NULL
                LIMIT 10"; // Procesar en lotes
        
        $result = $this->mysqli->query($sql);
        
        if (!$result) {
            error_log("Error en procesarRechazados: " . $this->mysqli->error);
            return ['success' => false, 'procesados' => 0, 'error' => $this->mysqli->error];
        }
        
        $procesados = 0;
        while ($row = $result->fetch_assoc()) {
            try {
                $this->generarYEnviarFeedback($row);
                $procesados++;
            } catch (Exception $e) {
                error_log("Error generando feedback para aplicación {$row['id_aplicacion']}: " . $e->getMessage());
            }
        }
        
        return ['success' => true, 'procesados' => $procesados];
    }
    
    /**
     * Genera y envía feedback personalizado
     */
    private function generarYEnviarFeedback($datos) {
        $mensaje = $this->generarMensaje($datos);
        $sugerencias = $this->generarSugerencias($datos);
        $razones = $this->identificarRazones($datos);
        
        // Verificar si ya existe feedback para esta aplicación
        $sql_check = "SELECT id_feedback FROM feedback_rechazo WHERE id_aplicacion = ?";
        $stmt_check = $this->mysqli->prepare($sql_check);
        $stmt_check->bind_param("i", $datos['id_aplicacion']);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $existe_feedback = $result_check->num_rows > 0;
        $id_feedback = null;
        if ($existe_feedback) {
            $row = $result_check->fetch_assoc();
            $id_feedback = $row['id_feedback'];
        }
        $stmt_check->close();
        
        // Guardar en base de datos solo si no existe
        if (!$existe_feedback) {
            $sql = "INSERT INTO feedback_rechazo 
                    (id_aplicacion, mensaje_generado, razones_rechazo, sugerencias_mejora, estado_envio)
                    VALUES (?, ?, ?, ?, 'pendiente')";
            
            $stmt = $this->mysqli->prepare($sql);
            $razones_json = json_encode($razones);
            $stmt->bind_param("isss", 
                $datos['id_aplicacion'],
                $mensaje,
                $razones_json,
                $sugerencias
            );
            $stmt->execute();
            $id_feedback = $stmt->insert_id;
            $stmt->close();
        }
        
        // Guardar en canal_comunicacion (siempre, para asegurar que esté registrado)
        require_once 'config.php';
        $conn = connection();
        $this->guardarLogComunicacion($conn, $datos['id_candidato'], $mensaje, 'Email', 'Enviado');
        
        // Enviar email
        $enviado = $this->enviarEmail($datos['email'], $mensaje);
        
        // Actualizar estado
        if ($enviado && $id_feedback) {
            $sql_update = "UPDATE feedback_rechazo 
                          SET estado_envio = 'enviado', fecha_envio = NOW() 
                          WHERE id_feedback = ?";
            $stmt_update = $this->mysqli->prepare($sql_update);
            $stmt_update->bind_param("i", $id_feedback);
            $stmt_update->execute();
            $stmt_update->close();
        }
    }
    
    /**
     * Genera mensaje personalizado
     */
    private function generarMensaje($datos) {
        $mensaje = "Hola " . $datos['nombre'] . ",\n\n";
        $mensaje .= "Gracias por tu interés en la posición de " . $datos['vacante'] . ".\n\n";
        $mensaje .= "Después de revisar cuidadosamente tu perfil y aplicación, lamentamos informarte que en esta ocasión no podremos avanzar con tu candidatura.\n\n";
        
        if ($datos['score_global']) {
            $mensaje .= "Tu evaluación general fue de " . $datos['score_global'] . "%.\n";
        }
        
        $mensaje .= "\nAgradecemos el tiempo que invertiste en aplicar y te deseamos éxito en tu búsqueda profesional.\n\n";
        $mensaje .= "Saludos cordiales,\nEquipo de RRHH";
        
        return $mensaje;
    }
    
    /**
     * Genera sugerencias de mejora
     */
    private function generarSugerencias($datos) {
        $sugerencias = [];
        
        if ($datos['score_tecnico'] && $datos['score_tecnico'] < 50) {
            $sugerencias[] = "Considera fortalecer tus habilidades técnicas relacionadas con el puesto";
        }
        
        if ($datos['score_blando'] && $datos['score_blando'] < 50) {
            $sugerencias[] = "Desarrolla más tus habilidades blandas y de comunicación";
        }
        
        if (empty($sugerencias)) {
            $sugerencias[] = "Continúa desarrollando tu experiencia y habilidades";
        }
        
        return implode(". ", $sugerencias);
    }
    
    /**
     * Identifica razones de rechazo
     */
    private function identificarRazones($datos) {
        $razones = [];
        
        if ($datos['score_global'] && $datos['score_global'] < 30) {
            $razones[] = "Score de evaluación muy bajo";
        }
        
        if ($datos['score_tecnico'] && $datos['score_tecnico'] < 40) {
            $razones[] = "Habilidades técnicas insuficientes";
        }
        
        if (empty($razones)) {
            $razones[] = "No cumple con los requisitos mínimos del puesto";
        }
        
        return $razones;
    }
    
    /**
     * Envía email (simulación - implementar con PHPMailer o similar)
     */
    private function enviarEmail($email, $mensaje) {
        // TODO: Implementar envío real de email
        // Por ahora solo simulación
        error_log("Email enviado a: $email");
        return true;
    }
    
    /**
     * Guarda log de comunicación en canal_comunicacion
     */
    private function guardarLogComunicacion($conn, $idCliente, $mensaje, $canal, $estado = 'Enviado') {
        // Verificar si ya existe un log similar para evitar duplicados
        $sql_check = "SELECT id_comunicacion FROM canal_comunicacion 
                     WHERE idClientes = ? AND canal = ? AND mensaje LIKE ? AND fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $stmt_check = $conn->prepare($sql_check);
        $mensaje_like = '%' . substr($mensaje, 0, 50) . '%'; // Primeros 50 caracteres para comparar
        $stmt_check->bind_param("iss", $idCliente, $canal, $mensaje_like);
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
