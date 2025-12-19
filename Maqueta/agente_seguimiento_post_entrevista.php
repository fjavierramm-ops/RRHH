<?php
require_once 'config.php';

class AgenteSeguimientoPostEntrevista {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    /**
     * Envía comunicación automática después de entrevista
     */
    public function enviarComunicacionPostEntrevista($id_entrevista) {
        // Obtener datos de la entrevista
        $sql = "SELECT 
                    e.id_entrevista,
                    e.fecha,
                    e.hora_inicio,
                    c.nombre AS candidato,
                    c.email,
                    v.titulo AS vacante
                FROM entrevistas e
                JOIN candidatos c ON e.idClientes = c.id
                JOIN vacantes v ON e.idVacante = v.id_vacante
                WHERE e.id_entrevista = ?";
        
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("i", $id_entrevista);
        $stmt->execute();
        $datos = $stmt->get_result()->fetch_assoc();
        
        if (!$datos) {
            return ['success' => false, 'mensaje' => 'Entrevista no encontrada'];
        }
        
        // Generar mensaje
        $mensaje = "Hola " . $datos['candidato'] . ",\n\n";
        $mensaje .= "Gracias por participar en la entrevista para la vacante: " . $datos['vacante'] . "\n\n";
        $mensaje .= "Tu entrevista fue programada para el " . date('d/m/Y', strtotime($datos['fecha'])) . 
                   " a las " . substr($datos['hora_inicio'], 0, 5) . ".\n\n";
        $mensaje .= "Nos pondremos en contacto contigo pronto con los resultados.\n\n";
        $mensaje .= "Saludos,\nEquipo de RRHH";
        
        // Guardar en canal_comunicacion
        $sql_log = "INSERT INTO canal_comunicacion 
                   (idClientes, tipo_origen, id_origen, tipo_destino, id_destino, canal, fecha, hora, mensaje, estado, automatica)
                   VALUES (?, 'Sistema', 1, 'Candidato', ?, 'Email', CURDATE(), CURTIME(), ?, 'Enviado', 1)";
        
        $stmt_log = $this->mysqli->prepare($sql_log);
        $idCliente = $this->obtenerIdCliente($id_entrevista);
        $stmt_log->bind_param("iis", $idCliente, $idCliente, $mensaje);
        $stmt_log->execute();
        
        // TODO: Enviar email real
        // mail($datos['email'], 'Gracias por tu entrevista', $mensaje);
        
        return ['success' => true, 'mensaje' => 'Comunicación enviada'];
    }
    
    private function obtenerIdCliente($id_entrevista) {
        $sql = "SELECT idClientes FROM entrevistas WHERE id_entrevista = ?";
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("i", $id_entrevista);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['idClientes'] ?? null;
    }
}
?>
