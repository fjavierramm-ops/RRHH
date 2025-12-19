<?php
require_once("config.php");
$conn = connection();


if (isset($_POST['eliminar_vacante'])) {
    $id = intval($_POST['id']);
    $sql = "DELETE FROM vacantes WHERE id_vacante = $id";
    if ($conn->query($sql) === TRUE) {
        echo "ok";
    } else {
        echo "error";
    }
    exit;
}

// === AGENDAR ENTREVISTA (USA TABLA 'entrevistas' Y 'canal_comunicacion') ===
if (isset($_POST['agendar_entrevista'])) {
    // Los IDs ahora vienen de los campos SELECT en el frontend
    $idVacante    = intval($_POST['idVacante'] ?? 0);
    $idCliente    = intval($_POST['idClientes'] ?? 0);
    $idReclutador = intval($_POST['idReclutador'] ?? 0);
    $fecha        = $_POST['fecha'] ?? '';
    $horaInicio   = $_POST['hora_inicio'] ?? '';
    $horaFin      = $_POST['hora_fin'] ?? '';
    $nota         = "Entrevista programada desde el panel.";

    // Esta validación también ayuda a evitar errores de FK si los selects no se eligieron (ID = 0)
    if (!$idVacante || !$idCliente || !$idReclutador || !$fecha || !$horaInicio || !$horaFin) {
        echo "Faltan datos";
        exit;
    }

    // Buscar si existe una aplicación para este candidato y vacante
    $sql_app = "SELECT id_aplicacion FROM aplicaciones WHERE id_candidato = ? AND id_vacante = ? LIMIT 1";
    $stmt_app = $conn->prepare($sql_app);
    $id_aplicacion = null;
    
    if ($stmt_app) {
        $stmt_app->bind_param("ii", $idCliente, $idVacante);
        $stmt_app->execute();
        $result_app = $stmt_app->get_result();
        
        if ($result_app && $result_app->num_rows > 0) {
            $row_app = $result_app->fetch_assoc();
            $id_aplicacion = $row_app['id_aplicacion'];
        } else {
            // Si no existe aplicación, crear una nueva
            $sql_create_app = "INSERT INTO aplicaciones (id_candidato, id_vacante, fecha_aplicacion, status_aplicacion) VALUES (?, ?, NOW(), 'En proceso')";
            $stmt_create = $conn->prepare($sql_create_app);
            if ($stmt_create) {
                $stmt_create->bind_param("ii", $idCliente, $idVacante);
                if ($stmt_create->execute()) {
                    $id_aplicacion = $conn->insert_id;
                }
                $stmt_create->close();
            }
        }
        $stmt_app->close();
    }

    // Si no se pudo obtener o crear id_aplicacion, usar NULL (si la columna lo permite)
    // Nota: Si id_aplicacion es NOT NULL, esto fallará y necesitarás crear la aplicación primero
    if (!$id_aplicacion) {
        echo "Error: No se pudo obtener o crear la aplicación";
        exit;
    }

    // 1. Verificar si ya existe una entrevista para esta aplicación
    // Si la tabla tiene restricción única en id_aplicacion, solo puede haber una entrevista por aplicación
    $sql_check_entrevista = "SELECT id_entrevista FROM entrevistas WHERE id_aplicacion = ? LIMIT 1";
    $stmt_check = $conn->prepare($sql_check_entrevista);
    $id_entrevista_creada = null;
    
    if ($stmt_check) {
        $stmt_check->bind_param("i", $id_aplicacion);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check && $result_check->num_rows > 0) {
            // Ya existe una entrevista para esta aplicación, actualizar en lugar de insertar
            $row_check = $result_check->fetch_assoc();
            $id_entrevista_creada = $row_check['id_entrevista'];
            
            $sql_update = "UPDATE entrevistas 
                          SET idClientes = ?, idVacante = ?, idReclutador = ?, fecha = ?, hora_inicio = ?, hora_fin = ?, estado = 'Programada', notas = ? 
                          WHERE id_entrevista = ?";
            $stmt_update = $conn->prepare($sql_update);
            if ($stmt_update) {
                $stmt_update->bind_param("iiissssi", $idCliente, $idVacante, $idReclutador, $fecha, $horaInicio, $horaFin, $nota, $id_entrevista_creada);
                $ok1 = $stmt_update->execute();
                if (!$ok1) {
                    echo "Error actualizando entrevista: " . $stmt_update->error;
                    $stmt_update->close();
                    $stmt_check->close();
                    exit;
                }
                $stmt_update->close();
            } else {
                echo "Error preparando UPDATE: " . $conn->error;
                $stmt_check->close();
                exit;
            }
        } else {
            // No existe, insertar nueva entrevista usando INSERT ... ON DUPLICATE KEY UPDATE
            $stmt = $conn->prepare("INSERT INTO entrevistas (id_aplicacion, idClientes, idVacante, idReclutador, fecha, hora_inicio, hora_fin, estado, notas) 
                                   VALUES (?,?,?,?,?,?,?, 'Programada', ?)
                                   ON DUPLICATE KEY UPDATE 
                                   idClientes = VALUES(idClientes),
                                   idVacante = VALUES(idVacante),
                                   idReclutador = VALUES(idReclutador),
                                   fecha = VALUES(fecha),
                                   hora_inicio = VALUES(hora_inicio),
                                   hora_fin = VALUES(hora_fin),
                                   estado = 'Programada',
                                   notas = VALUES(notas)");
            if (!$stmt) {
                echo "Error preparando SQL: " . $conn->error;
                $stmt_check->close();
                exit;
            }
            $stmt->bind_param("iiiiisss", $id_aplicacion, $idCliente, $idVacante, $idReclutador, $fecha, $horaInicio, $horaFin, $nota);
            $ok1 = $stmt->execute();
            if (!$ok1) {
                echo "Error ejecutando SQL: " . $stmt->error;
                $stmt->close();
                $stmt_check->close();
                exit;
            }
            $id_entrevista_creada = $conn->insert_id;
            // Si el INSERT fue un UPDATE (duplicate key), obtener el ID de otra forma
            if ($id_entrevista_creada == 0) {
                $sql_get_id = "SELECT id_entrevista FROM entrevistas WHERE id_aplicacion = ? LIMIT 1";
                $stmt_get = $conn->prepare($sql_get_id);
                $stmt_get->bind_param("i", $id_aplicacion);
                $stmt_get->execute();
                $result_get = $stmt_get->get_result();
                if ($result_get && $result_get->num_rows > 0) {
                    $row_get = $result_get->fetch_assoc();
                    $id_entrevista_creada = $row_get['id_entrevista'];
                }
                $stmt_get->close();
            }
            $stmt->close();
        }
        $stmt_check->close();
    } else {
        echo "Error preparando verificación: " . $conn->error;
        exit;
    }

    // 2. Registrar comunicación (CORRECCIÓN: Se usa un solo INSERT completo)
    $msg = "Se programó entrevista para el $fecha de $horaInicio a $horaFin.";
    // CORRECCIÓN CLAVE: Se añade idClientes a la lista de columnas y al bind_param
    // para satisfacer la restricción NOT NULL/FOREIGN KEY.
    $stmt2 = $conn->prepare("INSERT INTO canal_comunicacion 
                             (idClientes, tipo_origen, id_origen, tipo_destino, id_destino, canal, fecha, hora, mensaje, estado, automatica) 
                             VALUES (?, 'reclutador', ?, 'cliente', ?, 'Correo', ?, ?, ?, 'Enviado', 1)");
    
    if ($stmt2) {
        // Parámetros: idClientes, id_origen (idReclutador), id_destino (idCliente), fecha, hora, mensaje
        // Tipos: i, i, i, s, s, s  -> "iiisss"
        $stmt2->bind_param("iiisss", $idCliente, $idReclutador, $idCliente, $fecha, $horaInicio, $msg);
        $ok2 = $stmt2->execute();
        $stmt2->close();
    } else {
        // En caso de que la preparación falle, no afecta el resultado final, 
        // pero se mantiene la bandera del éxito de la entrevista ($ok1).
    }

    // 3. Llamar al agente de seguimiento post-entrevista (según guía de integración)
    if ($ok1 && $id_entrevista_creada) {
        require_once 'agente_orquestador.php';
        $orquestador = new AgenteOrquestador($conn);
        $orquestador->ejecutarAgente('seguimiento_post_entrevista', [
            'id_entrevista' => $id_entrevista_creada
        ]);
    }

    // Se responde con el resultado de la inserción de la entrevista.
    echo ($ok1) ? "ok" : "error";
    exit;
}


if (isset($_POST['eliminar_usuario'])) {

    $email = strtolower(trim($_POST['email']));

    // ← Aquí están tus DELETE corregidos
    $conn->query("DELETE FROM candidatos WHERE REPLACE(LOWER(TRIM(email)), ' ', '') = REPLACE('$email',' ','')");
    $delClientes = $conn->affected_rows;

    $conn->query("DELETE FROM reclutadores
                  WHERE REPLACE(LOWER(TRIM(email)), ' ', '') = REPLACE('$email',' ','')");
    $delReclutadores = $conn->affected_rows;

    echo ($delClientes > 0 || $delReclutadores > 0) ? "ok" : "no_existe";
    exit;
}



// === GUARDAR USUARIO ===
// === GUARDAR O EDITAR USUARIO ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $rol = $_POST['rol'];
    $estado = $_POST['estado'];
    $telefono = $_POST['telefono'] ?? null;
    $password = $_POST['password'] ?? null;

    $editando = $_POST['editando'] ?? "0";
    $email_original = $_POST['email_original'] ?? "";

    // ----------------------------------------------------
    // MODO EDITAR → HACER UPDATE
    // ----------------------------------------------------
    if ($editando === "1") {

        $pass_update = '';
        if ($rol !== "Candidato" && !empty($password)) {
            // Asumo que si hay password en la edición, es porque se quiere cambiar.
            // NOTA: Se recomienda ENCRIPTAR la contraseña antes de guardarla.
            $pass_update = ", password='$password'";
        }
        
        if ($rol === "Candidato") {
            $sql = "UPDATE candidatos SET nombre='$nombre', email='$email', telefono='$telefono' WHERE email='$email_original'";
        } else {
            $sql = "UPDATE reclutadores
                    SET NombreCompleto='$nombre', email='$email'".$pass_update.",
                        roles='$rol', estados='$estado'
                    WHERE email='$email_original'";
        }

        echo ($conn->query($sql) === TRUE) ? "edit_ok" : "edit_error";
        exit;
    }

    // ----------------------------------------------------
    // MODO CREAR → HACER INSERT
    // ----------------------------------------------------
    if ($rol === "Candidato") {
        $sql = "INSERT INTO candidatos (nombre, email, telefono) VALUES ('$nombre', '$email', '$telefono')";
    } else {
        // NOTA: Se recomienda ENCRIPTAR la contraseña antes de guardarla.
        $sql = "INSERT INTO reclutadores (NombreCompleto, email, password, roles, estados)
                VALUES ('$nombre', '$email', '$password', '$rol', '$estado')";
    }

    echo ($conn->query($sql) === TRUE) ? "ok" : "error";
    exit;
}


// === CONTAR USUARIOS ACTIVOS ===
if (isset($_GET['contar_usuarios'])) {
    $sql1 = "SELECT COUNT(*) AS total FROM candidatos";
    $sql2 = "SELECT COUNT(*) AS total FROM reclutadores WHERE estados='Activo'";

    $r1 = $conn->query($sql1)->fetch_assoc()['total'];
    $r2 = $conn->query($sql2)->fetch_assoc()['total'];

    echo $r1 + $r2;
    exit;
}

// === OBTENER LISTA DE USUARIOS (General) ===
if (isset($_GET['listar_usuarios'])) {

    $data = [];

    // Clientes
    $sql1 = "SELECT nombre AS NombreCompleto, email, 'Candidato' AS roles, 'Activo' AS estados FROM candidatos";
    $result1 = $conn->query($sql1);
    while ($row = $result1->fetch_assoc()) {
        $data[] = $row;
    }

    // Reclutadores
    $sql2 = "SELECT NombreCompleto, email, roles, estados FROM reclutadores";
    $result2 = $conn->query($sql2);
    while ($row = $result2->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode($data);
    exit;
}


// =====================================================
// === NUEVOS ENDPOINTS PARA SELECTS EN AGENDAR ENTREVISTA ===
// =====================================================

// === OBTENER LISTA DE CLIENTES ACTIVOS (CANDIDATOS) ===
if (isset($_GET['listar_clientes_activos'])) {
    $data = [];
    // Nota: Usamos 'estados'='Activo' para filtrar
    $sql = "SELECT id AS idClientes, nombre AS NombreCompleto FROM candidatos ORDER BY nombre";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        // Asegurarse de usar la columna idClientes
        $data[] = ['id' => $row['idClientes'], 'nombre' => $row['NombreCompleto']];
    }
    echo json_encode($data);
    exit;
}

// === OBTENER LISTA DE RECLUTADORES ACTIVOS ===
if (isset($_GET['listar_reclutadores_activos'])) {
    $data = [];
    // Nota: Usamos 'estados'='Activo' para filtrar
    $sql = "SELECT idreclutadores, NombreCompleto FROM reclutadores WHERE estados='Activo' ORDER BY NombreCompleto";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        // Asegurarse de usar la columna idreclutadores
        $data[] = ['id' => $row['idreclutadores'], 'nombre' => $row['NombreCompleto']];
    }
    echo json_encode($data);
    exit;
}


// =====================================================
// === LÓGICA VACANTES (MySQL) ===
// =====================================================

// 1. Eliminar Vacante - Ya está al inicio del archivo

// 2. Listar Vacantes
if (isset($_GET['listar_vacantes'])) {
    $data = [];
    $sql = "SELECT id_vacante AS idVacante, titulo, COALESCE(departamento, empresa) AS departamento, COALESCE(tipo, tipo_trabajo) AS tipo, estado FROM vacantes ORDER BY id_vacante DESC";
    $result = $conn->query($sql);
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
    exit;
}

// 3. Contar Vacantes Activas
if (isset($_GET['contar_vacantes'])) {
    $sql = "SELECT COUNT(*) AS total FROM vacantes WHERE estado='Activa'";
    $row = $conn->query($sql)->fetch_assoc();
    echo $row['total'];
    exit;
}










// === DISPONIBILIDAD DEL EQUIPO (BD REAL) ===

// Obtener filtros (si existen)
$filtro_puesto = $_GET['puesto'] ?? '';
$filtro_cliente = $_GET['cliente'] ?? '';
$filtro_semana = $_GET['semana'] ?? '';

// Función para formatear la fecha con el mes en texto
function formatearFecha($fecha) {
    setlocale(LC_TIME, 'es_ES.UTF-8');
    $date = new DateTime($fecha);
    $mes = strftime('%B', $date->getTimestamp());
    return $date->format('j') . ' ' . ucfirst($mes); // Simplificado un poco
}

// Consulta base
$sqlDisponibilidades = "
    SELECT d.*, c.nombre AS NombreCompleto
    FROM disponibilidaddelequipo d
    JOIN candidatos c ON c.id = d.idClientes
    WHERE 1=1
";

// Filtros
if (!empty($filtro_puesto)) {
    $sqlDisponibilidades .= " AND d.puesto = '".$conn->real_escape_string($filtro_puesto)."'";
}

if (!empty($filtro_cliente)) {
    $sqlDisponibilidades .= " AND d.idClientes = ".intval($filtro_cliente);
}

if (!empty($filtro_semana)) {
    $fecha = new DateTime($filtro_semana);
    $fecha->modify('monday this week');
    $inicioSemana = $fecha->format('Y-m-d');
    $fecha->modify('sunday this week');
    $finSemana = $fecha->format('Y-m-d');

    $sqlDisponibilidades .= " AND d.fecha_referencia BETWEEN '$inicioSemana' AND '$finSemana'";
}

// Ejecutar consulta
$disponibilidades = $conn->query($sqlDisponibilidades);

function diaSemanaANumero($diaTexto) {
    $mapa = [
        'lunes' => 1,
        'martes' => 2,
        'miércoles' => 3,
        'miercoles' => 3,
        'jueves' => 4,
        'viernes' => 5,
        'sábado' => 6,
        'sabado' => 6,
        'domingo' => 7
    ];

    $diaTexto = strtolower(trim($diaTexto));

    return $mapa[$diaTexto] ?? '';
}

?>





<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Panel del Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="styles.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>


    <div class="container">
        <a href="index.php" class="btn btn-secondary mb-3">Volver al inicio</a>

        <h1 class="section-title">Panel del Administrador</h1>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <h3>Usuarios activos</h3>
                    <h2 id="usuariosActivos">0</h2>
                    <p class="text-muted">Actualizado</p>
                </div>
                </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <h3>Vacantes abiertas</h3>
                    <h2 id="vacantesActivas">0</h2>
                    <p class="text-muted">Actualizado</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <h3>Postulaciones</h3>
                    <h2>452</h2>
                    <p class="text-muted">+40 este mes</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <h3>Procesos completados</h3>
                    <h2>289</h2>
                    <p class="text-muted">+15 desde el último mes</p>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Gestión de Usuarios</h2>
                <button id="btnNuevoUsuario" class="btn btn-primary">
                    + Nuevo Usuario
                </button>
            </div>
            <div class="table-responsive">
                <table id="tablaUsuarios" class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Gestión de Vacantes</h2>
                <button id="btnCrearVacante" class="btn btn-primary">
                    + Crear Vacante
                </button>
            </div>
            <div class="table-responsive">
                <table id="tablaVacantes" class="table table-striped">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Departamento</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Disponibilidad del Equipo</h2>
                <a href="disponibilidades.php" class="btn btn-outline">
                    Ver Calendario
                </a>
            </div>
            <div class="table-responsive">
                <table id="tablaDisponibilidades" class="table table-striped">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Semana</th>
                            <th>Días disponibles</th>
                        </tr>
                    </thead>
                    <tbody>
<tbody>
<?php if ($disponibilidades && $disponibilidades->num_rows > 0): ?>
    <?php while ($d = $disponibilidades->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($d['NombreCompleto']) ?></td>
            <td><?= formatearFecha($d['fecha_referencia']) ?></td>
            <td>
    <?= htmlspecialchars($d['dia_semana']) ?>
    (<?= diaSemanaANumero($d['dia_semana']) ?>)
    <span class="status enviado">
        <?= htmlspecialchars($d['estado']) ?>
    </span>
</td>

        </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr>
        <td colspan="3" class="text-center text-muted">
            No hay disponibilidad registrada
        </td>
    </tr>
<?php endif; ?>
</tbody>






                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Logs de Comunicación</h2>
                <a href="logs-comunicacion.php" class="btn btn-outline">
                    Ver todos los Logs
                </a>
            </div>
            <div class="table-responsive">
                <table id="tablaLogs" class="table table-striped">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Destino</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        </tbody>
                </table>
            </div>
        </div>
    </div>

    <div
        class="modal fade"
        id="modalUsuario"
        tabindex="-1"
        aria-labelledby="modalUsuarioLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalUsuarioLabel">
                        Registrar nuevo usuario
                    </h5>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre</label>
                            <input type="text" id="usuarioNombre" class="form-control" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Correo</label>
                            <input type="email" id="usuarioCorreo" class="form-control" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Rol</label>
                            <select id="usuarioRol" class="form-select">
    <option value="">Seleccione un rol...</option>
    <option value="Administrador">Administrador</option>
    <option value="Reclutadora">Reclutadora</option>
    <option value="Candidato">Candidato</option>
</select>

                        </div>
                        <div class="col-md-6" id="campoTelefono" style="display:none;">
    <label class="form-label">Teléfono</label>
    <input type="text" id="usuarioTelefono" class="form-control" />
</div>

<div class="col-md-6" id="campoPassword" style="display:none;">
    <label class="form-label">Password</label>
    <input type="password" id="usuarioPassword" class="form-control" />
</div>

                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <select id="usuarioEstado" class="form-select">
                                <option>Activo</option>
                                <option>Pendiente</option>
                                <option>Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button id="guardarUsuario" class="btn">Guardar</button>
                </div>
            </div>
            </div>
    </div>

    <div class="modal fade" id="modalEntrevista" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Programar entrevista</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formEntrevista" class="modal-body">
                    <input type="hidden" id="entrevistaVacanteId" />
                    <div class="mb-3">
                        <label class="form-label">Candidato (idClientes)</label>
                        <select class="form-select" id="entrevistaClienteId" required>
                            <option value="">Seleccione un Candidato...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reclutador (idreclutadores)</label>
                        <select class="form-select" id="entrevistaReclutadorId" required>
                            <option value="">Seleccione un Reclutador...</option>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Fecha</label>
                            <input type="date" class="form-control" id="entrevistaFecha" required />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Hora inicio</label>
                            <input type="time" class="form-control" id="entrevistaHoraInicio" required />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Hora fin</label>
                            <input type="time" class="form-control" id="entrevistaHoraFin" required />
                        </div>
                    </div>
                </form>
                <div class="modal-footer">
                    <button class="btn btn-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button form="formEntrevista" class="btn">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
// === ADMINISTRADOR.JS (VERSIÓN FINAL ACTUALIZADA) ===

// Esperamos a que el DOM esté cargado
document.getElementById("usuarioRol").addEventListener("change", function() {
    const rol = this.value;

    document.getElementById("campoTelefono").style.display =
      rol === "Candidato" ? "block" : "none";

    document.getElementById("campoPassword").style.display =
      (rol === "Administrador" || rol === "Reclutadora") ? "block" : "none";
});


window.addEventListener("DOMContentLoaded", () => {
    // --- Elementos base ---
    const modal = new bootstrap.Modal(document.getElementById("modalUsuario"));
    const btnNuevoUsuario = document.getElementById("btnNuevoUsuario");
    const tablaUsuarios = document.getElementById("tablaUsuarios").querySelector("tbody");
    const tablaVacantes = document.getElementById("tablaVacantes").querySelector("tbody");
    const btnGuardarUsuario = document.getElementById("guardarUsuario");
    const btnCrearVacante = document.getElementById("btnCrearVacante");
    const modalEntrevista = document.getElementById("modalEntrevista");
    const formEntrevista = document.getElementById("formEntrevista");
    const inputVacanteId = document.getElementById("entrevistaVacanteId");

    // MODIFICADO: inputClienteId e inputReclutadorId ahora son elementos SELECT
    const inputClienteId = document.getElementById("entrevistaClienteId");
    const inputReclutadorId = document.getElementById("entrevistaReclutadorId");
    
    const inputFecha = document.getElementById("entrevistaFecha");
    const inputHoraInicio = document.getElementById("entrevistaHoraInicio");
    const inputHoraFin = document.getElementById("entrevistaHoraFin");



    // --- Datos locales ---
let usuarios = [];


function cargarUsuariosBD() {
    fetch("administrador.php?listar_usuarios=1")
        .then(r => r.json())
        .then(data => {
            usuarios = data.map(u => ({
    // Usamos las claves exactas de la consulta SQL: NombreCompleto, email, roles, estados
    nombre: (u.NombreCompleto ?? "").trim(),
    email: (u.email ?? "").trim(),
    rol: (u.roles ?? "").trim(), // Cambiado de 'rol' a 'roles'
    estado: (u.estados ?? "").trim() // Cambiado de 'estado' a 'estados'
}));

            renderUsuarios();
        });
}

// =====================================================
// === NUEVAS FUNCIONES: CARGAR CLIENTES Y RECLUTADORES ===
// =====================================================

function cargarClientes() {
    fetch("administrador.php?listar_clientes_activos=1")
        .then(r => r.json())
        .then(data => {
            // Asegurarse de que el select está vacío excepto por la opción por defecto
            inputClienteId.innerHTML = '<option value="">Seleccione un Candidato...</option>';
            data.forEach(c => {
                const option = document.createElement('option');
                option.value = c.id;
                option.textContent = `ID ${c.id} - ${c.nombre}`;
                inputClienteId.appendChild(option);
            });
        })
        .catch(err => console.error("Error cargando clientes:", err));
}

function cargarReclutadores() {
    fetch("administrador.php?listar_reclutadores_activos=1")
        .then(r => r.json())
        .then(data => {
            // Asegurarse de que el select está vacío excepto por la opción por defecto
            inputReclutadorId.innerHTML = '<option value="">Seleccione un Reclutador...</option>';
            data.forEach(r => {
                const option = document.createElement('option');
                option.value = r.id;
                option.textContent = `ID ${r.id} - ${r.nombre}`;
                inputReclutadorId.appendChild(option);
            });
        })
        .catch(err => console.error("Error cargando reclutadores:", err));
}
// =====================================================
// =====================================================


    // Las siguientes variables locales se estaban usando en el código original pero solo en JS para renderizar
    // ya que la lógica de PHP no las necesitaba
    // let vacantes = JSON.parse(localStorage.getItem("vacantes")) || [];
    let disponibilidades = JSON.parse(localStorage.getItem("disponibilidades")) || [
      { empleado: "Ana Martínez", semana: "11-17 Nov", dias: 5, estado: "Disponible" },
      { empleado: "Carlos Pérez", semana: "11-17 Nov", dias: 3, estado: "Parcial" },
    ];
    let logs = JSON.parse(localStorage.getItem("logs")) || [
      { fecha: "2025-11-13 10:23", tipo: "Correo", destino: "cliente@empresa.com", estado: "Enviado" },
      { fecha: "2025-11-13 09:50", tipo: "WhatsApp", destino: "+52 5512345678", estado: "Fallido" },
    ];

    // --- Estado de edición de usuario ---
    let usuarioEditIndex = null;
    window.emailOriginal = null; // Variable global para guardar el email original al editar

    // --- Inicialización ---
    // renderVacantes(); // Se llama dentro de cargarVacantesBD()
    renderDisponibilidades();
    renderLogs();
    actualizarMetricas();
    cargarUsuariosBD();
    cargarClientes(); // Llamada para rellenar el SELECT de clientes
    cargarReclutadores(); // Llamada para rellenar el SELECT de reclutadores


    console.log("✅ administrador.js inicializado correctamente");

    // =====================================================
    // === GESTIÓN DE USUARIOS ===
    // =====================================================

    btnNuevoUsuario.addEventListener("click", () => {
      usuarioEditIndex = null;
      window.emailOriginal = null;
      document.querySelector("#modalUsuario .modal-title").textContent = "Registrar nuevo usuario";
      limpiarCampos();
      modal.show();
    });

// ⭐⭐ ESTA LÍNEA ERA LA QUE FALTABA Y ESTABA AL FINAL DE LA FUNCIÓN JS ANTERIOR ⭐⭐
btnGuardarUsuario.addEventListener("click", handleGuardarUsuario);




    function handleGuardarUsuario() {
      const nombre = document.getElementById("usuarioNombre").value.trim();
      const correo = document.getElementById("usuarioCorreo").value.trim();
      const rol = document.getElementById("usuarioRol").value;
      const estado = document.getElementById("usuarioEstado").value;
      const telefono = document.getElementById("usuarioTelefono").value;
      const password = document.getElementById("usuarioPassword").value;

      if (!nombre || !correo || !rol || !estado || (rol !== "Candidato" && !password && !window.emailOriginal) || (rol === "Candidato" && !telefono)) {
        Swal.fire("Campos incompletos", "Por favor completa todos los campos requeridos para el rol seleccionado.", "warning");
        return;
      }

      Swal.fire("Procesando...", "Guardando usuario en la base de datos...", "info");

      const datos = new FormData();
      datos.append("nombre", nombre);
      datos.append("email", correo);
      datos.append("rol", rol);
      datos.append("estado", estado);
      datos.append("telefono", telefono);
      datos.append("password", password);
      datos.append("editando", window.emailOriginal ? "1" : "0");
      datos.append("email_original", window.emailOriginal ?? "");



    fetch("administrador.php", {
      method: "POST",
      body: datos
    })
    .then(res => res.text())
    .then(resp => {

      // ACEPTAR AMBAS RESPUESTAS DEL PHP: ok (crear) y edit_ok (editar)
      if (resp.trim() === "ok" || resp.trim() === "edit_ok") {

          Swal.fire("Éxito", "Usuario guardado correctamente.", "success");

          // Recargar página después del guardado para actualizar todo
          setTimeout(() => location.reload(), 800);

      } else {

          Swal.fire("Error", "No se pudo guardar el usuario: " + resp, "error");

      }
    });


}





    function limpiarCampos() {
      document.getElementById("usuarioNombre").value = "";
      document.getElementById("usuarioCorreo").value = "";
      document.getElementById("usuarioRol").value = ""; // ← Rol vacío
      document.getElementById("usuarioEstado").selectedIndex = 0;

      // Ocultar los dos campos dinámicos
      document.getElementById("campoTelefono").style.display = "none";
      document.getElementById("campoPassword").style.display = "none";

      // Limpiar sus valores
      document.getElementById("usuarioTelefono").value = "";
      document.getElementById("usuarioPassword").value = "";
    }


    function renderUsuarios() {
      tablaUsuarios.innerHTML = "";

      usuarios.forEach((u, i) => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${u.nombre}</td>
          <td>${u.email}</td>
          <td>${u.rol}</td>
          <td><span class="status ${estadoColor(u.estado)}">${u.estado}</span></td>
          <td>
            <button class="btn btn-sm btn-outline editar" data-index="${i}">Editar</button>
            <button class="btn btn-sm eliminar" data-index="${i}">Eliminar</button>
          </td>
        `;
        tablaUsuarios.appendChild(tr);
      });
    }



    // Delegación de eventos para la tabla de usuarios
    tablaUsuarios.addEventListener("click", (e) => {
      // Eliminar usuario
    if (e.target.classList.contains("eliminar")) {
      const i = Number(e.target.dataset.index);
      const u = usuarios[i];
      if (!u) return;

      Swal.fire({
          title: "¿Eliminar usuario?",
          text: `El usuario "${u.nombre}" ( ${u.email} ) será eliminado permanentemente.`,
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#d33",
          confirmButtonText: "Sí, eliminar",
          cancelButtonText: "Cancelar",
      }).then((res) => {
          if (res.isConfirmed) {

              const datos = new FormData();
              datos.append("eliminar_usuario", 1);
              datos.append("email", u.email.trim().toLowerCase());

              fetch("administrador.php", {
                  method: "POST",
                  body: datos
              })
              .then(r => r.text())
              .then(resp => {
                  if (resp.trim() === "ok") {
                      Swal.fire("Eliminado", "Usuario eliminado correctamente.", "success");
                      setTimeout(() => location.reload(), 600);
                  } else if (resp.trim() === "no_existe") {
                      Swal.fire("Error", "El usuario no fue encontrado o no existe.", "error");
                  }
                  else {
                      Swal.fire("Error", "No se pudo eliminar el usuario. Respuesta: " + resp, "error");
                  }
              });

          }
      });
    }


      // Editar usuario
    if (e.target.classList.contains("editar")) {
      const i = Number(e.target.dataset.index);
      const u = usuarios[i];
      if (!u) return;

      usuarioEditIndex = i;
      window.emailOriginal = u.email; // <== SE GUARDA SIEMPRE

      document.querySelector("#modalUsuario .modal-title").textContent = "Editar usuario";
      document.getElementById("usuarioNombre").value = u.nombre;
      document.getElementById("usuarioCorreo").value = u.email;
      document.getElementById("usuarioRol").value = u.rol;
      document.getElementById("usuarioEstado").value = u.estado;
      
      // Activar la lógica del campo dinámico manualmente
      document.getElementById("usuarioRol").dispatchEvent(new Event('change'));

      // Limpiar password/teléfono por defecto al abrir el modal de edición, 
      // para que solo se envíen si se modifican.
      document.getElementById("usuarioTelefono").value = "";
      document.getElementById("usuarioPassword").value = "";

      modal.show();
    }
    });

// =====================================================
    // === GESTIÓN DE VACANTES (CON BASE DE DATOS) ===
    // =====================================================

    // 1. Cargar vacantes al iniciar
    cargarVacantesBD();

    // 2. Función para obtener vacantes del servidor (PHP)
    function cargarVacantesBD() {
        fetch("administrador.php?listar_vacantes=1")
            .then(r => r.json())
            .then(data => {
                window.vacantes = data; // Guardamos los datos reales de la BD en un global
                renderVacantes();
                actualizarMetricas(); // Actualizamos contadores
            })
            .catch(err => console.error("Error cargando vacantes:", err));
    }

    // 3. Renderizar tabla (Usando ID real de BD)
    function renderVacantes() {
      tablaVacantes.innerHTML = "";
      window.vacantes.forEach((v) => { // Usamos window.vacantes
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${v.titulo}</td>
          <td>${v.departamento}</td>
          <td>${v.tipo}</td>
          <td><span class="status ${estadoColorVacante(v.estado)}">${v.estado}</span></td>
          <td>
            <button class="btn btn-sm btn-outline ver-vacante" data-id="${v.idVacante}">Ver</button>
            <button class="btn btn-sm btn-outline editar-vacante" data-id="${v.idVacante}">Editar</button>
            <button class="btn btn-sm btn-outline-primary agendar-entrevista" data-id="${v.idVacante}">Programar entrevista</button>
            <button class="btn btn-sm eliminar-vacante" data-id="${v.idVacante}" data-titulo="${v.titulo}">Eliminar</button>
          </td>`;
        tablaVacantes.appendChild(tr);
      });
    }

    // 4. Eventos de la Tabla Vacantes (Ver, Editar, Eliminar, Programar)
    tablaVacantes.addEventListener("click", (e) => {
      const btn = e.target;
        
      // A) Ver vacante
      if (btn.classList.contains("ver-vacante")) {
        const id = btn.dataset.id;
        window.location.href = `ver-vacante.php?view=${id}`;
      }

      // B) Editar vacante
      if (btn.classList.contains("editar-vacante")) {
        const id = btn.dataset.id;
        window.location.href = `crear-vacante.php?edit=${id}`;
      }

      // C) Eliminar vacante (Conexión a PHP)
      if (btn.classList.contains("eliminar-vacante")) {
        const id = btn.dataset.id;
        const titulo = btn.dataset.titulo;

        Swal.fire({
          title: "¿Eliminar vacante?",
          text: `La vacante "${titulo}" será eliminada de la Base de Datos.`,
          icon: "warning", showCancelButton: true, confirmButtonColor: "#d33", confirmButtonText: "Sí, eliminar"
        }).then((res) => {
          if (res.isConfirmed) {
            
            const datos = new FormData();
            datos.append("eliminar_vacante", 1);
            datos.append("id", id);

            fetch("administrador.php", { method: "POST", body: datos })
              .then(r => r.text())
              .then(resp => {
                  if (resp.trim() === "ok") {
                      Swal.fire("Eliminado", "Vacante eliminada.", "success");
                      cargarVacantesBD(); // Recargar tabla
                  } else {
                      Swal.fire("Error", "No se pudo eliminar la vacante.", "error");
                  }
              });
          }
        });
      }

      // D) Programar entrevista (abre modal)
      if (btn.classList.contains("agendar-entrevista")) {
        const id = btn.dataset.id;
        if (inputVacanteId) inputVacanteId.value = id;
        
        // MODIFICADO: Reiniciar los SELECTS a la opción por defecto
        if (inputClienteId) inputClienteId.selectedIndex = 0; 
        if (inputReclutadorId) inputReclutadorId.selectedIndex = 0; 
        
        if (inputFecha) inputFecha.value = "";
        if (inputHoraInicio) inputHoraInicio.value = "";
        if (inputHoraFin) inputHoraFin.value = "";
        
        const m = new bootstrap.Modal(modalEntrevista);
        m.show();
      }
    });

    // 5. Redirección botón Crear Vacante
    if (btnCrearVacante) {
        btnCrearVacante.addEventListener("click", (e) => {
            e.preventDefault();
            // Esta línea es la que define la dirección a crear-vacante.php
            window.location.href = "crear-vacante.php";
        });
    }

    // =====================================================
    // === PROGRAMAR ENTREVISTA (ENVÍO A PHP) ===
    // =====================================================
    if (formEntrevista) {
      formEntrevista.addEventListener("submit", (e) => {
        e.preventDefault();

        // Validación adicional para selects
        if (!inputClienteId.value || !inputReclutadorId.value) {
             Swal.fire("Error", "Por favor, selecciona un Candidato y un Reclutador.", "error");
             return;
        }

        const datos = new FormData();
        datos.append("agendar_entrevista", 1);
        datos.append("idVacante", inputVacanteId.value.trim());
        // Se obtiene el valor (ID) del elemento SELECT
        datos.append("idClientes", inputClienteId.value.trim()); 
        datos.append("idReclutador", inputReclutadorId.value.trim()); // Se obtiene el valor (ID) del elemento SELECT
        datos.append("fecha", inputFecha.value.trim());
        datos.append("hora_inicio", inputHoraInicio.value.trim());
        datos.append("hora_fin", inputHoraFin.value.trim());

        fetch("administrador.php", { method: "POST", body: datos })
          .then(r => r.text())
          .then(resp => {
            if (resp.trim() === "ok") {
              Swal.fire("Éxito", "Entrevista programada correctamente.", "success");
              setTimeout(() => location.reload(), 800);
            } else {
              Swal.fire("Error", resp.trim() || "No se pudo programar la entrevista.", "error");
            }
          })
          .catch(() => Swal.fire("Error", "No se pudo programar la entrevista.", "error"));
      });
    }

    // =====================================================
    // === UTILIDADES Y METRICAS ===
    // =====================================================

    function estadoColor(estado) {
      switch (estado) {
        case "Activo": return "enviado";
        case "Pendiente": return "pendiente";
        case "Inactivo": return "fallido";
        default: return "";
      }
    }

    function estadoColorVacante(estado) {
      switch (estado) {
        case "Activa": return "enviado";
        case "En revisión": return "pendiente";
        case "Cerrada": return "fallido";
        default: return "";
      }
    }

    function actualizarMetricas() {
      // Usuarios Activos (Desde PHP)
      fetch("administrador.php?contar_usuarios=1")
          .then(r => r.text())
          .then(num => {
              document.getElementById("usuariosActivos").textContent = num;
          });

      // Vacantes Activas (Desde PHP)
      fetch("administrador.php?contar_vacantes=1")
          .then(r => r.text())
          .then(num => {
              document.getElementById("vacantesActivas").textContent = num;
          });
    }

    // === LÓGICA DE DISPONIBILIDAD Y LOGS (Usando datos locales temporales) ===

    function renderDisponibilidades() {
        const tablaDisponibilidadesBody = document.querySelector("#tablaDisponibilidades tbody");
        // Nota: Esta parte se renderiza desde PHP en la versión final
        // Dejo la función solo por si los datos locales eran un fallback de emergencia.
        /*
        tablaDisponibilidadesBody.innerHTML = "";
        disponibilidades.forEach(d => {
            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td>${d.empleado}</td>
                <td>${d.semana}</td>
                <td>${d.dias} días</td>
                <td><span class="status ${estadoColor(d.estado)}">${d.estado}</span></td>
            `;
            tablaDisponibilidadesBody.appendChild(tr);
        });
        */
    }

    function renderLogs() {
        const tablaLogsBody = document.querySelector("#tablaLogs tbody");
        tablaLogsBody.innerHTML = "";
        logs.forEach(l => {
            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td>${l.fecha}</td>
                <td>${l.tipo}</td>
                <td>${l.destino}</td>
                <td><span class="status ${estadoColor(l.estado)}">${l.estado}</span></td>
            `;
            tablaLogsBody.appendChild(tr);
        });
    }


}); // Fin de DOMContentLoaded
    </script>
</body>
</html>