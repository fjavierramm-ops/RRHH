<?php
require_once 'config.php';
// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'agente_orquestador.php';
    $orquestador = new AgenteOrquestador($mysqli);
    
    if (isset($_POST['iniciar_onboarding'])) {
        $resultado = $orquestador->ejecutarAgente('seguimiento_ingreso', [
            'accion' => 'iniciar',
            'id_aplicacion' => $_POST['id_aplicacion'],
            'fecha_ingreso' => $_POST['fecha_ingreso'] ?? null
        ]);
        header("Location: admin_onboarding.php?iniciado=1");
        exit;
    }
    
    if (isset($_POST['actualizar_tarea'])) {
        $resultado = $orquestador->ejecutarAgente('seguimiento_ingreso', [
            'accion' => 'actualizar_tarea',
            'id_aplicacion' => $_POST['id_aplicacion'],
            'tarea_nombre' => $_POST['tarea_nombre'],
            'nuevo_estado' => $_POST['nuevo_estado']
        ]);
        header("Location: admin_onboarding.php?actualizado=1");
        exit;
    }
}

// Consulta: Trae SOLO contratados y cruza con la tabla onboarding
$sql = "SELECT 
            a.id_aplicacion,
            c.nombre, v.titulo AS puesto,
            o.id_onboarding, o.fecha_ingreso, 
            o.doc_contratacion, o.config_equipos, o.induccion, o.entrenamiento,
            o.tareas_pendientes, o.fecha_limite_ingreso, o.recordatorios_enviados
        FROM aplicaciones a
        JOIN candidatos c ON a.id_candidato = c.id
        JOIN vacantes v ON a.id_vacante = v.id_vacante
        LEFT JOIN onboarding o ON a.id_aplicacion = o.id_aplicacion
        WHERE a.status_aplicacion = 'Contratado' OR a.status_aplicacion = 'contratado'";


$result = $mysqli->query($sql);

// Helper para el color del puntito
function getDotColor($status) {
    if ($status === 'Completado') return 'verde';
    if ($status === 'En proceso') return 'amarillo';
    return 'gris'; // Pendiente
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Seguimiento de Onboarding</title>
    <link rel="stylesheet" href="estilo_admin.css">
</head>
<body>

    <h1>Seguimiento de Onboarding</h1>
    <hr>

    <div class="filtros">
        <div class="filtro-item">
            <label>Estado:</label>
            <select><option>Todos</option></select>
        </div>
        <input type="text" placeholder="Buscar empleado...">
    </div>

    <div class="contenedor-grid">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): 
                // Fecha de ingreso o "Por definir"
                $ingreso = $row['fecha_ingreso'] ? date('d/m/Y', strtotime($row['fecha_ingreso'])) : 'Por definir';
            ?>
            <div class="card">
                <h2><?php echo htmlspecialchars($row['nombre']); ?></h2>
                <small><?php echo htmlspecialchars($row['puesto']); ?></small>
                
                <div class="etiqueta-ingreso">Ingreso: <?php echo $ingreso; ?></div>

                <p style="margin-top: 15px; font-weight: 600;">Progreso:</p>

                <div class="checklist-item">
                    <span>Documentación</span>
                    <span>
                        <div class="punto <?php echo getDotColor($row['doc_contratacion']); ?>"></div>
                        <?php echo $row['doc_contratacion'] ?? 'Pendiente'; ?>
                    </span>
                </div>

                <div class="checklist-item">
                    <span>Config. Equipos</span>
                    <span>
                        <div class="punto <?php echo getDotColor($row['config_equipos']); ?>"></div>
                        <?php echo $row['config_equipos'] ?? 'Pendiente'; ?>
                    </span>
                </div>

                <div class="checklist-item">
                    <span>Inducción</span>
                    <span>
                        <div class="punto <?php echo getDotColor($row['induccion']); ?>"></div>
                        <?php echo $row['induccion'] ?? 'Pendiente'; ?>
                    </span>
                </div>

                <?php 
$onboarding_iniciado = !empty($row['id_onboarding']);
$tareas = json_decode($row['tareas_pendientes'] ?? '{}', true) ?? [];
?>

<?php if (!$onboarding_iniciado): ?>
    <form method="POST">
        <input type="hidden" name="id_aplicacion" value="<?php echo $row['id_aplicacion']; ?>">
        <input type="hidden" name="iniciar_onboarding" value="1">
        <label>Fecha ingreso:</label>
        <input type="date" name="fecha_ingreso" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>
        <button type="submit" class="btn btn-green">Iniciar Onboarding</button>
    </form>
<?php else: ?>
    <?php foreach ($tareas as $tarea_key => $tarea): ?>
        <form method="POST" style="display: flex; gap: 5px; margin: 5px 0;">
            <input type="hidden" name="id_aplicacion" value="<?php echo $row['id_aplicacion']; ?>">
            <input type="hidden" name="actualizar_tarea" value="1">
            <input type="hidden" name="tarea_nombre" value="<?php echo $tarea_key; ?>">
            <span style="flex: 1;"><?php echo $tarea['nombre']; ?>:</span>
            <select name="nuevo_estado">
                <option value="Pendiente" <?php echo ($tarea['estado'] === 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                <option value="En proceso" <?php echo ($tarea['estado'] === 'En proceso') ? 'selected' : ''; ?>>En proceso</option>
                <option value="Completado" <?php echo ($tarea['estado'] === 'Completado') ? 'selected' : ''; ?>>Completado</option>
            </select>
            <button type="submit" style="padding: 3px 8px;">Actualizar</button>
        </form>
    <?php endforeach; ?>
<?php endif; ?>

            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No hay candidatos contratados actualmente.</p>
        <?php endif; ?>
    </div>

</body>
</html>