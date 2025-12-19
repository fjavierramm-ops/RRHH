<?php
require_once 'config.php';

// 1. Obtener puestos únicos para llenar el filtro (dropdown)
$puestos_sql = "SELECT DISTINCT titulo FROM vacantes ORDER BY titulo";
$puestos_result = $mysqli->query($puestos_sql);

// 2. Capturar variables que vienen del formulario (si existen)
$filtro_puesto = isset($_GET['puesto']) ? $mysqli->real_escape_string($_GET['puesto']) : '';
$filtro_score = isset($_GET['score']) ? $_GET['score'] : '';

// 3. Construir la consulta BASE
// Solo traemos candidatos activos (ni rechazados ni contratados)
$sql = "SELECT 
            a.id_aplicacion,
            c.id, c.nombre, c.habilidades_tecnicas,
            v.titulo AS vacante,
            e.score_global,
            COUNT(r.id_riesgo) AS total_riesgos,
            MAX(CASE WHEN r.severidad = 'alta' THEN 1 ELSE 0 END) AS tiene_riesgo_alto
        FROM aplicaciones a
        JOIN candidatos c ON a.id_candidato = c.id
        JOIN vacantes v ON a.id_vacante = v.id_vacante
        LEFT JOIN evaluaciones e ON a.id_aplicacion = e.id_aplicacion
        LEFT JOIN riesgos_detectados r ON a.id_aplicacion = r.id_aplicacion AND r.revisado = FALSE
        WHERE a.status_aplicacion IN ('En proceso', 'Entrevista', 'En revisión')
        GROUP BY a.id_aplicacion";


// 4. Aplicar los FILTROS a la consulta SQL
if ($filtro_puesto != '' && $filtro_puesto != 'Todos') {
    $sql .= " AND v.titulo = '$filtro_puesto'";
}

if ($filtro_score == 'alto') {
    $sql .= " AND e.score_global >= 80";
}

// Ejecutar la consulta final
$result = $mysqli->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Evaluación de Candidatos - RRHH</title> <link rel="stylesheet" href="estilo_admin.css">
</head>
<body>

    <h1>Evaluación de Candidatos</h1>
    <hr>

    <form method="GET" action="admin_evaluacion.php">
        <div class="filtros">
            <div class="filtro-item">
                <label>Filtrar por puesto:</label>
                <select name="puesto" onchange="this.form.submit()">
                    <option value="Todos">Todos los puestos</option>
                    <?php 
                    // Reiniciamos el puntero del resultado de puestos para recorrerlo
                    if($puestos_result) $puestos_result->data_seek(0); 
                    while($p = $puestos_result->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $p['titulo']; ?>" <?php echo ($filtro_puesto == $p['titulo']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['titulo']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="filtro-item">
                <label>Filtrar por puntuación:</label>
                <select name="score" onchange="this.form.submit()">
                    <option value="Todos">Todas las puntuaciones</option>
                    <option value="alto" <?php echo ($filtro_score == 'alto') ? 'selected' : ''; ?>>Mayor a 80%</option>
                </select>
            </div>
            
            <?php if($filtro_puesto || $filtro_score): ?>
                <div class="filtro-item" style="justify-content: flex-end;">
                    <a href="admin_evaluacion.php" class="btn btn-orange" style="margin:0; text-align:center;">Limpiar Filtros</a>
                </div>
            <?php endif; ?>
        </div>
    </form>

    <div class="contenedor-grid">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): 
                $score = isset($row['score_global']) && $row['score_global'] !== null ? intval($row['score_global']) : 0;
                // Convertir texto de habilidades en array para mostrar etiquetas
                $skills = $row['habilidades_tecnicas'] ? explode(',', $row['habilidades_tecnicas']) : [];
                $skills = array_slice($skills, 0, 3);
            ?>
            <div class="card">
                <h2><?php echo htmlspecialchars($row['nombre']); ?></h2>
                <small><?php echo htmlspecialchars($row['vacante']); ?></small>
                <?php if ($row['total_riesgos'] > 0): ?>
    <div style="margin: 10px 0; padding: 8px; background-color: <?php echo $row['tiene_riesgo_alto'] ? '#fee2e2' : '#fef3c7'; ?>; 
                border-left: 4px solid <?php echo $row['tiene_riesgo_alto'] ? '#ef4444' : '#f59e0b'; ?>; 
                border-radius: 4px;">
        <strong style="color: <?php echo $row['tiene_riesgo_alto'] ? '#991b1b' : '#92400e'; ?>;">
            ⚠️ <?php echo $row['total_riesgos']; ?> riesgo(s) detectado(s)
        </strong>
        <?php if ($row['tiene_riesgo_alto']): ?>
            <span style="color: #991b1b;">- ALTA PRIORIDAD</span>
        <?php endif; ?>
        
        <?php
        // Obtener riesgos detallados
        $sql_riesgos = "SELECT tipo_riesgo, severidad, descripcion, score_riesgo 
                        FROM riesgos_detectados 
                        WHERE id_aplicacion = ? AND revisado = FALSE 
                        ORDER BY score_riesgo DESC";
        $stmt_riesgos = $mysqli->prepare($sql_riesgos);
        $stmt_riesgos->bind_param("i", $row['id_aplicacion']);
        $stmt_riesgos->execute();
        $riesgos = $stmt_riesgos->get_result();
        ?>
        
        <?php if ($riesgos->num_rows > 0): ?>
            <div style="margin-top: 10px;">
                <strong>Riesgos detectados:</strong>
                <ul style="margin: 5px 0; padding-left: 20px; font-size: 0.9rem;">
                    <?php while ($riesgo = $riesgos->fetch_assoc()): ?>
                        <li style="color: <?php echo $riesgo['severidad'] === 'alta' ? '#c62828' : '#f59e0b'; ?>;">
                            <?php echo htmlspecialchars($riesgo['descripcion']); ?>
                            (<?php echo $riesgo['severidad']; ?> - Score: <?php echo $riesgo['score_riesgo']; ?>)
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
                <div class="porcentaje"><?php echo $score; ?>%</div>
                
                <div class="barra-fondo">
                    <?php $colorBarra = ($score < 50) ? '#ffb300' : '#22c55e'; ?>
                    <div class="barra-progreso" style="width: <?php echo $score; ?>%; background-color: <?php echo $colorBarra; ?>;"></div>
                </div>

                <p><strong>Habilidades principales:</strong></p>
                <div class="tags-container">
                    <?php if(count($skills) > 0): ?>
                        <?php foreach($skills as $skill): ?>
                            <span class="tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span style="font-size: 0.8rem; color: #999;">Sin habilidades registradas</span>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
    <a href="#" class="btn">Ver detalles</a>
    <form method="POST" action="procesar_accion_candidato.php" style="display: inline;">
        <input type="hidden" name="id_aplicacion" value="<?php echo $row['id_aplicacion'] ?? ''; ?>">
        <input type="hidden" name="accion" value="programar_entrevista">
        <button type="submit" class="btn" style="background-color: #22c55e; color: white; border: none; cursor: pointer;">
            Programar Entrevista
        </button>
    </form>
    <form method="POST" action="procesar_accion_candidato.php" style="display: inline;">
        <input type="hidden" name="id_aplicacion" value="<?php echo $row['id_aplicacion'] ?? ''; ?>">
        <input type="hidden" name="accion" value="rechazar">
        <button type="submit" class="btn" style="background-color: #ef4444; color: white; border: none; cursor: pointer;" 
                onclick="return confirm('¿Estás seguro de rechazar a este candidato?');">
            Rechazar
        </button>
    </form>
    <form method="POST" action="procesar_accion_candidato.php" style="display: inline;">
    <input type="hidden" name="id_aplicacion" value="<?php echo $row['id_aplicacion']; ?>">
    <input type="hidden" name="accion" value="contratar">
    <input type="date" name="fecha_ingreso" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" style="padding: 5px; margin-right: 5px;">
    <button type="submit" class="btn" style="background-color: #10b981; color: white;" 
            onclick="return confirm('¿Contratar? Se iniciará onboarding.');">
        Contratar
    </button>
</form>
</div>

            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="width: 100%; text-align: center; color: #666; font-size: 1.2rem; padding: 40px;">
                No se encontraron candidatos con estos filtros.<br>
                <small>(Recuerda: Solo se muestran candidatos en estatus 'En proceso', 'Entrevista' o 'En revisión')</small>
            </p>
        <?php endif; ?>
    </div>

</body>
</html>