<?php
require_once 'config.php';

class AgenteDeteccionRiesgos {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    /**
     * Analiza una aplicación en busca de riesgos
     */
    public function analizar($id_aplicacion) {
        // Obtener datos de la aplicación
        $sql = "SELECT 
                    a.id_aplicacion,
                    c.nombre,
                    c.email,
                    c.telefono,
                    c.cv_path,
                    c.habilidades_tecnicas,
                    e.score_tecnico,
                    e.score_blando,
                    e.score_global
                FROM aplicaciones a
                JOIN candidatos c ON a.id_candidato = c.id
                LEFT JOIN evaluaciones e ON a.id_aplicacion = e.id_aplicacion
                WHERE a.id_aplicacion = ?";
        
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("i", $id_aplicacion);
        $stmt->execute();
        $result = $stmt->get_result();
        $datos = $result->fetch_assoc();
        
        if (!$datos) {
            return ['success' => false, 'mensaje' => 'Aplicación no encontrada'];
        }
        
        $riesgos = [];
        $score_riesgo_total = 0;
        
        // 1. Detectar score muy bajo (si existe evaluación)
        // También detectar si no hay evaluación (riesgo de proceso incompleto)
        $score_global = $datos['score_global'] ?? null;
        if ($score_global === null) {
            // No hay evaluación aún, pero esto no es un riesgo por sí solo
            // Solo se marca como riesgo si además hay otros problemas
        } elseif ($score_global < 30) {
            $riesgos[] = [
                'tipo' => 'informacion_sospechosa',
                'severidad' => 'alta',
                'descripcion' => 'Score de evaluación extremadamente bajo (' . $score_global . '%)',
                'evidencia' => 'Score global: ' . $score_global . '%'
            ];
            $score_riesgo_total += 40;
        }
        
        // 2. Detectar habilidades técnicas vacías, muy pocas o sospechosas
        $habilidades = trim($datos['habilidades_tecnicas'] ?? '');
        $habilidades_lower = strtolower($habilidades);
        $palabras_sospechosas = ['no se', 'no me importa', 'nada', 'ninguna', 'sin habilidades', 'no tengo'];
        $es_sospechoso = false;
        foreach ($palabras_sospechosas as $palabra) {
            if (strpos($habilidades_lower, $palabra) !== false) {
                $es_sospechoso = true;
                break;
            }
        }
        
        if (empty($habilidades) || strlen($habilidades) < 3 || $es_sospechoso) {
            $riesgos[] = [
                'tipo' => 'inconsistencia',
                'severidad' => $es_sospechoso ? 'alta' : 'media',
                'descripcion' => $es_sospechoso 
                    ? 'Habilidades técnicas sospechosas o inadecuadas' 
                    : 'Perfil con habilidades técnicas insuficientes o vacías',
                'evidencia' => 'Habilidades técnicas: ' . ($habilidades ?: 'Vacío')
            ];
            $score_riesgo_total += $es_sospechoso ? 35 : 25;
        }
        
        // 3. Detectar información de contacto incompleta
        if (empty($datos['telefono']) || empty($datos['email'])) {
            $riesgos[] = [
                'tipo' => 'inconsistencia',
                'severidad' => 'baja',
                'descripcion' => 'Información de contacto incompleta',
                'evidencia' => 'Email: ' . ($datos['email'] ?: 'Vacío') . ', Teléfono: ' . ($datos['telefono'] ?: 'Vacío')
            ];
            $score_riesgo_total += 15;
        }
        
        // 4. Detectar CV no subido
        $cv_path = $datos['cv_path'] ?? '';
        $cv_existe = false;
        if (!empty($cv_path)) {
            // Verificar si la ruta es absoluta o relativa
            $ruta_completa = (strpos($cv_path, '/') === 0 || strpos($cv_path, 'C:') === 0) 
                ? $cv_path 
                : __DIR__ . '/' . $cv_path;
            $cv_existe = file_exists($ruta_completa);
        }
        
        if (empty($cv_path) || !$cv_existe) {
            $riesgos[] = [
                'tipo' => 'inconsistencia',
                'severidad' => 'media',
                'descripcion' => 'CV no encontrado o no subido',
                'evidencia' => 'Ruta CV: ' . ($cv_path ?: 'No especificada')
            ];
            $score_riesgo_total += 20;
        }
        
        // Guardar riesgos en base de datos (solo si hay riesgos)
        if (count($riesgos) > 0) {
            // Limpiar riesgos previos no revisados para esta aplicación
            $sql_delete = "DELETE FROM riesgos_detectados WHERE id_aplicacion = ? AND revisado = 0";
            $stmt_delete = $this->mysqli->prepare($sql_delete);
            $stmt_delete->bind_param("i", $id_aplicacion);
            $stmt_delete->execute();
            $stmt_delete->close();
            
            // Insertar nuevos riesgos
            foreach ($riesgos as $riesgo) {
                $sql_insert = "INSERT INTO riesgos_detectados 
                              (id_aplicacion, tipo_riesgo, severidad, descripcion, evidencia, score_riesgo) 
                              VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_insert = $this->mysqli->prepare($sql_insert);
                $stmt_insert->bind_param("issssi", 
                    $id_aplicacion, 
                    $riesgo['tipo'], 
                    $riesgo['severidad'], 
                    $riesgo['descripcion'], 
                    $riesgo['evidencia'],
                    $score_riesgo_total
                );
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }
        
        return [
            'success' => true,
            'riesgos_encontrados' => count($riesgos),
            'score_riesgo' => min($score_riesgo_total, 100),
            'riesgos' => $riesgos
        ];
    }
}
?>
