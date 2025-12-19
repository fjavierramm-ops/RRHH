<?php 
require_once("config.php");
$conn = connection();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset("utf8mb4");

if (!$conn) die("Error de conexión: " . mysqli_connect_error());

$vacante = null;
$editMode = false;

// MODO VIEW: Ver detalle de vacante
if (isset($_GET['view'])) {
    $id = intval($_GET['view']);
    // Usar alias para compatibilidad con el sistema nuevo
    $sql = "SELECT 
                id_vacante,
                id_vacante AS idVacante,
                titulo,
                COALESCE(departamento, empresa) AS departamento,
                COALESCE(tipo, tipo_trabajo) AS tipo,
                ubicacion,
                descripcion,
                requisitos,
                salario,
                COALESCE(fechaApertura, fecha_publicacion) AS fechaApertura,
                COALESCE(fechaCierre, fecha_publicacion) AS fechaCierre,
                COALESCE(responsable, 'RRHH') AS responsable,
                estado
            FROM vacantes 
            WHERE id_vacante = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $vacante = $result->num_rows > 0 ? $result->fetch_assoc() : die("Vacante no encontrada");
    $stmt->close();
}

// MODO EDIT: Editar vacante
if (isset($_GET['edit'])) {
    $editMode = true;
    $id = intval($_GET['edit']);
    // Usar alias para compatibilidad
    $sql = "SELECT 
                id_vacante,
                id_vacante AS idVacante,
                titulo,
                COALESCE(departamento, empresa) AS departamento,
                COALESCE(tipo, tipo_trabajo) AS tipo,
                ubicacion,
                descripcion,
                requisitos,
                salario,
                COALESCE(fechaApertura, fecha_publicacion) AS fechaApertura,
                COALESCE(fechaCierre, fecha_publicacion) AS fechaCierre,
                COALESCE(responsable, 'RRHH') AS responsable,
                estado
            FROM vacantes 
            WHERE id_vacante = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $vacante = $result->num_rows > 0 ? $result->fetch_assoc() : die("Vacante no encontrada para editar");
    $stmt->close();
}

// POST para insertar/editar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $departamento = trim($_POST['departamento'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $requisitos = trim($_POST['requisitos'] ?? '');
    $fechaApertura = !empty($_POST['fechaApertura']) ? $_POST['fechaApertura'] : date('Y-m-d');
    $fechaCierre = !empty($_POST['fechaCierre']) ? $_POST['fechaCierre'] : $fechaApertura;
    $responsable = trim($_POST['responsable'] ?? '');
    $salario = max(0, intval($_POST['salario'] ?? 0));
    $estado = $_POST['estado'] ?? 'Activa';

    if (!empty($_POST['idVacante'])) {
        // EDITAR: Actualizar usando columnas de la BD de Maqueta
        $idVacante = intval($_POST['idVacante']);
        $stmt = $conn->prepare("
            UPDATE vacantes SET 
                titulo=?, 
                empresa=?, 
                departamento=?,
                tipo_trabajo=?, 
                tipo=?,
                ubicacion=?, 
                descripcion=?, 
                requisitos=?, 
                salario=?, 
                fecha_publicacion=?,
                fechaApertura=?,
                fechaCierre=?,
                responsable=?,
                estado=? 
            WHERE id_vacante=?
        ");
        $stmt->bind_param(
            "sssssssisssssi", 
            $titulo, 
            $departamento,  // Guardar en empresa también
            $departamento,  // Guardar en departamento (nueva columna)
            $tipo,          // Guardar en tipo_trabajo
            $tipo,          // Guardar en tipo (nueva columna)
            $ubicacion, 
            $descripcion, 
            $requisitos, 
            $salario, 
            $fechaApertura, // Guardar en fecha_publicacion
            $fechaApertura, // Guardar en fechaApertura (nueva columna)
            $fechaCierre,   // Guardar en fechaCierre (nueva columna)
            $responsable,   // Guardar en responsable (nueva columna)
            $estado,
            $idVacante
        );
    } else {
        // INSERTAR: Insertar usando columnas de la BD de Maqueta
        $fecha_creacion = date('Y-m-d');
        $stmt = $conn->prepare("
            INSERT INTO vacantes 
                (titulo, empresa, departamento, tipo_trabajo, tipo, ubicacion, descripcion, requisitos, 
                 salario, fecha_publicacion, fechaApertura, fechaCierre, responsable, estado, fecha_creacion) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "ssssssssissssss", 
            $titulo, 
            $departamento,  // Guardar en empresa
            $departamento,  // Guardar en departamento (nueva columna)
            $tipo,          // Guardar en tipo_trabajo
            $tipo,          // Guardar en tipo (nueva columna)
            $ubicacion, 
            $descripcion, 
            $requisitos, 
            $salario, 
            $fechaApertura, // Guardar en fecha_publicacion
            $fechaApertura, // Guardar en fechaApertura (nueva columna)
            $fechaCierre,   // Guardar en fechaCierre (nueva columna)
            $responsable,   // Guardar en responsable (nueva columna)
            $estado,
            $fecha_creacion
        );
    }

    try {
        $stmt->execute();
        $last_id = !empty($_POST['idVacante']) ? intval($_POST['idVacante']) : $conn->insert_id;
        // Redirige a ver-vacante.php con el id insertado o editado
        echo "<script>
                  alert('Vacante guardada correctamente.');
                  window.location.href='ver-vacante.php?view={$last_id}';
              </script>";
        exit;
    } catch (mysqli_sql_exception $e) {
        die("Error al guardar vacante: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($vacante) && !$editMode ? 'Detalle de Vacante' : ($editMode ? 'Editar Vacante' : 'Crear Vacante') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background-color: #f8f9fa; }
.status.enviado { background-color: #d1e7dd; color: #0f5132; padding: 2px 8px; border-radius: 5px; }
.status.pendiente { background-color: #fff3cd; color: #664d03; padding: 2px 8px; border-radius: 5px; }
.status.fallido { background-color: #f8d7da; color: #842029; padding: 2px 8px; border-radius: 5px; }
</style>
</head>
<body>
<div class="container mt-5">
<h1 class="mb-4 text-center"><?= isset($vacante) && !$editMode ? 'Detalle de Vacante' : ($editMode ? 'Editar Vacante' : 'Crear Vacante') ?></h1>

<div id="detalleVacante" class="card p-4 shadow-sm">
<?php if (!isset($vacante) || $editMode): ?>
<form method="POST" action="">
    <?php if($editMode): ?>
        <input type="hidden" name="idVacante" value="<?= htmlspecialchars($vacante['id_vacante'] ?? $vacante['idVacante'] ?? '') ?>">
    <?php endif; ?>

    <div class="row mb-3">
        <div class="col-md-6">
            <label for="titulo" class="form-label">Título de la vacante</label>
            <input type="text" id="titulo" name="titulo" class="form-control" placeholder="Ejemplo: Desarrollador Backend" required value="<?= htmlspecialchars($vacante['titulo'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label for="departamento" class="form-label">Departamento</label>
            <select id="departamento" name="departamento" class="form-select" required>
                <option value="Tecnologías de la Información" <?= ($vacante['departamento'] ?? '') === 'Tecnologías de la Información' ? 'selected' : '' ?>>Tecnologías de la Información</option>
                <option value="Recursos Humanos" <?= ($vacante['departamento'] ?? '') === 'Recursos Humanos' ? 'selected' : '' ?>>Recursos Humanos</option>
                <option value="Ventas" <?= ($vacante['departamento'] ?? '') === 'Ventas' ? 'selected' : '' ?>>Ventas</option>
                <option value="Marketing" <?= ($vacante['departamento'] ?? '') === 'Marketing' ? 'selected' : '' ?>>Marketing</option>
                <option value="Operaciones" <?= ($vacante['departamento'] ?? '') === 'Operaciones' ? 'selected' : '' ?>>Operaciones</option>
                <option value="Finanzas" <?= ($vacante['departamento'] ?? '') === 'Finanzas' ? 'selected' : '' ?>>Finanzas</option>
            </select>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label for="tipo" class="form-label">Tipo de Empleo</label>
            <select id="tipo" name="tipo" class="form-select" required>
                <option value="Tiempo Completo" <?= ($vacante['tipo'] ?? '') === 'Tiempo Completo' ? 'selected' : '' ?>>Tiempo Completo</option>
                <option value="Tiempo Parcial" <?= ($vacante['tipo'] ?? '') === 'Tiempo Parcial' ? 'selected' : '' ?>>Tiempo Parcial</option>
                <option value="Contrato" <?= ($vacante['tipo'] ?? '') === 'Contrato' ? 'selected' : '' ?>>Contrato</option>
                <option value="Medio tiempo" <?= ($vacante['tipo'] ?? '') === 'Medio tiempo' ? 'selected' : '' ?>>Medio tiempo</option>
                <option value="Por proyecto" <?= ($vacante['tipo'] ?? '') === 'Por proyecto' ? 'selected' : '' ?>>Por proyecto</option>
            </select>
        </div>
        <div class="col-md-6">
            <label for="ubicacion" class="form-label">Ubicación</label>
            <input type="text" id="ubicacion" name="ubicacion" class="form-control" placeholder="Ej: Remoto, Ciudad de México, Monterrey" required value="<?= htmlspecialchars($vacante['ubicacion'] ?? '') ?>">
        </div>
    </div>

    <div class="mb-3">
        <label for="descripcion" class="form-label">Descripción de la vacante</label>
        <textarea id="descripcion" name="descripcion" class="form-control" rows="3" placeholder="Detalle las responsabilidades del puesto..." required><?= htmlspecialchars($vacante['descripcion'] ?? '') ?></textarea>
    </div>

    <div class="mb-4">
        <label for="requisitos" class="form-label">Requisitos clave</label>
        <textarea id="requisitos" name="requisitos" class="form-control" rows="3" placeholder="Ej: 3+ años de experiencia en React, manejo de bases de datos SQL..." required><?= htmlspecialchars($vacante['requisitos'] ?? '') ?></textarea>
    </div>

    <div class="row mb-3 align-items-end">
        <div class="col-md-4">
            <label for="salario" class="form-label">Salario estimado (MXN)</label>
            <input type="number" id="salario" name="salario" class="form-control" step="1000" min="0" placeholder="Ejemplo: 25000" required value="<?= htmlspecialchars($vacante['salario'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label for="fechaApertura" class="form-label">Fecha de apertura</label>
            <input type="date" id="fechaApertura" name="fechaApertura" class="form-control" required value="<?= htmlspecialchars($vacante['fechaApertura'] ?? date('Y-m-d')) ?>">
        </div>
        <div class="col-md-4">
            <label for="fechaCierre" class="form-label">Fecha de cierre</label>
            <input type="date" id="fechaCierre" name="fechaCierre" class="form-control" required value="<?= htmlspecialchars($vacante['fechaCierre'] ?? date('Y-m-d')) ?>">
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <label for="responsable" class="form-label">Responsable del proceso</label>
            <input type="text" id="responsable" name="responsable" class="form-control" placeholder="Nombre del reclutador o encargado" required value="<?= htmlspecialchars($vacante['responsable'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label for="estado" class="form-label">Estado de la vacante</label>
            <select id="estado" name="estado" class="form-select">
                <option value="Activa" <?= ($vacante['estado'] ?? 'Activa') === 'Activa' ? 'selected' : '' ?>>Activa</option>
                <option value="En revisión" <?= ($vacante['estado'] ?? '') === 'En revisión' ? 'selected' : '' ?>>En revisión</option>
                <option value="Cerrada" <?= ($vacante['estado'] ?? '') === 'Cerrada' ? 'selected' : '' ?>>Cerrada</option>
                <option value="Abierta" <?= ($vacante['estado'] ?? '') === 'Abierta' ? 'selected' : '' ?>>Abierta</option>
            </select>
        </div>
    </div>
    
    <div class="text-end">
        <button type="button" class="btn btn-secondary me-2" onclick="window.location.href='administrador.php'">Cancelar</button>
        <button type="submit" class="btn btn-primary"><?= $editMode ? 'Actualizar Vacante' : 'Guardar Vacante' ?></button>
    </div>
</form>
<?php else: ?>
<!-- MODO VIEW: Mostrar detalle de vacante -->
<h3><?= htmlspecialchars($vacante['titulo']) ?></h3>
<p><strong>Departamento:</strong> <?= htmlspecialchars($vacante['departamento'] ?? 'N/A') ?></p>
<p><strong>Tipo:</strong> <?= htmlspecialchars($vacante['tipo'] ?? 'N/A') ?></p>
<p><strong>Ubicación:</strong> <?= htmlspecialchars($vacante['ubicacion']) ?></p>
<p><strong>Descripción:</strong><br><?= nl2br(htmlspecialchars($vacante['descripcion'])) ?></p>
<p><strong>Requisitos:</strong><br><?= nl2br(htmlspecialchars($vacante['requisitos'])) ?></p>
<p><strong>Salario estimado:</strong> 
    <?php 
    $salario = $vacante['salario'] ?? 0;
    // Si es string, intentar extraer el número
    if (is_string($salario)) {
        // Remover símbolos y espacios, luego extraer números
        $salario_limpio = preg_replace('/[^0-9]/', '', $salario);
        $salario = !empty($salario_limpio) ? intval($salario_limpio) : 0;
    }
    // Si es número, formatearlo
    if (is_numeric($salario) && $salario > 0) {
        echo number_format((float)$salario, 0, '.', ',') . ' MXN';
    } else {
        echo htmlspecialchars($vacante['salario'] ?? 'No especificado');
    }
    ?>
</p>
<p><strong>Fechas:</strong> <?= $vacante['fechaApertura'] ?? 'N/A' ?> a <?= $vacante['fechaCierre'] ?? 'N/A' ?></p>
<p><strong>Responsable:</strong> <?= htmlspecialchars($vacante['responsable'] ?? 'N/A') ?></p>
<p><strong>Estado:</strong>
    <?php 
    $estado = $vacante['estado'] ?? 'Activa';
    $clase = ($estado === "Activa" || $estado === "Abierta") ? "enviado" : ($estado === "En revisión" ? "pendiente" : "fallido"); 
    ?>
    <span class="status <?= $clase ?>"><?= htmlspecialchars($estado) ?></span>
</p>

<div class="mt-3">
    <a href="ver-vacante.php?edit=<?= $vacante['id_vacante'] ?>" class="btn btn-primary me-2">Editar Vacante</a>
    <button type="button" class="btn btn-secondary" onclick="window.location.href='administrador.php'">Regresar al panel</button>
</div>
<?php endif; ?>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

