<?php
require_once 'config.php';

class AgenteOrquestador {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    /**
     * Ejecuta un agente específico
     * @param string $nombre_agente Nombre del agente ('segmentacion', 'deteccion_riesgos', etc.)
     * @param array $datos Datos necesarios para el agente
     * @return array Resultado de la ejecución
     */
    public function ejecutarAgente($nombre_agente, $datos = []) {
        $id_log = $this->registrarInicio($nombre_agente, $datos);
        $resultado = ['success' => false, 'mensaje' => ''];
        
        try {
            switch($nombre_agente) {
                case 'segmentacion':
                    $resultado = $this->ejecutarSegmentacion($datos);
                    break;
                    
                case 'deteccion_riesgos':
                    $resultado = $this->ejecutarDeteccionRiesgos($datos);
                    break;
                    
                case 'calendarizacion':
                    $resultado = $this->ejecutarCalendarizacion($datos);
                    break;
                    
                case 'feedback_rechazo':
                    $resultado = $this->ejecutarFeedbackRechazo($datos);
                    break;
                case 'validacion_proceso':
                        require_once 'agente_validacion_proceso.php';
                        $agente = new AgenteValidacionProceso($this->mysqli);
                        return $agente->validar($datos['id_aplicacion']);
                        
                case 'seguimiento_ingreso':
                        require_once 'agente_seguimiento_ingreso.php';
                        $agente = new AgenteSeguimientoIngreso($this->mysqli);
                        $accion = $datos['accion'] ?? 'iniciar';
                        if ($accion === 'iniciar') {
                            return $agente->iniciarOnboarding($datos['id_aplicacion'], $datos['fecha_ingreso'] ?? null);
                        }
                        if ($accion === 'actualizar_tarea') {
                            return $agente->actualizarTarea($datos['id_aplicacion'], $datos['tarea_nombre'], $datos['nuevo_estado']);
                        }
                        return ['success' => false, 'mensaje' => 'Acción no reconocida'];
                 
                case 'feedback_no_seleccionados':
                    require_once 'agente_feedback_no_seleccionados.php';
                    $agente = new AgenteFeedbackNoSeleccionados($this->mysqli);
                    return $agente->procesarRechazados();
                    
                case 'seguimiento_post_entrevista':
                    require_once 'agente_seguimiento_post_entrevista.php';
                    $agente = new AgenteSeguimientoPostEntrevista($this->mysqli);
                    $id_entrevista = $datos['id_entrevista'] ?? null;
                    if (!$id_entrevista) {
                        return ['success' => false, 'mensaje' => 'ID de entrevista no proporcionado'];
                    }
                    return $agente->enviarComunicacionPostEntrevista($id_entrevista);
                    
                default:
                    throw new Exception("Agente '$nombre_agente' no reconocido");
            }
            
            $this->registrarFin($id_log, 'completado', $resultado);
            return $resultado;
            
        } catch (Exception $e) {
            $this->registrarFin($id_log, 'error', ['error' => $e->getMessage()], $e->getMessage());
            return ['success' => false, 'mensaje' => $e->getMessage()];
        }
    }
    
    /**
     * Registra el inicio de ejecución de un agente
     */
    private function registrarInicio($nombre_agente, $datos) {
        $id_aplicacion = $datos['id_aplicacion'] ?? null;
        $sql = "INSERT INTO log_agentes (id_aplicacion, agente_nombre, estado, datos_entrada) 
                VALUES (?, ?, 'procesando', ?)";
        $stmt = $this->mysqli->prepare($sql);
        $datos_json = json_encode($datos);
        $stmt->bind_param("iss", $id_aplicacion, $nombre_agente, $datos_json);
        $stmt->execute();
        return $stmt->insert_id;
    }
    
    /**
     * Registra el fin de ejecución de un agente
     */
    private function registrarFin($id_log, $estado, $resultado, $error = null) {
        $sql = "UPDATE log_agentes 
                SET estado = ?, datos_salida = ?, fecha_fin = NOW(), error_mensaje = ? 
                WHERE id_log = ?";
        $stmt = $this->mysqli->prepare($sql);
        $resultado_json = json_encode($resultado);
        $stmt->bind_param("sssi", $estado, $resultado_json, $error, $id_log);
        $stmt->execute();
    }
    
    /**
     * Ejecuta el agente de segmentación
     */
    private function ejecutarSegmentacion($datos) {
        $id_aplicacion = $datos['id_aplicacion'];
        
        if (!$id_aplicacion) {
            return ['success' => false, 'mensaje' => 'ID de aplicación no proporcionado'];
        }
        
        // Ejecutar directamente la lógica de procesar_fit.php
        // Obtener datos
        $sql = "SELECT 
                    a.id_aplicacion,
                    c.habilidades_tecnicas AS skills_candidato,
                    c.habilidades_blandas AS soft_candidato,
                    v.requisitos AS requisitos_vacante,
                    v.descripcion AS descripcion_vacante
                FROM aplicaciones a
                JOIN candidatos c ON a.id_candidato = c.id
                JOIN vacantes v ON a.id_vacante = v.id_vacante
                WHERE a.id_aplicacion = ?";
        
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("i", $id_aplicacion);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        
        if (!$data) {
            return ['success' => false, 'mensaje' => 'Aplicación no encontrada'];
        }
        
        // Calcular scores (misma lógica que procesar_fit.php)
        $requisitos_texto = strtolower($data['requisitos_vacante'] . ' ' . $data['descripcion_vacante']);
        $skills = explode(',', $data['skills_candidato'] ?? '');
        $softs = explode(',', $data['soft_candidato'] ?? '');
        
        $requisitos_array = explode(',', $data['requisitos_vacante'] ?? '');
        $requisitos_array = array_map('trim', array_map('strtolower', $requisitos_array));
        $requisitos_array = array_filter($requisitos_array);
        
        $coincidencias_tec = 0;
        $total_skills = count(array_filter($skills));
        $total_requisitos = count($requisitos_array);
        $palabras_encontradas = [];
        
        if ($total_skills > 0 && $total_requisitos > 0) {
            foreach ($skills as $skill) {
                $skill = trim(strtolower($skill));
                if (empty($skill)) continue;
                foreach ($requisitos_array as $req) {
                    if (strpos($req, $skill) !== false || strpos($skill, $req) !== false) {
                        $coincidencias_tec++;
                        $palabras_encontradas[] = $skill;
                        break;
                    }
                }
            }
            $score_tecnico = intval(($coincidencias_tec / max($total_skills, $total_requisitos)) * 100);
            if ($coincidencias_tec >= $total_requisitos * 0.7) {
                $score_tecnico = min(100, $score_tecnico + 10);
            }
        } else {
            $score_tecnico = 0;
        }
        
        $coincidencias_soft = 0;
        $total_softs = count(array_filter($softs));
        if ($total_softs > 0) {
            foreach ($softs as $soft) {
                $soft = trim(strtolower($soft));
                if (!empty($soft) && strpos($requisitos_texto, $soft) !== false) {
                    $coincidencias_soft++;
                }
            }
            $score_blando = 50 + ($coincidencias_soft * 10);
            if($score_blando > 100) $score_blando = 100;
        } else {
            $score_blando = 50;
        }
        
        $score_global = intval(($score_tecnico + $score_blando) / 2);
        
        $clasificacion = 'Bajo Fit';
        if ($score_global >= 75) {
            $clasificacion = 'Alto Fit';
        } elseif ($score_global >= 50) {
            $clasificacion = 'Medio Fit';
        }
        
        $segmento = 'C';
        if ($score_global >= 85) {
            $segmento = 'A';
        } elseif ($score_global >= 65) {
            $segmento = 'B';
        }
        
        $feedback = "Análisis Automático: Se detectaron coincidencias en: " . implode(", ", $palabras_encontradas) . ". ";
        if ($score_tecnico < 50) {
            $feedback .= "El perfil técnico parece bajo respecto a los requisitos descritos.";
        } else {
            $feedback .= "El candidato cuenta con las palabras clave principales.";
        }
        
        // Guardar en base de datos
        $sql_insert = "INSERT INTO evaluaciones (id_aplicacion, score_tecnico, score_blando, comentarios_tecnicos, clasificacion_fit, segmento) 
                       VALUES (?, ?, ?, ?, ?, ?)
                       ON DUPLICATE KEY UPDATE 
                       score_tecnico = VALUES(score_tecnico),
                       score_blando = VALUES(score_blando),
                       comentarios_tecnicos = VALUES(comentarios_tecnicos),
                       clasificacion_fit = VALUES(clasificacion_fit),
                       segmento = VALUES(segmento)";
        
        $stmt_ins = $this->mysqli->prepare($sql_insert);
        $stmt_ins->bind_param("iiisss", $id_aplicacion, $score_tecnico, $score_blando, $feedback, $clasificacion, $segmento);
        $stmt_ins->execute();
        $stmt_ins->close();
        
        return [
            'success' => true,
            'score_global' => $score_global,
            'score_tecnico' => $score_tecnico,
            'score_blando' => $score_blando,
            'clasificacion' => $clasificacion,
            'segmento' => $segmento
        ];
    }
    
    /**
     * Ejecuta el agente de detección de riesgos
     */
    private function ejecutarDeteccionRiesgos($datos) {
        require_once 'agente_deteccion_riesgos.php';
        $agente = new AgenteDeteccionRiesgos($this->mysqli);
        return $agente->analizar($datos['id_aplicacion']);
    }
    
    /**
     * Ejecuta el agente de calendarización
     */
    private function ejecutarCalendarizacion($datos) {
        require_once 'agente_calendarizacion.php';
        $agente = new AgenteCalendarizacion($this->mysqli);
        return $agente->procesar($datos);
    }
    
    /**
     * Ejecuta el agente de feedback de rechazo
     */
    private function ejecutarFeedbackRechazo($datos) {
        require_once 'agente_feedback_rechazo.php';
        $agente = new AgenteFeedbackRechazo($this->mysqli);
        return $agente->generarYEnviar($datos);
    }
}
?>
