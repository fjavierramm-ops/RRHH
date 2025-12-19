<?php
require_once 'config.php';
// Procesar validación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['validar'])) {
    require_once 'agente_orquestador.php';
    $orquestador = new AgenteOrquestador($mysqli);
    $resultado = $orquestador->ejecutarAgente('validacion_proceso', [
        'id_aplicacion' => $_POST['id_aplicacion']
    ]);
    header("Location: admin_validacion.php?validado=" . $_POST['id_aplicacion']);
    exit;
}

// 1. Lógica para obtener departamentos únicos para el filtro
$deptos_sql = "SELECT DISTINCT empresa FROM vacantes ORDER BY empresa";
$deptos_result = $mysqli->query($deptos_sql);

// 2. Capturar el filtro si existe
$filtro_depto = isset($_GET['depto']) ? $mysqli->real_escape_string($_GET['depto']) : '';

// 3. Consulta Principal con Filtro Dinámico
$sql = "SELECT 
            a.id_aplicacion, a.fecha_aplicacion, a.status_aplicacion,
            c.nombre AS candidato,
            v.titulo AS vacante, v.empresa AS departamento,
            COUNT(CASE WHEN cv.estado_validacion = 'aprobado' THEN 1 END) AS validaciones_aprobadas,
            COUNT(CASE WHEN cv.estado_validacion = 'pendiente' THEN 1 END) AS validaciones_pendientes
        FROM aplicaciones a
        JOIN candidatos c ON a.id_candidato = c.id
        JOIN vacantes v ON a.id_vacante = v.id_vacante
        LEFT JOIN comentarios_validacion cv ON a.id_aplicacion = cv.id_aplicacion AND cv.autor = 'Sistema (Agente)'
        WHERE 1=1
        GROUP BY a.id_aplicacion";


if ($filtro_depto != '' && $filtro_depto != 'Todos') {
    $sql .= " AND v.empresa = '$filtro_depto'";
}

$sql .= " ORDER BY a.fecha_aplicacion DESC";
$result = $mysqli->query($sql);

// 4. Helper para colores (CORREGIDO)
function getBadgeClass($status) {
    // Convertimos a minúsculas para comparar fácil
    $s = mb_strtolower($status);
    
    // Validado o Contratado -> VERDE
    if (strpos($s, 'validado') !== false || strpos($s, 'contratado') !== false) {
        return 'badge-validado';
    }
    // En revisión o En proceso -> AMARILLO
    if (strpos($s, 'revisión') !== false || strpos($s, 'proceso') !== false) {
        return 'badge-revision';
    }
    // Rechazado o Ajustes -> ROJO
    return 'badge-ajustes';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Validación Cliente Interno</title>
    <link rel="stylesheet" href="estilo_admin.css">
</head>
<body>

    <h1>Validación con Cliente Interno</h1>
    <hr>

    <form method="GET" action="admin_validacion.php">
        <div class="filtros">
            <div class="filtro-item">
                <label>Filtrar por Departamento:</label>
                <select name="depto" onchange="this.form.submit()">
                    <option value="Todos">Todos los departamentos</option>
                    <?php while($d = $deptos_result->fetch_assoc()): ?>
                        <option value="<?php echo $d['empresa']; ?>" <?php echo ($filtro_depto == $d['empresa']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['empresa']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <?php if($filtro_depto): ?>
                <div class="filtro-item" style="justify-content: flex-end;">
                    <a href="admin_validacion.php" class="btn btn-orange" style="margin:0; text-align:center;">Limpiar Filtros</a>
                </div>
            <?php endif; ?>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>Entregable / Candidato</th>
                <th>Departamento Destino</th>
                <th>Fecha Envío</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>
                        <strong>Reporte: <?php echo htmlspecialchars($row['candidato']); ?></strong><br>
                        <small>Para: <?php echo htmlspecialchars($row['vacante']); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($row['departamento']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['fecha_aplicacion'])); ?></td>
                    <td>
                        <span class="estado-badge <?php echo getBadgeClass($row['status_aplicacion']); ?>">
                            <?php echo htmlspecialchars($row['status_aplicacion']); ?>
                        </span>
                    </td>
                    <td>
    <button class="btn">Ver detalle</button>
    <form method="POST" style="display: inline;">
        <input type="hidden" name="id_aplicacion" value="<?php echo $row['id_aplicacion']; ?>">
        <input type="hidden" name="validar" value="1">
        <button type="submit" class="btn" style="background-color: #22c55e; color: white;">
            Validar Proceso
        </button>
    </form>
</td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align:center; padding: 20px;">No se encontraron validaciones con esos filtros.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>