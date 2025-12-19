<?php
require_once 'config.php';

class AgenteValidacionProceso {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function validar($id_aplicacion) {
        // Obtener datos de la aplicación
        $sql = "SELECT 
                    a.id_aplicacion, a.status_aplicacion,
                    c.cv_path, c.habilidades_tecnicas,
                    e.score_global,
                    COUNT(DISTINCT ent.id_entrevista) AS tiene_entrevista
                FROM aplicaciones a
                JOIN candidatos c ON a.id_candidato = c.id
                LEFT JOIN evaluaciones e ON a.id_aplicacion = e.id_aplicacion
                LEFT JOIN entrevistas ent ON a.id_aplicacion = ent.id_aplicacion
                WHERE a.id_aplicacion = ?
                GROUP BY a.id_aplicacion";
        
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("i", $id_aplicacion);
        $stmt->execute();
        $result = $stmt->get_result();
        $datos = $result->fetch_assoc();
        
        if (!$datos) {
            return ['success' => false, 'mensaje' => 'Aplicación no encontrada'];
        }
        
        $validaciones = [];
        
        // Validar CV
        if (empty($datos['cv_path']) || !file_exists($datos['cv_path'])) {
            $validaciones[] = [
                'tipo' => 'entregable',
                'estado' => 'rechazado',
                'descripcion' => 'CV no encontrado',
                'fecha_limite' => date('Y-m-d', strtotime('+3 days'))
            ];
        }
        
        // Validar evaluación
        if (!$datos['score_global'] || $datos['score_global'] < 30) {
            $validaciones[] = [
                'tipo' => 'proceso',
                'estado' => 'rechazado',
                'descripcion' => 'Evaluación incompleta o score muy bajo',
                'fecha_limite' => date('Y-m-d', strtotime('+1 day'))
            ];
        }
        
        // Si todo está bien
        if (empty($validaciones)) {
            $validaciones[] = [
                'tipo' => 'proceso',
                'estado' => 'aprobado',
                'descripcion' => 'Proceso validado correctamente',
                'fecha_limite' => null
            ];
        }
        
        // Guardar en BD
        foreach ($validaciones as $validacion) {
            $sql_insert = "INSERT INTO comentarios_validacion 
                          (id_aplicacion, autor, mensaje, tipo_validacion, estado_validacion, fecha_limite) 
                          VALUES (?, 'Sistema (Agente)', ?, ?, ?, ?)";
            $stmt_insert = $this->mysqli->prepare($sql_insert);
            $fecha_limite = $validacion['fecha_limite'] ?? null;
            $stmt_insert->bind_param("issss", $id_aplicacion, $validacion['descripcion'], 
                $validacion['tipo'], $validacion['estado'], $fecha_limite);
            $stmt_insert->execute();
        }
        
        return ['success' => true, 'validaciones' => $validaciones];
    }
}
?>
