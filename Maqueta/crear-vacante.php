<?php 
require_once("config.php");
$conn = connection();
$conn->set_charset("utf8mb4");

$vacante = null;
$editMode = false;

/* ===============================
   MODO EDICIÓN
================================ */
if (isset($_GET['edit'])) {
    $editMode = true;
    $id = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM vacantes WHERE id_vacante = $id");
    if ($res && $res->num_rows > 0) {
        $vacante = $res->fetch_assoc();
    } else {
        die("Vacante no encontrada");
    }
}

/* ===============================
   GUARDAR
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titulo        = $_POST['titulo'] ?? '';
    $departamento  = $_POST['departamento'] ?? '';
    $tipo          = $_POST['tipo'] ?? '';
    $ubicacion     = $_POST['ubicacion'] ?? '';
    $descripcion   = $_POST['descripcion'] ?? '';
    $requisitos    = $_POST['requisitos'] ?? '';
    $salario       = isset($_POST['salario']) ? intval($_POST['salario']) : 0;
    $fechaApertura = $_POST['fechaApertura'] ?? date('Y-m-d');
    $fechaCierre   = $_POST['fechaCierre'] ?? $fechaApertura;
    $responsable   = $_POST['responsable'] ?? '';
    $estado        = $_POST['estado'] ?? 'Activa';

    // Si no hay descripción, generar automáticamente (solo en modo creación)
    if (empty($descripcion) && empty($_POST['idVacante'])) {
        require_once 'agente_redaccion_vacantes.php';
        $agente_redaccion = new AgenteRedaccionVacantes($conn);
        $descripcion = $agente_redaccion->generarDescripcion([
            'titulo' => $titulo,
            'departamento' => $departamento,
            'tipo' => $tipo,
            'ubicacion' => $ubicacion,
            'requisitos' => $requisitos
        ]);
    }

    if (!empty($_POST['idVacante'])) {
        // EDITAR (no tocar fecha_creacion)
        // departamento se mapea a empresa, tipo se mapea a tipo_trabajo, fechaApertura se mapea a fecha_publicacion
        $stmt = $conn->prepare("
    UPDATE vacantes SET
    titulo=?, 
    empresa=?,
    tipo_trabajo=?,
    ubicacion=?,
    descripcion=?, 
    requisitos=?, 
    salario=?,
    fecha_publicacion=?,
    estado=?
    WHERE id_vacante=?
");
$stmt->bind_param(
    "ssssssissi",  // 10 tipos: 8 columnas + 1 WHERE id
    $titulo, $departamento, $tipo, $ubicacion,
    $descripcion, $requisitos, $salario,
    $fechaApertura, $estado,
    $_POST['idVacante']  // id_vacante para WHERE
);
        $stmt->execute();
        $id = $_POST['idVacante'];
    } else {
        // INSERTAR (incluye fecha_creacion automática)
        $fecha_creacion = date('Y-m-d');
        $stmt = $conn->prepare("
    INSERT INTO vacantes
    (titulo, empresa, tipo_trabajo, ubicacion, descripcion, requisitos,
     salario, fecha_publicacion, estado)
    VALUES (?,?,?,?,?,?,?,?,?)
");
$stmt->bind_param(
    "ssssssiss",  // 9 tipos (una por cada columna)
    $titulo, $departamento, $tipo, $ubicacion,
    $descripcion, $requisitos, $salario,
    $fechaApertura, $estado
);
        $stmt->execute();
        $id = $conn->insert_id;
    }
    
    // Asegurar que no hay output antes del header
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    header("Location: ver-vacante.php?view=$id");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Crear Vacante</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="styles.css" />
</head>

<body>
  <div class="container">
    <h1 class="section-title" id="tituloVacante">
      <?= $editMode ? 'Editar Vacante' : 'Crear Nueva Vacante' ?>
    </h1>

    <div class="card">
      <form method="POST">

        <?php if ($editMode): ?>
          <input type="hidden" name="idVacante" value="<?= $vacante['idVacante'] ?>">
        <?php endif; ?>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label">Título de la vacante</label>
            <input type="text" name="titulo" class="form-control"
                   placeholder="Ejemplo: Desarrollador Backend"
                   value="<?= htmlspecialchars($vacante['titulo'] ?? '', ENT_QUOTES) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Departamento</label>
            <select name="departamento" class="form-select">
              <?php
              $deps = ['Tecnologías de la Información','Marketing','Recursos Humanos','Operaciones','Finanzas'];
              foreach ($deps as $d) {
                $sel = ((($vacante['departamento'] ?? '') === $d) ? 'selected' : '');
                echo "<option $sel>$d</option>";
              }
              ?>
            </select>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label">Tipo de contratación</label>
            <select name="tipo" class="form-select">
              <?php
              $tipos = ['Tiempo completo','Medio tiempo','Por proyecto'];
              foreach ($tipos as $t) {
                $sel = ((($vacante['tipo'] ?? '') === $t) ? 'selected' : '');
                echo "<option $sel>$t</option>";
              }
              ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Ubicación</label>
            <input type="text" name="ubicacion" class="form-control"
                   placeholder="Ejemplo: Ciudad de México o Remoto"
                   value="<?= htmlspecialchars($vacante['ubicacion'] ?? '', ENT_QUOTES) ?>">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Descripción general</label>
          <div class="d-flex justify-content-between mb-2">
            <span></span>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="generarDescripcionIA()">
              🤖 Generar con IA
            </button>
          </div>
          <textarea name="descripcion" id="descripcion" class="form-control" rows="4"
            placeholder="Describe brevemente las responsabilidades del puesto..."><?= htmlspecialchars($vacante['descripcion'] ?? '', ENT_QUOTES) ?></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label">Requisitos</label>
          <textarea name="requisitos" class="form-control" rows="4"
            placeholder="Lista los requisitos esenciales del puesto..."><?= htmlspecialchars($vacante['requisitos'] ?? '', ENT_QUOTES) ?></textarea>
        </div>

        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label">Salario estimado (MXN)</label>
            <input type="number" name="salario" class="form-control"
                   placeholder="Ejemplo: 25000"
                   value="<?= htmlspecialchars($vacante['salario'] ?? '', ENT_QUOTES) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Fecha de apertura</label>
            <input type="date" name="fechaApertura" class="form-control"
                   value="<?= htmlspecialchars($vacante['fechaApertura'] ?? '', ENT_QUOTES) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Fecha de cierre</label>
            <input type="date" name="fechaCierre" class="form-control"
                   value="<?= htmlspecialchars($vacante['fechaCierre'] ?? '', ENT_QUOTES) ?>">
          </div>
        </div>

        <div class="row mb-4">
          <div class="col-md-6">
            <label class="form-label">Responsable del proceso</label>
            <input type="text" name="responsable" class="form-control"
                   placeholder="Nombre del reclutador o encargado"
                   value="<?= htmlspecialchars($vacante['responsable'] ?? '', ENT_QUOTES) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Estado de la vacante</label>
            <select name="estado" class="form-select">
              <?php
              $estados = ['Activa','En revisión','Cerrada'];
              foreach ($estados as $e) {
                $sel = ((($vacante['estado'] ?? '') === $e) ? 'selected' : '');
                echo "<option $sel>$e</option>";
              }
              ?>
            </select>
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
          <a href="administrador.php" class="btn btn-outline">Cancelar</a>
          <button type="submit" class="btn">
            <?= $editMode ? 'Actualizar vacante' : 'Guardar vacante' ?>
          </button>
        </div>

      </form>
    </div>
  </div>

  <script>
  function generarDescripcionIA() {
    const titulo = document.querySelector('input[name="titulo"]').value;
    const departamento = document.querySelector('select[name="departamento"]').value;
    const tipo = document.querySelector('select[name="tipo"]').value;
    const ubicacion = document.querySelector('input[name="ubicacion"]').value;
    const requisitos = document.querySelector('textarea[name="requisitos"]').value;
    
    if (!titulo || !requisitos) {
      alert('Por favor completa título y requisitos primero');
      return;
    }
    
    // Mostrar indicador de carga
    const btn = event.target;
    const textoOriginal = btn.textContent;
    btn.disabled = true;
    btn.textContent = '⏳ Generando...';
    
    // Llamar a endpoint AJAX
    fetch('generar_descripcion_ia.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({titulo, departamento, tipo, ubicacion, requisitos})
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        document.getElementById('descripcion').value = data.descripcion;
        btn.textContent = '✅ Generado';
        setTimeout(() => {
          btn.textContent = textoOriginal;
          btn.disabled = false;
        }, 2000);
      } else {
        alert('Error al generar descripción: ' + (data.error || 'Error desconocido'));
        btn.textContent = textoOriginal;
        btn.disabled = false;
      }
    })
    .catch(error => {
      alert('Error al conectar con el servidor');
      btn.textContent = textoOriginal;
      btn.disabled = false;
    });
  }
  </script>
</body>
</html>
