<?php
// session_start() ya se llama en config.php
require_once("config.php");
$conn = connection();

/* =========================================================
   PROCESAR INSERCIÓN DEL RESULTADO
   ========================================================= */
if (isset($_POST['procesar_resultado']) && $_POST['procesar_resultado'] == 1) {

    $idEntrevista = intval($_POST['idEntrevista']);
    $resultado    = $_POST['resultado'];

    // Campos opcionales
    $salario_ofrecido  = $_POST['salario_ofrecido'] ?? null;
    $fecha_siguiente   = $_POST['fecha_entrevista'] ?? null;
    $hora_siguiente    = $_POST['hora_inicio'] ?? null;
    $tipo_entrevista   = $_POST['tipo_entrevista'] ?? null;
    $feedback_area     = $_POST['feedback_area'] ?? null;
    $feedback_detalle  = $_POST['feedback_detalle'] ?? null;

    $sql = "INSERT INTO resultados_entrevista
            (idEntrevista, resultado, salario_ofrecido, fecha_siguiente, hora_siguiente,
             tipo_entrevista, feedback_area, feedback_detalle)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "isdsssss",
        $idEntrevista,
        $resultado,
        $salario_ofrecido,
        $fecha_siguiente,
        $hora_siguiente,
        $tipo_entrevista,
        $feedback_area,
        $feedback_detalle
    );

    if ($stmt->execute()) {
    $_SESSION['mensaje_exito'] = "✅ Resultado de entrevista guardado correctamente.";
} else {
    $_SESSION['mensaje_exito'] = "❌ Error al guardar el resultado.";
}

header("Location: disponibilidades.php");
exit;
}

// Procesar feedback de entrevistador
if (isset($_POST['guardar_feedback_entrevistador'])) {
    $idEntrevista_fb = intval($_POST['idEntrevista'] ?? $idEntrevista ?? 0);
    
    if ($idEntrevista_fb <= 0) {
        $_SESSION['mensaje_exito'] = "❌ Error: ID de entrevista no válido.";
        header("Location: post-entrevista.php");
        exit;
    }
    
    $feedback_texto = $_POST['feedback_texto'] ?? '';
    $calificacion = intval($_POST['calificacion'] ?? 0);
    
    $sql_fb = "INSERT INTO feedback_entrevista 
               (id_entrevista, tipo, feedback_texto, calificacion)
               VALUES (?, 'entrevistador', ?, ?)
               ON DUPLICATE KEY UPDATE 
               feedback_texto = VALUES(feedback_texto),
               calificacion = VALUES(calificacion)";
    
    $stmt_fb = $conn->prepare($sql_fb);
    $stmt_fb->bind_param("isi", $idEntrevista_fb, $feedback_texto, $calificacion);
    $stmt_fb->execute();
    
    $_SESSION['mensaje_exito'] = "✅ Feedback de entrevistador guardado.";
    header("Location: post-entrevista.php?idEntrevista=$idEntrevista");
    exit;
}

/* =========================================================
   MENSAJE FLASH
   ========================================================= */
if (isset($_SESSION['mensaje_exito'])) {
    echo '
    <div class="alert alert-success alert-dismissible fade show shadow-sm mx-auto mt-4" style="max-width:700px;" role="alert">
        '.$_SESSION['mensaje_exito'].'
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
    unset($_SESSION['mensaje_exito']);
}

/* =========================================================
   OBTENER ID ENTREVISTA
   ========================================================= */
$idEntrevista = isset($_GET['idEntrevista']) 
    ? intval($_GET['idEntrevista']) 
    : (isset($_POST['idEntrevista']) ? intval($_POST['idEntrevista']) : 0);

/* =========================================================
   DATOS DE LA ENTREVISTA
   ========================================================= */
$datosCandidato = null;
if ($idEntrevista > 0) {
    $sql = "SELECT 
    e.id_entrevista AS idEntrevista,
    c.nombre AS candidato,
    v.titulo AS vacante
FROM entrevistas e
LEFT JOIN candidatos c ON c.id = e.idClientes
LEFT JOIN vacantes v ON v.id_vacante = e.idVacante
WHERE e.id_entrevista = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idEntrevista);
    $stmt->execute();
    $datosCandidato = $stmt->get_result()->fetch_assoc();
}
// Obtener feedback existente
$feedback_entrevistador = null;
$feedback_candidato = null;

$sql_feedback = "SELECT tipo, feedback_texto, calificacion 
                 FROM feedback_entrevista 
                 WHERE id_entrevista = ?";
$stmt_feedback = $conn->prepare($sql_feedback);
$stmt_feedback->bind_param("i", $idEntrevista);
$stmt_feedback->execute();
$result_feedback = $stmt_feedback->get_result();

while ($fb = $result_feedback->fetch_assoc()) {
    if ($fb['tipo'] === 'entrevistador') {
        $feedback_entrevistador = $fb;
    } else {
        $feedback_candidato = $fb;
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Resultado de Entrevista</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: linear-gradient(180deg, #f1f5f9, #ffffff);
}
.card-evaluacion {
    max-width: 720px;
    margin: 60px auto;
    border-radius: 22px;
}
.card-header-evaluacion {
    background: linear-gradient(135deg, #4f46e5, #3b82f6);
    padding: 26px;
}
.info-box {
    background: #f8fafc;
    border-radius: 14px;
    padding: 18px;
}
</style>
</head>

<body>

<div class="card card-evaluacion shadow-lg">
    <div class="card-header card-header-evaluacion text-white">
        <h5 class="mb-0">📝 Resultado de la Entrevista</h5>
    </div>

    <div class="card-body p-4">
        <?php if ($datosCandidato): ?>

        <div class="info-box mb-4">
            <div><strong>Candidato:</strong> <?= htmlspecialchars($datosCandidato['candidato']) ?></div>
            <div><strong>Vacante:</strong> <?= htmlspecialchars($datosCandidato['vacante']) ?></div>
        </div>

        <form method="POST">
            <input type="hidden" name="idEntrevista" value="<?= $idEntrevista ?>">
            <input type="hidden" name="procesar_resultado" value="1">

            <label class="form-label">Resultado</label>
            <select name="resultado" class="form-select mb-3" required onchange="actualizar(this.value)">
                <option value="">Selecciona</option>
                <option value="Aceptacion">Aceptación</option>
                <option value="SiguienteFase">Siguiente fase</option>
                <option value="Rechazo">Rechazo</option>
            </select>

            <div id="dinamico"></div>

            <button class="btn btn-success w-100 mt-3">Guardar resultado</button>
        </form>

        <!-- Sección de Feedback del Entrevistador -->
        <hr class="my-4">
        <h6 class="mb-3">📝 Feedback del Entrevistador</h6>
        
        <?php if ($feedback_entrevistador): ?>
            <div class="info-box mb-3">
                <strong>Feedback guardado:</strong>
                <p class="mb-1"><?= htmlspecialchars($feedback_entrevistador['feedback_texto']) ?></p>
                <small>Calificación: <?= $feedback_entrevistador['calificacion'] ?>/10</small>
            </div>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="idEntrevista" value="<?= $idEntrevista ?>">
                <input type="hidden" name="guardar_feedback_entrevistador" value="1">
                
                <div class="mb-3">
                    <label class="form-label">Feedback del entrevistador</label>
                    <textarea name="feedback_texto" class="form-control" rows="4" 
                              placeholder="Describe tu impresión del candidato, fortalezas, áreas de mejora..."></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Calificación (0-10)</label>
                    <input type="number" name="calificacion" class="form-control" min="0" max="10" value="0">
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Guardar Feedback</button>
            </form>
        <?php endif; ?>

        <?php else: ?>
            <div class="alert alert-warning">Entrevista no encontrada</div>
        <?php endif; ?>
    </div>
</div>

<script>
function actualizar(valor) {
    let d = document.getElementById("dinamico");
    d.innerHTML = "";

    if (valor === "Aceptacion") {
        d.innerHTML = `
            <label>Salario ofrecido</label>
            <input type="number" step="0.01" name="salario_ofrecido" class="form-control" required>
        `;
    }

    if (valor === "SiguienteFase") {
        d.innerHTML = `
            <label>Fecha</label>
            <input type="date" name="fecha_entrevista" class="form-control" required>
            <label class="mt-2">Hora</label>
            <input type="time" name="hora_inicio" class="form-control" required>
            <label class="mt-2">Tipo de entrevista</label>
            <input type="text" name="tipo_entrevista" class="form-control" required>
        `;
    }

    if (valor === "Rechazo") {
        d.innerHTML = `
            <label>Área de feedback</label>
            <input type="text" name="feedback_area" class="form-control">
            <label class="mt-2">Detalle</label>
            <textarea name="feedback_detalle" class="form-control"></textarea>
        `;
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
