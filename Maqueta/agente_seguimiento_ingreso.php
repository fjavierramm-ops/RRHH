<?php
require_once 'config.php';

class AgenteSeguimientoIngreso {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function iniciarOnboarding($id_aplicacion, $fecha_ingreso = null) {
        if (!$fecha_ingreso) {
            $fecha_ingreso = date('Y-m-d', strtotime('+7 days'));
        }
        
        $tareas_pendientes = [
            'doc_contratacion' => ['nombre' => 'Documentación', 'estado' => 'Pendiente'],
            'config_equipos' => ['nombre' => 'Config. Equipos', 'estado' => 'Pendiente'],
            'induccion' => ['nombre' => 'Inducción', 'estado' => 'Pendiente'],
            'entrenamiento' => ['nombre' => 'Entrenamiento', 'estado' => 'Pendiente']
        ];
        
        $sql = "INSERT INTO onboarding 
                (id_aplicacion, fecha_ingreso, doc_contratacion, config_equipos, 
                 induccion, entrenamiento, tareas_pendientes, fecha_limite_ingreso) 
                VALUES (?, ?, 'Pendiente', 'Pendiente', 'Pendiente', 'Pendiente', ?, ?)
                ON DUPLICATE KEY UPDATE
                fecha_ingreso = VALUES(fecha_ingreso),
                tareas_pendientes = VALUES(tareas_pendientes),
                fecha_limite_ingreso = VALUES(fecha_limite_ingreso)";
        
        $stmt = $this->mysqli->prepare($sql);
        $tareas_json = json_encode($tareas_pendientes);
        $fecha_limite = date('Y-m-d', strtotime($fecha_ingreso . ' -1 day'));
        $stmt->bind_param("isss", $id_aplicacion, $fecha_ingreso, $tareas_json, $fecha_limite);
        
        if ($stmt->execute()) {
            return ['success' => true, 'fecha_ingreso' => $fecha_ingreso];
        }
        
        return ['success' => false, 'mensaje' => 'Error al iniciar onboarding'];
    }
    
    public function actualizarTarea($id_aplicacion, $tarea_nombre, $nuevo_estado) {
        $sql = "SELECT tareas_pendientes FROM onboarding WHERE id_aplicacion = ?";
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("i", $id_aplicacion);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if (!$row) return ['success' => false];
        
        $tareas = json_decode($row['tareas_pendientes'], true);
        if (isset($tareas[$tarea_nombre])) {
            $tareas[$tarea_nombre]['estado'] = $nuevo_estado;
            
            $sql_update = "UPDATE onboarding SET 
                         tareas_pendientes = ?, $tarea_nombre = ?
                         WHERE id_aplicacion = ?";
            $stmt_update = $this->mysqli->prepare($sql_update);
            $tareas_json = json_encode($tareas);
            $stmt_update->bind_param("ssi", $tareas_json, $nuevo_estado, $id_aplicacion);
            $stmt_update->execute();
            
            return ['success' => true];
        }
        
        return ['success' => false];
    }
}
?>
