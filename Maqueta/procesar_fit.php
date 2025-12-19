<?php
require_once 'config.php';

// Solo permitir si es una petición POST o si se llama internamente
if ($_SERVER['REQUEST_METHOD'] === 'POST' || defined('INTERNAL_CALL')) {
    
    $id_aplicacion = $_POST['id_aplicacion'] ?? $id_app_internal ?? null;

    if (!$id_aplicacion) {
        die("Error: No se especificó la aplicación.");
    }

    // 1. OBTENER DATOS (Candidato vs Vacante)
    // Hacemos un JOIN para traer todo lo que necesitamos comparar
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

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $id_aplicacion);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if (!$data) {
        die("Error: Aplicación no encontrada.");
    }

    // 2. LÓGICA DEL AGENTE (Cálculo de Coincidencias)
    
    // Convertimos todo a minúsculas para comparar mejor
    $requisitos_texto = strtolower($data['requisitos_vacante'] . ' ' . $data['descripcion_vacante']);
    
    // Separamos las habilidades del candidato por comas
    $skills = explode(',', $data['skills_candidato']);
    $softs = explode(',', $data['soft_candidato']);

    // --- A. CALCULAR SCORE TÉCNICO (MEJORADO) ---
    // Mejorar con ponderación de requisitos
    $requisitos_array = explode(',', $data['requisitos_vacante']);
    $requisitos_array = array_map('trim', array_map('strtolower', $requisitos_array));
    $requisitos_array = array_filter($requisitos_array); // Eliminar vacíos
    
    $coincidencias_tec = 0;
    $total_skills = count($skills);
    $total_requisitos = count($requisitos_array);
    $palabras_encontradas = [];

    if ($total_skills > 0 && $total_requisitos > 0) {
        foreach ($skills as $skill) {
            $skill = trim(strtolower($skill));
            if (empty($skill)) continue;
            
            foreach ($requisitos_array as $req) {
                // Coincidencia exacta o parcial
                if (strpos($req, $skill) !== false || strpos($skill, $req) !== false) {
                    $coincidencias_tec++;
                    $palabras_encontradas[] = $skill;
                    break; // Contar cada skill solo una vez
                }
            }
        }
        
        // Score = (coincidencias / max(skills, requisitos)) * 100
        $score_tecnico = intval(($coincidencias_tec / max($total_skills, $total_requisitos)) * 100);
        
        // Bonus por tener muchas habilidades relevantes
        if ($coincidencias_tec >= $total_requisitos * 0.7) {
            $score_tecnico = min(100, $score_tecnico + 10);
        }
    } else {
        $score_tecnico = 0;
    }

    // --- B. CALCULAR SCORE BLANDO (Simulado por ahora) ---
    // Como las habilidades blandas son subjetivas, daremos un puntaje base 
    // y sumaremos si aparecen en la descripción.
    $coincidencias_soft = 0;
    $total_softs = count($softs);
    
    if ($total_softs > 0) {
        foreach ($softs as $soft) {
            $soft = trim(strtolower($soft));
            if (!empty($soft) && strpos($requisitos_texto, $soft) !== false) {
                $coincidencias_soft++;
            }
        }
        $score_blando = 50 + ($coincidencias_soft * 10); // Base 50 + 10 por cada coincidencia
        if($score_blando > 100) $score_blando = 100;
    } else {
        $score_blando = 50; // Puntaje neutral si no llenó nada
    }

    // 3. GENERAR FEEDBACK AUTOMÁTICO
    $feedback = "Análisis Automático: Se detectaron coincidencias en: " . implode(", ", $palabras_encontradas) . ". ";
    if ($score_tecnico < 50) {
        $feedback .= "El perfil técnico parece bajo respecto a los requisitos descritos.";
    } else {
        $feedback .= "El candidato cuenta con las palabras clave principales.";
    }

    // 4. GUARDAR EN BASE DE DATOS (Tabla evaluaciones)
    // Usamos INSERT ... ON DUPLICATE KEY UPDATE para no crear dobles
   // Calcular score global (asegurar que ambos scores estén definidos)
$score_tecnico = isset($score_tecnico) ? intval($score_tecnico) : 0;
$score_blando = isset($score_blando) ? intval($score_blando) : 0;
$score_global = intval(($score_tecnico + $score_blando) / 2);

// Clasificar fit
$clasificacion = 'Bajo Fit';
if ($score_global >= 75) {
    $clasificacion = 'Alto Fit';
} elseif ($score_global >= 50) {
    $clasificacion = 'Medio Fit';
}

// Clasificar en segmentos A, B, C según XML
$segmento = 'C'; // Bajo por defecto
if ($score_global >= 85) {
    $segmento = 'A'; // Alto Potencial
} elseif ($score_global >= 65) {
    $segmento = 'B'; // Coincidencia Media
}

// Actualizar SQL para incluir segmento (score_global es GENERATED, no se inserta)
$sql_insert = "INSERT INTO evaluaciones (id_aplicacion, score_tecnico, score_blando, comentarios_tecnicos, clasificacion_fit, segmento) 
               VALUES (?, ?, ?, ?, ?, ?)
               ON DUPLICATE KEY UPDATE 
               score_tecnico = VALUES(score_tecnico),
               score_blando = VALUES(score_blando),
               comentarios_tecnicos = VALUES(comentarios_tecnicos),
               clasificacion_fit = VALUES(clasificacion_fit),
               segmento = VALUES(segmento)";

    $stmt_ins = $mysqli->prepare($sql_insert);
    if (!$stmt_ins) {
        error_log("Error preparando SQL en procesar_fit.php: " . $mysqli->error);
        if (!defined('INTERNAL_CALL')) {
            die("Error al preparar consulta: " . $mysqli->error);
        }
        return;
    }
    
    $stmt_ins->bind_param("iiisss", $id_aplicacion, $score_tecnico, $score_blando, $feedback, $clasificacion, $segmento);
    
    if ($stmt_ins->execute()) {
        if (!defined('INTERNAL_CALL')) {
            echo "Evaluación procesada. Score Técnico: $score_tecnico%, Score Global: $score_global%";
        }
    } else {
        error_log("Error ejecutando SQL en procesar_fit.php: " . $stmt_ins->error);
        if (!defined('INTERNAL_CALL')) {
            echo "Error al guardar: " . $stmt_ins->error;
        }
    }
    $stmt_ins->close();

} else {
    // Si intentan abrir el archivo directo sin datos
    echo "Agente de Segmentación listo. Esperando datos...";
}
?>