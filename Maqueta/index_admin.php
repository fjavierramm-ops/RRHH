<?php 
// session_start() ya se llama en config.php
require_once('config.php');
$conn = connection();

if (!isset($_SESSION['admin_id'])) {
    header("Location: Admlogin.php");
    exit;
}

$adminId = $_SESSION['admin_id'];
$email = $_SESSION['admin_email'];

// Consulta mejorada usando prepared statements
$sql = "SELECT NombreCompleto, roles FROM reclutadores WHERE idreclutadores = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

$NombreCompleto = $data ? $data['NombreCompleto'] : "Reclutador Desconocido";
$rolUsuario = $data ? ($data['roles'] ?? "Reclutador") : "Reclutador";
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Perfil de Administrador - Gestión</title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" />
  <link rel="stylesheet" href="estilo_admin.css" />

  <style>
    .status.activa { background-color: #d1e7dd; color: #0f5132; }
    .status.cerrada { background-color: #f8d7da; color: #842029; }
    .status.enrevision { background-color: #fff3cd; color: #664d03; }
    
    .btn-cerrar-perfil {
      display: inline-block;
      padding: 8px 16px;
      background-color: #c00;
      color: #fff !important;
      text-decoration: none;
      border-radius: 5px;
      border: none;
      transition: background-color 0.2s;
    }
    .btn-cerrar-perfil:hover { background-color: #900; }

    .admin-nav-links {
      display: flex;
      gap: 15px;
      margin-bottom: 20px;
      justify-content: flex-end;
    }
  </style>
</head>
<body>

  <div class="container">

    <div class="admin-nav-links">
        <a href="administrador.php" class="btn btn-sm btn-outline-primary">Panel General</a>
        <a href="logout_admin.php" class="btn-cerrar-perfil">Cerrar Sesión</a>
    </div>
    
    <div class="banner-container mb-5">
      <img id="bannerImage" src="https://images.unsplash.com/photo-1503264116251-35a269479413?auto=format&fit=crop&w=1200&q=60" alt="Banner" class="banner-img" />

      <div class="menu-container">
        <button id="menuToggle" class="menu-btn">
          <i class="bi bi-three-dots-vertical"></i>
        </button>
        <div id="menuOptions" class="menu-options">
          <label for="fileInput" class="menu-item">Cambiar foto de perfil</label>
          <label for="bannerInput" class="menu-item">Cambiar banner</label>
          <button id="resetBanner" class="menu-item">Restaurar banner</button>
        </div>
        <input type="file" id="fileInput" accept="image/*" hidden />
        <input type="file" id="bannerInput" accept="image/*" hidden />
      </div>

      <div class="banner-content" id="bannerContent">
        <img id="profileImage" src="https://cdn-icons-png.flaticon.com/512/847/847969.png" alt="Perfil" class="profile-img-left" />
        <div class="banner-info">
          <h2 class="user-name"><?php echo htmlspecialchars($NombreCompleto); ?></h2>
          <h5 class="user-role"><?php echo htmlspecialchars($rolUsuario); ?></h5>
          <p class="user-email"><?php echo htmlspecialchars($email); ?></p>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <h3>Información de Contacto y Rol</h3>
      <div class="row">
        <div class="col-md-4">
          <p><strong>Nombre:</strong> <?php echo htmlspecialchars($NombreCompleto); ?></p>
          <p><strong>Correo:</strong> <?php echo htmlspecialchars($email); ?></p>
        </div>
        <div class="col-md-4">
          <p><strong>Puesto:</strong> Reclutador Senior</p>
          <p><strong>Área:</strong> <?php echo htmlspecialchars($rolUsuario); ?></p>
        </div>
        <div class="col-md-4">
          <p><strong>Fecha de ingreso:</strong> 01/08/2021</p>
          <p><strong>Estado:</strong> <span class="status activa">Activo</span></p>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <h3>Vacantes bajo mi cargo</h3>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Vacante</th>
              <th>Departamento</th>
              <th>Candidatos</th>
              <th>Fecha de Cierre</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Obtener vacantes del reclutador actual
            $sql_vacantes = "SELECT 
                              v.id_vacante,
                              v.titulo,
                              COALESCE(v.departamento, v.empresa) AS departamento,
                              COALESCE(v.fechaCierre, v.fecha_publicacion) AS fecha_cierre,
                              v.estado,
                              COUNT(DISTINCT a.id_aplicacion) AS total_candidatos
                            FROM vacantes v
                            LEFT JOIN aplicaciones a ON a.id_vacante = v.id_vacante
                            WHERE v.responsable LIKE ? OR v.responsable IS NULL OR v.responsable = ''
                            GROUP BY v.id_vacante, v.titulo, v.departamento, v.empresa, v.fechaCierre, v.fecha_publicacion, v.estado
                            ORDER BY v.id_vacante DESC
                            LIMIT 10";
            $stmt_vacantes = $conn->prepare($sql_vacantes);
            $nombre_like = "%" . $NombreCompleto . "%";
            $stmt_vacantes->bind_param("s", $nombre_like);
            $stmt_vacantes->execute();
            $result_vacantes = $stmt_vacantes->get_result();
            
            if ($result_vacantes->num_rows > 0) {
              while ($vac = $result_vacantes->fetch_assoc()) {
                $estado_clase = ($vac['estado'] === "Activa" || $vac['estado'] === "Abierta") ? "activa" : 
                               ($vac['estado'] === "En revisión" ? "enrevision" : "cerrada");
                echo "<tr>";
                echo "<td>" . htmlspecialchars($vac['titulo']) . "</td>";
                echo "<td>" . htmlspecialchars($vac['departamento']) . "</td>";
                echo "<td>" . $vac['total_candidatos'] . "</td>";
                echo "<td>" . htmlspecialchars($vac['fecha_cierre']) . "</td>";
                echo "<td><span class='status {$estado_clase}'>" . htmlspecialchars($vac['estado']) . "</span></td>";
                echo "<td>";
                echo "<button class='btn btn-sm btn-primary gestionar-vacante' data-vacante='" . htmlspecialchars($vac['titulo']) . "' data-departamento='" . htmlspecialchars($vac['departamento']) . "' data-candidatos='" . $vac['total_candidatos'] . "' data-estado='" . htmlspecialchars($vac['estado']) . "'>Gestionar</button>";
                echo "</td>";
                echo "</tr>";
              }
            } else {
              echo "<tr><td colspan='6' class='text-center'>No hay vacantes asignadas</td></tr>";
            }
            $stmt_vacantes->close();
            ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Gestionar Vacante -->
    <div class="modal fade" id="modalGestionar" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Gestionar Vacante</h5>
            <button class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p><strong>Vacante:</strong> <span id="gVacante"></span></p>
            <p><strong>Departamento:</strong> <span id="gDepartamento"></span></p>
            <p><strong>Candidatos:</strong> <span id="gCandidatos"></span></p>
            <p><strong>Estado:</strong> <span id="gEstado"></span></p>
            <hr>
            <p><strong>Opciones disponibles:</strong></p>
            <ul>
                <li>Revisar candidatos</li>
                <li>Actualizar estado</li>
                <li>Modificar información de la vacante</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Ver Reporte -->
    <div class="modal fade" id="modalReporte" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Reporte de Vacante</h5>
            <button class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p><strong>Vacante:</strong> <span id="rVacante"></span></p>
            <p><strong>Departamento:</strong> <span id="rDepartamento"></span></p>
            <p><strong>Total candidatos:</strong> <span id="rCandidatos"></span></p>
            <hr>
            <p><strong>Resumen:</strong></p>
            <p id="rResumen">Esta vacante generó tráfico adecuado.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <h3>Preferencias de notificación</h3>
      <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" id="notiEmail_admin" checked />
        <label class="form-check-label" for="notiEmail_admin">Recibir notificaciones por correo electrónico</label>
      </div>
      <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" id="notiSistema_admin" checked />
        <label class="form-check-label" for="notiSistema_admin">Mostrar alertas en el sistema</label>
      </div>
      <div class="mt-3">
        <button class="btn" id="guardarPreferencias">Guardar cambios</button>
      </div>
    </div>

  </div>

  <!-- MODALES Y SCRIPTS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

  <script>
// --- BOTONES GESTIONAR VACANTE ---
document.querySelectorAll(".gestionar-vacante").forEach(btn => {
  btn.addEventListener("click", function() {
    document.getElementById("gVacante").textContent = this.dataset.vacante;
    document.getElementById("gDepartamento").textContent = this.dataset.departamento;
    document.getElementById("gCandidatos").textContent = this.dataset.candidatos;
    document.getElementById("gEstado").textContent = this.dataset.estado;

    new bootstrap.Modal(document.getElementById("modalGestionar")).show();
  });
});

// --- BOTONES VER REPORTE ---
document.querySelectorAll(".btn.btn-sm.btn-outline-secondary").forEach(btn => {
  btn.addEventListener("click", function() {
    const fila = this.closest("tr");
    const datos = fila.children;

    document.getElementById("rVacante").textContent = datos[0].textContent;
    document.getElementById("rDepartamento").textContent = datos[1].textContent;
    document.getElementById("rCandidatos").textContent = datos[2].textContent;

    new bootstrap.Modal(document.getElementById("modalReporte")).show();
  });
});

// --- GUARDAR PREFERENCIAS ---
document.getElementById("guardarPreferencias")?.addEventListener("click", function() {
  const email = document.getElementById("notiEmail_admin").checked;
  const sistema = document.getElementById("notiSistema_admin").checked;
  
  // Aquí puedes agregar lógica para guardar en BD si lo necesitas
  alert("Preferencias guardadas: Email=" + email + ", Sistema=" + sistema);
});

// --- MENÚ TOGGLE ---
document.getElementById("menuToggle")?.addEventListener("click", function() {
  const menu = document.getElementById("menuOptions");
  menu.classList.toggle("active");
});

// --- CAMBIAR FOTO DE PERFIL ---
document.getElementById("fileInput")?.addEventListener("change", function(e) {
  const file = e.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById("profileImage").src = e.target.result;
    };
    reader.readAsDataURL(file);
  }
});

// --- CAMBIAR BANNER ---
document.getElementById("bannerInput")?.addEventListener("change", function(e) {
  const file = e.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById("bannerImage").src = e.target.result;
    };
    reader.readAsDataURL(file);
  }
});

// --- RESTAURAR BANNER ---
document.getElementById("resetBanner")?.addEventListener("click", function() {
  document.getElementById("bannerImage").src = "https://images.unsplash.com/photo-1503264116251-35a269479413?auto=format&fit=crop&w=1200&q=60";
});
  </script>

</body>
</html>

