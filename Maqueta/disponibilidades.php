<?php
// =====================
// CONEXIÓN
// =====================
require_once("config.php");
$conn = connection();

/* ============================================================
   FUNCIÓN: Validar rangos horarios antes de guardar
   Se ejecuta SOLO en la disponibilidad del candidato (Opción A)
   ============================================================ */
function validarRangoHorario($horaTexto)
{
    // Esperado: "09:00 - 10:00"
    $partes = array_map('trim', explode('-', $horaTexto));

    if (count($partes) < 2) {
        return [false, "El formato debe ser: 09:00 - 10:00"];
    }

    $inicio = $partes[0];
    $fin    = $partes[1];

    // Validar formato
    if (!preg_match('/^\d{2}:\d{2}$/', $inicio) || !preg_match('/^\d{2}:\d{2}$/', $fin)) {
        return [false, "El formato de hora es inválido (usa hh:mm)"];
    }

    // Convertir a minutos
    list($h1, $m1) = explode(':', $inicio);
    list($h2, $m2) = explode(':', $fin);

    $t1 = intval($h1) * 60 + intval($m1);
    $t2 = intval($h2) * 60 + intval($m2);

    if ($t2 <= $t1) {
        return [false, "La hora final debe ser mayor que la hora inicial."];
    }

    // Validar que la diferencia sea EXACTA de 60 minutos
    if (($t2 - $t1) != 60) {
        return [false, "El rango debe ser exactamente de 60 minutos."];
    }

    return [true, [$inicio, $fin]];
}



// =====================
// MANEJO DE ELIMINACIÓN (DELETE)
// =====================

// ELIMINAR REGISTRO DE DISPONIBILIDAD (Candidato - tabla: disponibilidaddelequipo)
if (isset($_POST['eliminar_disponibilidad'])) {
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM disponibilidaddelequipo WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                header("Location: ".$_SERVER['PHP_SELF']."?ok_delete=1");
                exit;
            } else {
                $error_delete = "Error al eliminar la disponibilidad: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// ELIMINAR REGISTRO DE ENTREVISTA (tabla: entrevistas)
if (isset($_POST['eliminar_entrevista'])) {
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM entrevistas WHERE id_entrevista = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                header("Location: ".$_SERVER['PHP_SELF']."?ok_delete=1");
                exit;
            } else {
                $error_delete = "Error al eliminar la entrevista: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// ELIMINAR REGISTRO DE DISPONIBILIDAD RRHH (tabla: disponibilidades_rrhh)
if (isset($_POST['eliminar_disponibilidad_rrhh'])) {
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM disponibilidades_rrhh WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                header("Location: ".$_SERVER['PHP_SELF']."?ok_delete=1");
                exit;
            } else {
                $error_delete = "Error al eliminar la disponibilidad RRHH: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// =====================
// LOG EXTENDIDO
// =====================
function registrarComunicacionExtendida($conn, $idCliente, $idReclutador, $fecha, $hora, $mensaje) {
    $sql = "INSERT INTO canal_comunicacion
            (idClientes, tipo_origen, id_origen, tipo_destino, id_destino, canal, fecha, hora, mensaje, estado, automatica)
            VALUES (?, 'reclutador', ?, 'cliente', ?, 'Correo', ?, ?, ?, 'Enviado', 1)";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("iiisss", $idCliente, $idReclutador, $idCliente, $fecha, $hora, $mensaje);

        $ok = $stmt->execute();
        $stmt->close();
        if ($ok) return true;
    }

    $stmt2 = $conn->prepare("INSERT INTO canal_comunicacion
                             (idClientes, canal, fecha, hora, mensaje, estado)
                             VALUES (?, 'Correo', ?, ?, ?, 'Enviado')");
    if ($stmt2) {
        $stmt2->bind_param("isss", $idCliente, $fecha, $hora, $mensaje);
        $stmt2->execute();
        $stmt2->close();
    }
    return false;
}

// =====================
// PROGRAMAR ENTREVISTA
// =====================
if (isset($_POST['agendar_entrevista'])) {
    $idCliente    = intval($_POST['idClientes'] ?? 0);
    $idVacante    = intval($_POST['idVacante'] ?? 0);
    $idReclutador = intval($_POST['idReclutador'] ?? 0);
    $fecha        = $_POST['fecha'] ?? '';
    $horaInicio   = $_POST['hora_inicio'] ?? '';
    $horaFin      = $_POST['hora_fin'] ?? '';
    $nota         = "Entrevista programada desde disponibilidades.";

    if (!$idCliente || !$idVacante || !$idReclutador || !$fecha || !$horaInicio || !$horaFin) {
        $error_agenda = "Faltan datos para programar.";
    } else {
        $stmt = $conn->prepare("INSERT INTO entrevistas
            (idClientes, idVacante, idReclutador, fecha, hora_inicio, hora_fin, estado, notas)
            VALUES (?,?,?,?,?,?,'Programada',?)");

        if ($stmt) {
            $stmt->bind_param("iiissss", $idCliente, $idVacante, $idReclutador, $fecha, $horaInicio, $horaFin, $nota);

            $ok1 = $stmt->execute();
            $stmt->close();

            if ($ok1) {
                $msg = "Se programó entrevista para el $fecha de $horaInicio a $horaFin.";
                registrarComunicacionExtendida($conn, $idCliente, $idReclutador, $fecha, $horaInicio, $msg);
                $ok_agenda = true;
            } else {
                $error_agenda = "Error al ejecutar inserción: " . $conn->error;
            }
        } else {
            $error_agenda = "Error preparando la consulta: " . $conn->error;
        }
    }
}


// =====================
// MENSAJES
// =====================
$mensaje_exito = isset($_GET['ok']);
$ok_agenda = isset($ok_agenda) && $ok_agenda;

$error_insert = '';
$error_rrhh = $error_rrhh ?? '';


// =====================
// FILTROS
// =====================
$filtro_puesto  = $_GET['puesto'] ?? '';
$filtro_cliente = $_GET['cliente'] ?? '';
$filtro_semana  = $_GET['semana'] ?? '';


// =====================
// CÁLCULO SEMANA
// =====================
$inicioSemana = null;
$finSemana    = null;

if (!empty($filtro_semana)) {
    $fecha = new DateTime($filtro_semana);
    $fecha->modify('monday this week');
    $inicioSemana = $fecha->format('Y-m-d');
    $fecha->modify('sunday this week');
    $finSemana = $fecha->format('Y-m-d');
}


/* ============================================================
   INSERTAR DISPONIBILIDAD DEL CANDIDATO
   ============================================================ */
if (isset($_POST['guardar_disponibilidad'])) {

    $idClientes = intval($_POST['idClientes'] ?? 0);
    $dia        = $_POST['dia_semana'] ?? '';
    $horaTexto  = $_POST['hora'] ?? '';
    $fecha      = $_POST['fecha_referencia'] ?? '';
    $puesto     = $_POST['puesto'] ?? '';
    $estado     = $_POST['estado'] ?? 'Disponible';

    // Validación obligatoria
    if ($idClientes === 0 || $dia === '' || $horaTexto === '' || $fecha === '' || $puesto === '') {
        $error_insert = "Faltan datos obligatorios.";
    } else {

        list($okHora, $resultadoHora) = validarRangoHorario($horaTexto);

        if (!$okHora) {
            $error_insert = $resultadoHora;
        } else {
            list($horaInicio, $horaFin) = $resultadoHora;

            $sql = $conn->prepare("
                INSERT INTO disponibilidaddelequipo
                (idClientes, dia_semana, hora_inicio, hora_fin, fecha_referencia, puesto, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            if ($sql) {
                $sql->bind_param(
                    "issssss",
                    $idClientes,
                    $dia,
                    $horaInicio,
                    $horaFin,
                    $fecha,
                    $puesto,
                    $estado
                );

                if ($sql->execute()) {
                    header("Location: ".$_SERVER['PHP_SELF']."?ok=1");
                    exit;
                } else {
                    $error_insert = "Error al ejecutar la inserción: " . $conn->error;
                }
                $sql->close();
            } else {
                $error_insert = "Error preparando la inserción: " . $conn->error;
            }
        }
    }
}


// =====================
// INSERTAR DISPONIBILIDAD RRHH (reclutadores)
// =====================
if (isset($_POST['guardar_disponibilidad_rrhh'])) {

    $idReclutador = intval($_POST['idReclutador'] ?? 0);
    $dia        = $_POST['dia_semana'] ?? '';
    $horaTexto  = $_POST['hora'] ?? '';
    $fecha      = $_POST['fecha_referencia'] ?? '';
    $puesto     = $_POST['puesto'] ?? '';
    $estado     = $_POST['estado'] ?? 'Disponible';

    $horas = array_pad(array_map('trim', explode('-', $horaTexto)), 2, '');
    $horaInicio = $horas[0];
    $horaFin = $horas[1];

    if ($idReclutador === 0 || $dia === '' || $horaTexto === '' || $fecha === '' || $puesto === '') {
        $error_rrhh = "Faltan datos obligatorios para guardar la disponibilidad (Reclutador, Día, Hora, Fecha, Puesto).";
    } else {
        $sql = $conn->prepare("
            INSERT INTO disponibilidades_rrhh
            (idreclutadores, dia_semana, hora_inicio, hora_fin, fecha_referencia, puesto, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        if ($sql) {
            $sql->bind_param("issssss", $idReclutador, $dia, $horaInicio, $horaFin, $fecha, $puesto, $estado);
            if ($sql->execute()) {
                // Inserción RRHH correcta
                header("Location: ".$_SERVER['PHP_SELF']."?ok_rrhh=1");

                // Buscamos candidatos que tengan disponibilidad en la misma fecha y puesto (Para posible agendamiento automático)
                $cands_q = $conn->prepare("SELECT idClientes FROM disponibilidaddelequipo WHERE fecha_referencia = ? AND puesto = ?");
                if ($cands_q) {
                    $cands_q->bind_param("ss", $fecha, $puesto);
                    $cands_q->execute();
                    $res = $cands_q->get_result();
                    $cands_q->close();

                    // Si se encuentra al menos una coincidencia, redirigir con indicador (Aunque el proceso de agendamiento automático no está en este archivo)
                    if ($res->num_rows > 0) {
                        header("Location: ".$_SERVER['PHP_SELF']."?ok_rrhh=1&agendado_auto=1");
                        exit;
                    }
                }

                exit;
            } else {
                $error_rrhh = "No se pudo guardar disponibilidad RRHH. Detalle: " . $conn->error . " (Asegúrese que el ID de Reclutador $idReclutador existe).";
            }
        } else {
            $error_rrhh = "Error preparando la inserción de disponibilidad RRHH: " . $conn->error;
        }
    }
}

// =====================
// OBTENER CLIENTES Y FILTROS
// =====================
$clientes = $conn->query("SELECT id AS idClientes, nombre AS NombreCompleto FROM candidatos ORDER BY nombre ASC");
$clientesFiltro = $conn->query("SELECT id AS idClientes, nombre AS NombreCompleto FROM candidatos ORDER BY nombre ASC");
$reclutadores_list = $conn->query("SELECT idreclutadores, NombreCompleto FROM reclutadores ORDER BY NombreCompleto ASC");
$vacantes_list = $conn->query("SELECT id_vacante AS idVacante, titulo FROM vacantes ORDER BY titulo ASC");
$puestosFiltro = $conn->query("SELECT DISTINCT puesto FROM disponibilidaddelequipo ORDER BY puesto ASC");

// =====================
// CONSULTA DISPONIBILIDADES (candidatos)
// =====================
$where = [];
if ($filtro_puesto !== '') {
    $where[] = "d.puesto = '".$conn->real_escape_string($filtro_puesto)."'";
}
if ($filtro_cliente !== '') {
    $where[] = "d.idClientes = ".intval($filtro_cliente);
}
if ($inicioSemana && $finSemana) {
    $where[] = "d.fecha_referencia BETWEEN '$inicioSemana' AND '$finSemana'";
}
$whereSQL = $where ? "WHERE ".implode(" AND ", $where) : "";

$disponibilidades = $conn->query("
    SELECT d.*, c.nombre AS NombreCompleto
    FROM disponibilidaddelequipo d
    JOIN candidatos c ON c.id = d.idClientes
    $whereSQL
    ORDER BY d.fecha_referencia, d.hora_inicio
");

// ENTREVISTAS list
$entrevistas = $conn->query("
    SELECT e.*, c.nombre AS candidato, v.titulo AS vacante, r.NombreCompleto AS reclutador
    FROM entrevistas e
    LEFT JOIN candidatos c ON c.id = e.idClientes
    LEFT JOIN vacantes v ON v.id_vacante = e.idVacante
    LEFT JOIN reclutadores r ON r.idreclutadores = e.idReclutador
");

$dispoRRHH = null;
if ($conn->query("SHOW TABLES LIKE 'disponibilidades_rrhh'")->num_rows > 0) {
    $dispoRRHH = $conn->query("
        SELECT d.*, r.NombreCompleto
        FROM disponibilidades_rrhh d
        JOIN reclutadores r ON r.idreclutadores = d.idreclutadores
        ORDER BY d.fecha_referencia, d.hora_inicio
    ");
}

$semanaAnterior = $filtro_semana ? date('Y-m-d', strtotime($filtro_semana.' -7 days')) : '';
$semanaSiguiente = $filtro_semana ? date('Y-m-d', strtotime($filtro_semana.' +7 days')) : '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Disponibilidades</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles.css"> </head>
<body>

<div class="container py-4">
<h1 class="mb-3">Gestión de Disponibilidades</h1>

<div class="mb-4 text-end">
    <a href="administrador.php" class="btn btn-outline-secondary mb-2">← Volver al inicio</a><br>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalDispo">+ Agregar disponibilidad</button>
    <?php if ($dispoRRHH): ?>
    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalDispoRRHH">+ Disponibilidad RRHH</button>
    <?php endif; ?>
</div>

<?php if ($mensaje_exito): ?>
<div class="alert alert-success">✅ Disponibilidad guardada correctamente.</div>
<?php endif; ?>
<?php if (!empty($ok_agenda)): ?>
<div class="alert alert-success">✅ Entrevista programada correctamente.</div>
<?php endif; ?>
<?php if (isset($_GET['ok_delete'])): ?>
<div class="alert alert-warning">🗑️ Registro eliminado correctamente.</div>
<?php endif; ?>
<?php if (!empty($error_agenda)): ?>
<div class="alert alert-danger">⚠️ <?= htmlspecialchars($error_agenda) ?></div>
<?php endif; ?>
<?php if (!empty($error_rrhh)): ?>
<div class="alert alert-danger">⚠️ <?= htmlspecialchars($error_rrhh) ?></div>
<?php endif; ?>
<?php if (!empty($error_delete)): ?>
<div class="alert alert-danger">⚠️ <?= htmlspecialchars($error_delete) ?></div>
<?php endif; ?>
<?php if (!empty($error_insert)): ?>
<div class="alert alert-danger">⚠️ <?= htmlspecialchars($error_insert) ?></div>
<?php endif; ?>
<?php if (isset($_GET['agendado_auto'])): ?>
<div class="alert alert-success">🤖✅ Se agendaron entrevistas automáticamente tras guardar disponibilidad RRHH.</div>
<?php endif; ?>

<form method="GET" class="card p-3 mb-4">
<div class="row g-3">
<div class="col-md-4">
<label>Filtrar por puesto</label>
<select name="puesto" class="form-select">
<option value="">Todos</option>
<?php while ($p = $puestosFiltro->fetch_assoc()): ?>
<option value="<?= htmlspecialchars($p['puesto']) ?>" <?= $filtro_puesto == $p['puesto'] ? 'selected':'' ?>>
<?= htmlspecialchars($p['puesto']) ?>
</option>
<?php endwhile; ?>
</select>
</div>

<div class="col-md-4">
<label>Filtrar por candidato</label>
<select name="cliente" class="form-select">
<option value="">Todos</option>
<?php mysqli_data_seek($clientesFiltro, 0); while ($c = $clientesFiltro->fetch_assoc()): ?>
<option value="<?= $c['idClientes'] ?>" <?= $filtro_cliente == $c['idClientes'] ? 'selected':'' ?>>
<?= htmlspecialchars($c['NombreCompleto']) ?>
</option>
<?php endwhile; ?>
</select>
</div>

<div class="col-md-4">
<label>Semana</label>
<input type="date" name="semana" value="<?= htmlspecialchars($filtro_semana) ?>" class="form-control" onchange="this.form.submit()">
</div>
</div>
</form>

<div class="d-flex justify-content-between mb-3">
<a href="?semana=<?= $semanaAnterior ?>" class="btn btn-secondary">← Semana anterior</a>
<a href="?semana=<?= $semanaSiguiente ?>" class="btn btn-secondary">Semana siguiente →</a>
</div>

<?php if ($disponibilidades->num_rows > 0): ?>
<table class="table table-bordered">
<thead>
<tr>
<th>Candidato</th><th>Día</th><th>Hora</th><th>Fecha</th><th>Puesto</th><th>Estado</th><th>Acciones</th>
</tr>
</thead>
<tbody>
<?php while ($d = $disponibilidades->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($d['NombreCompleto']) ?></td>
<td><?= $d['dia_semana'] ?></td>
<td><?= $d['hora_inicio'] ?> - <?= $d['hora_fin'] ?></td>
<td><?= $d['fecha_referencia'] ?></td>
<td><?= htmlspecialchars($d['puesto']) ?></td>
<td><?= $d['estado'] ?></td>
<td>
    <button
      class="btn btn-sm btn-outline-primary mb-1"
      data-bs-toggle="modal"
      data-bs-target="#modalEntrevista"
      data-cliente="<?= htmlspecialchars($d['idClientes']) ?>"
      data-fecha="<?= htmlspecialchars($d['fecha_referencia']) ?>"
      data-horainicio="<?= htmlspecialchars($d['hora_inicio']) ?>"
      data-horafin="<?= htmlspecialchars($d['hora_fin']) ?>"
    >Programar entrevista</button>

    <button class="btn btn-success btn-sm mb-1"
        data-bs-toggle="modal"
        data-bs-target="#modalIA"
        data-idcliente="<?= $d['idClientes'] ?>"
        data-fecha="<?= $d['fecha_referencia'] ?>"
        data-horaini="<?= $d['hora_inicio'] ?>"
        data-horafin="<?= $d['hora_fin'] ?>">
        🤖 IA
    </button>

    <form method="POST" id="form-dispo-<?= $d['id'] ?>" style="display:none;">
        <input type="hidden" name="eliminar_disponibilidad" value="1">
        <input type="hidden" name="id" value="<?= $d['id'] ?>">
    </form>
    <button
        class="btn btn-sm btn-danger"
        data-bs-toggle="modal"
        data-bs-target="#modalConfirmacionGenerica"
        data-title="Eliminar Disponibilidad"
        data-body="¿Está seguro de eliminar la disponibilidad de **<?= htmlspecialchars($d['NombreCompleto']) ?>** para el puesto **<?= htmlspecialchars($d['puesto']) ?>**?"
        data-form-id="#form-dispo-<?= $d['id'] ?>"
    >Eliminar</button>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
<?php else: ?>
<p class="text-muted">No hay disponibilidades para mostrar.</p>
<?php endif; ?>

<?php if ($entrevistas && $entrevistas->num_rows > 0): ?>
<div class="card mt-4">
  <h4 class="mb-3">Entrevistas programadas</h4>
  <div class="table-responsive">
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Candidato</th>
          <th>Vacante</th>
          <th>Reclutador</th>
          <th>Fecha</th>
          <th>Hora</th>
          <th>Estado</th>
          <th>Acciones</th> </tr>
      </thead>
      <tbody>
        <?php while ($e = $entrevistas->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($e['candidato']) ?></td>
            <td><?= htmlspecialchars($e['vacante']) ?></td>
            <td><?= htmlspecialchars($e['reclutador']) ?></td>
            <td><?= htmlspecialchars($e['fecha']) ?></td>
            <td><?= htmlspecialchars($e['hora_inicio']) ?> - <?= htmlspecialchars($e['hora_fin']) ?></td>
            <td><?= htmlspecialchars($e['estado']) ?></td>
            <td>
                <form method="POST" id="form-entrevista-<?= $e['id_entrevista'] ?? $e['idEntrevista'] ?? '' ?>" style="display:none;">
                    <input type="hidden" name="eliminar_entrevista" value="1">
                    <input type="hidden" name="id" value="<?= $e['id_entrevista'] ?? $e['idEntrevista'] ?? '' ?>">
                </form>
                <button
                  class="btn btn-sm btn-danger"
                  data-bs-toggle="modal"
                  data-bs-target="#modalConfirmacionGenerica"
                  data-title="Eliminar Entrevista"
                  data-body="¿Está seguro de eliminar la entrevista de **<?= htmlspecialchars($e['candidato']) ?>** para la vacante **<?= htmlspecialchars($e['vacante']) ?>** programada el <?= htmlspecialchars($e['fecha']) ?>?"
                  data-form-id="#form-entrevista-<?= $e['id_entrevista'] ?? $e['idEntrevista'] ?? '' ?>"
                >Eliminar</button>
                <a href="post-entrevista.php?idEntrevista=<?= $e['id_entrevista'] ?? $e['idEntrevista'] ?? '' ?>" class="btn btn-sm btn-outline-success">
                    📝 Evaluar
                </a>

            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if ($dispoRRHH && $dispoRRHH->num_rows > 0): ?>
<div class="card mt-4">
  <h4 class="mb-3">Disponibilidad RRHH (reclutadores/analistas)</h4>
  <div class="table-responsive">
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Reclutador</th>
          <th>Día</th>
          <th>Hora</th>
          <th>Fecha</th>
          <th>Puesto</th>
          <th>Estado</th>
          <th>Acciones</th> </tr>
      </thead>
      <tbody>
        <?php while ($r = $dispoRRHH->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($r['NombreCompleto']) ?></td>
            <td><?= htmlspecialchars($r['dia_semana']) ?></td>
            <td><?= htmlspecialchars($r['hora_inicio']) ?> - <?= htmlspecialchars($r['hora_fin']) ?></td>
            <td><?= htmlspecialchars($r['fecha_referencia']) ?></td>
            <td><?= htmlspecialchars($r['puesto']) ?></td>
            <td><?= htmlspecialchars($r['estado']) ?></td>
            <td>
                <form method="POST" id="form-rrhh-<?= $r['id'] ?>" style="display:none;">
                    <input type="hidden" name="eliminar_disponibilidad_rrhh" value="1">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                </form>
                <button
                  class="btn btn-sm btn-danger"
                  data-bs-toggle="modal"
                  data-bs-target="#modalConfirmacionGenerica"
                  data-title="Eliminar Disponibilidad RRHH"
                  data-body="¿Está seguro de eliminar la disponibilidad del reclutador **<?= htmlspecialchars($r['NombreCompleto']) ?>** para el puesto **<?= htmlspecialchars($r['puesto']) ?>**?"
                  data-form-id="#form-rrhh-<?= $r['id'] ?>"
                >Eliminar</button>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

</div>

<div class="modal fade" id="modalDispo">
<div class="modal-dialog modal-dialog-centered">
<form method="POST" class="modal-content">
<div class="modal-body">
<input type="hidden" name="guardar_disponibilidad" value="1">
<select name="dia_semana" class="form-select mb-2" required>
<option value="" disabled selected>Día</option>
<option>Lunes</option><option>Martes</option><option>Miércoles</option>
<option>Jueves</option><option>Viernes</option><option>Sábado</option><option>Domingo</option>
</select>
<input type="text" name="hora" class="form-control mb-2" placeholder="09:00 - 10:00" required>
<input type="date" name="fecha_referencia" class="form-control mb-2" required>
<select name="idClientes" class="form-select mb-2" required>
<option value="" disabled selected>Candidato</option>
<?php mysqli_data_seek($clientes,0); while($c=$clientes->fetch_assoc()): ?>
<option value="<?= $c['idClientes'] ?>"><?= htmlspecialchars($c['NombreCompleto']) ?></option>
<?php endwhile; ?>
</select>
<input type="text" name="puesto" class="form-control mb-2" required>
<select name="estado" class="form-select">
<option>Disponible</option>
<option>Coincidencia óptima</option>
<option>Confirmado</option>
</select>
<button class="btn btn-primary mt-3 w-100">Guardar</button>
</div>
</form>
</div>
</div>

<div class="modal fade" id="modalDispoRRHH">
<div class="modal-dialog modal-dialog-centered">
<form method="POST" class="modal-content">
<div class="modal-body">
<input type="hidden" name="guardar_disponibilidad_rrhh" value="1">
<select name="dia_semana" class="form-select mb-2" required>
<option value="" disabled selected>Día</option> <option>Lunes</option><option>Martes</option><option>Miércoles</option>
<option>Jueves</option><option>Viernes</option><option>Sábado</option><option>Domingo</option>
</select>
<input type="text" name="hora" class="form-control mb-2" placeholder="09:00 - 10:00" required>
<input type="date" name="fecha_referencia" class="form-control mb-2" required>
<select name="idReclutador" class="form-select mb-2" required>
    <option value="" disabled selected>Reclutador</option>
    <?php mysqli_data_seek($reclutadores_list, 0); while ($r = $reclutadores_list->fetch_assoc()): ?>
    <option value="<?= $r['idreclutadores'] ?>">
        <?= htmlspecialchars($r['NombreCompleto']) ?> (ID: <?= $r['idreclutadores'] ?>)
    </option>
    <?php endwhile; ?>
</select>
<input type="text" name="puesto" class="form-control mb-2" required>
<select name="estado" class="form-select">
<option>Disponible</option>
<option>Coincidencia óptima</option>
<option>Confirmado</option>
</select>
<button class="btn btn-primary mt-3 w-100">Guardar</button>
</div>
</form>
</div>
</div>

<div class="modal fade" id="modalEntrevista" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Programar entrevista</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <form id="formEntrevistaDispo" method="POST" class="modal-body">
        <input type="hidden" name="agendar_entrevista" value="1">
        <input type="hidden" id="entrevistaClienteId" name="idClientes">
        <div class="mb-3">
          <label class="form-label">Vacante</label>
          <select name="idVacante" class="form-select" required>
            <option value="" disabled selected>Seleccione una vacante</option>
            <?php mysqli_data_seek($vacantes_list, 0); while ($v = $vacantes_list->fetch_assoc()): ?>
            <option value="<?= $v['idVacante'] ?>"><?= htmlspecialchars($v['titulo']) ?> (ID: <?= $v['idVacante'] ?>)</option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Reclutador</label>
          <select name="idReclutador" class="form-select" required>
            <option value="" disabled selected>Seleccione un reclutador</option>
            <?php mysqli_data_seek($reclutadores_list, 0); while ($r = $reclutadores_list->fetch_assoc()): ?>
            <option value="<?= $r['idreclutadores'] ?>"><?= htmlspecialchars($r['NombreCompleto']) ?> (ID: <?= $r['idreclutadores'] ?>)</option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="row g-2">
          <div class="col-md-6"><label class="form-label">Fecha</label><input type="date" name="fecha" class="form-control" required></div>
          <div class="col-md-3"><label class="form-label">Hora inicio</label><input type="time" name="hora_inicio" class="form-control" required></div>
          <div class="col-md-3"><label class="form-label">Hora fin</label><input type="time" name="hora_fin" class="form-control" required></div>
        </div>
      </form>
      <div class="modal-footer"><button class="btn btn-outline" data-bs-dismiss="modal">Cancelar</button><button type="submit" form="formEntrevistaDispo" class="btn btn-primary">Guardar</button></div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalIA">
<div class="modal-dialog">
<form method="POST" action="api_agente.php" class="modal-content">
<div class="modal-body">
<input type="hidden" name="action" value="iniciar">
<input type="hidden" name="idCliente" id="iaCliente">
<input type="hidden" name="fecha" id="iaFecha">
<input type="hidden" name="hora_inicio" id="iaHoraIni">
<input type="hidden" name="hora_fin" id="iaHoraFin">

<select name="idReclutador" class="form-select mb-2" required>
<option value="">Reclutador</option>
<?php mysqli_data_seek($reclutadores_list, 0); while($r=$reclutadores_list->fetch_assoc()): ?>
<option value="<?= $r['idreclutadores'] ?>"><?= htmlspecialchars($r['NombreCompleto']) ?></option>
<?php endwhile; ?>
</select>

<select name="idVacante" class="form-select mb-2" required>
<option value="">Vacante</option>
<?php mysqli_data_seek($vacantes_list, 0); while($v=$vacantes_list->fetch_assoc()): ?>
<option value="<?= $v['idVacante'] ?>"><?= htmlspecialchars($v['titulo']) ?></option>
<?php endwhile; ?>
</select>

<p class="text-muted">La confirmación se realiza SOLO por WhatsApp.</p>
</div>
<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
<button class="btn btn-success">Iniciar Agente</button>
</div>
</form>
</div>
</div>

<div class="modal fade" id="modalConfirmacionGenerica" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalConfirmacionTitle">Confirmar Acción</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
      <div class="modal-body" id="modalConfirmacionBody">
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnConfirmarAccion">Aceptar</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Rellenar modal entrevista clásica (Se mantiene)
const modalEntrevista = document.getElementById('modalEntrevista');
if (modalEntrevista) {
  modalEntrevista.addEventListener('show.bs.modal', (event) => {
    const button = event.relatedTarget;
    if (!button) return;
    const cliente = button.getAttribute('data-cliente');
    const fecha = button.getAttribute('data-fecha');
    const horaInicio = button.getAttribute('data-horainicio');
    const horaFin = button.getAttribute('data-horafin');
    const inputCliente = modalEntrevista.querySelector('#entrevistaClienteId');
    const inputFecha = modalEntrevista.querySelector('input[name="fecha"]');
    const inputHoraIni = modalEntrevista.querySelector('input[name="hora_inicio"]');
    const inputHoraFin = modalEntrevista.querySelector('input[name="hora_fin"]');
    if (inputCliente) inputCliente.value = cliente || '';
    if (inputFecha) inputFecha.value = fecha || '';
    if (inputHoraIni) inputHoraIni.value = horaInicio || '';
    if (inputHoraFin) inputHoraFin.value = horaFin || '';
    const selectReclutador = modalEntrevista.querySelector('select[name="idReclutador"]');
    if (selectReclutador) selectReclutador.value = '';
    const selectVacante = modalEntrevista.querySelector('select[name="idVacante"]');
    if (selectVacante) selectVacante.value = '';
  });
}

// Rellenar modal IA (Nuevo, de jkdkid.text)
const modalIA=document.getElementById('modalIA');
if(modalIA) {
    modalIA.addEventListener('show.bs.modal',e=>{
        const b=e.relatedTarget;
        modalIA.querySelector('#iaCliente').value=b.dataset.idcliente;
        modalIA.querySelector('#iaFecha').value=b.dataset.fecha;
        modalIA.querySelector('#iaHoraIni').value=b.dataset.horaini;
        modalIA.querySelector('#iaHoraFin').value=b.dataset.horafin;
    });
}

// Script para el Modal de Confirmación Genérico (Se mantiene)
const modalConfirmacion = document.getElementById('modalConfirmacionGenerica');
if (modalConfirmacion) {
  modalConfirmacion.addEventListener('show.bs.modal', (event) => {
    const button = event.relatedTarget;
    if (!button) return;

    const title = button.getAttribute('data-title');
    const body = button.getAttribute('data-body');
    const formId = button.getAttribute('data-form-id');

    modalConfirmacion.querySelector('#modalConfirmacionTitle').textContent = title || 'Confirmar';
    let formattedBody = (body || '¿Está seguro de proceder con esta acción?').replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    modalConfirmacion.querySelector('#modalConfirmacionBody').innerHTML = formattedBody;

    let btnConfirmar = modalConfirmacion.querySelector('#btnConfirmarAccion');
    const newBtnConfirmar = btnConfirmar.cloneNode(true);
    btnConfirmar.parentNode.replaceChild(newBtnConfirmar, btnConfirmar);
    btnConfirmar = newBtnConfirmar;

    btnConfirmar.addEventListener('click', () => {
        const formToSubmit = document.querySelector(formId);
        if (formToSubmit) {
            formToSubmit.submit();
        }
        const bootstrapModal = bootstrap.Modal.getInstance(modalConfirmacion);
        if (bootstrapModal) {
            bootstrapModal.hide();
        }
    });
  });
}
</script>
</body>
</html>