<?php
require_once "config.php";

if (!isset($_SESSION["id"])) {
    header("location: login.html"); 
    exit;
}

$id_usuario = $_SESSION["id"]; 
$mensaje_cambio = ""; 

// ** 1. Procesar la solicitud de cambio de fecha (POST) **
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_entrevista']) && isset($_POST['fecha']) && isset($_POST['hora'])) {
    
    $id_entrevista_solicitada = trim($_POST['id_entrevista']);
    $fecha_solicitada = trim($_POST['fecha']);
    $hora_solicitada = trim($_POST['hora']);

    if (empty($id_entrevista_solicitada) || empty($fecha_solicitada) || empty($hora_solicitada)) {
        $mensaje_cambio = "Error: Faltan datos para la solicitud de cambio.";
    } else {
        $sql_update = "UPDATE entrevistas SET 
                        fecha_propuesta_3 = ?, 
                        hora_propuesta_3 = ?, 
                        status_confirmacion = 'Pendiente de cambio' 
                       WHERE id_entrevista = ?";

        if ($stmt = $mysqli->prepare($sql_update)) {
            $stmt->bind_param("ssi", $fecha_solicitada, $hora_solicitada, $id_entrevista_solicitada);

            if ($stmt->execute()) {
                $mensaje_cambio = "Solicitud de cambio enviada. Espera confirmación.";
            } else {
                $mensaje_cambio = "Error al actualizar: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $mensaje_cambio = "Error de consulta: " . $mysqli->error;
        }
    }
}

// ** 2. Lógica de Búsqueda **
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : null;
$filtro_sql = ""; // Variable para inyectar el filtro en la consulta

if ($busqueda) {
    // Escapamos la búsqueda para seguridad
    $b = $mysqli->real_escape_string($busqueda);
    // Agregamos condición: que el título O la empresa coincidan con la búsqueda
    $filtro_sql = " AND (v.titulo LIKE '%$b%' OR v.empresa LIKE '%$b%') ";
}

// ** 3. Consulta para obtener las entrevistas **
$sql_select = "SELECT 
                e.id_entrevista, 
                e.id_aplicacion,
                e.fecha_final, 
                e.hora_final, 
                e.status_confirmacion, 
                v.titulo, 
                v.empresa,
                e.fecha_propuesta_1, e.hora_propuesta_1,
                e.fecha_propuesta_2, e.hora_propuesta_2,
                e.fecha_propuesta_3, e.hora_propuesta_3
              FROM 
                entrevistas e
              JOIN 
                aplicaciones a ON e.id_aplicacion = a.id_aplicacion
              JOIN 
                vacantes v ON a.id_vacante = v.id_vacante
              WHERE 
                a.id_candidato = ? 
                $filtro_sql  -- Aquí insertamos el filtro si existe
              ORDER BY 
                e.fecha_final DESC, e.id_entrevista DESC";

$entrevistas = [];
if (isset($mysqli) && $mysqli && $stmt_select = $mysqli->prepare($sql_select)) {
    $stmt_select->bind_param("i", $id_usuario);
    if ($stmt_select->execute()) {
        $result = $stmt_select->get_result();
        while ($row = $result->fetch_assoc()) {
            $entrevistas[] = $row;
        }
        $result->free();
    }
    $stmt_select->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Entrevistas - Portal de Empleos</title>
  <link rel="stylesheet" href="estilo.css">
</head>
<body>
  <header class="dashboard-header">
      <h1>Bienvenido</h1>
      <p class="subtitle">Revisa el estado de tus entrevistas</p>
      
      <form action="entrevistas.php" method="GET" class="search-bar">
        <input type="text" name="q" value="<?php echo htmlspecialchars($busqueda ?? ''); ?>" placeholder="Buscar en tus entrevistas..." required>
        <button type="submit">Buscar</button>
      </form>

      <nav class="dashboard-tabs">
        <button class="tab" onclick="window.location.href='inicio.php'">Vacantes</button>
        <button class="tab" onclick="window.location.href='aplicaciones.php'">Mis aplicaciones</button>
        <button class="tab active">Entrevistas</button>
      </nav>
  </header>

  <main class="dashboard-main">
    <section class="aplicaciones-section">
      
      <?php if ($busqueda): ?>
          <div style="margin-bottom: 20px;">
             <h2>Resultados para: "<?php echo htmlspecialchars($busqueda); ?>"</h2>
             <a href="entrevistas.php" style="font-size: 14px; color: #666; text-decoration: underline;">(Ver todas las entrevistas)</a>
          </div>
      <?php else: ?>
          <h2>Entrevistas programadas</h2>
      <?php endif; ?>
    
      <?php 
      if ($mensaje_cambio) {
          $clase_msg = (strpos($mensaje_cambio, '') !== false) ? '#e6fffa' : '#ffe6e6';
          $color_msg = (strpos($mensaje_cambio, '') !== false) ? '#006644' : '#cc0000';
          echo "<div style='margin-bottom: 20px; padding: 15px; border-radius: 8px; background-color: $clase_msg; color: $color_msg; border: 1px solid $color_msg; text-align:center;'>$mensaje_cambio</div>";
      }
      ?>

      <div class="aplicaciones-grid">
        <?php 
          if (count($entrevistas) > 0) {
              foreach ($entrevistas as $entrevista) {
                  // Lógica visual de fechas
                  $fecha_hora_texto = "N/A";
                  $fecha_hora_strong = "";
                  $estado = htmlspecialchars($entrevista['status_confirmacion']);

                  if ($entrevista['fecha_final'] && $entrevista['hora_final']) {
                      $fecha_obj = DateTime::createFromFormat('Y-m-d', $entrevista['fecha_final']);
                      $fecha_formateada = $fecha_obj ? $fecha_obj->format('d/m/Y') : $entrevista['fecha_final'];
                      $hora_formateada = substr($entrevista['hora_final'], 0, 5); 
                      $fecha_hora_texto = "Programada el día";
                      $fecha_hora_strong = "$fecha_formateada $hora_formateada";

                  } elseif ($entrevista['fecha_propuesta_3'] && $entrevista['hora_propuesta_3'] && $estado == 'Pendiente de cambio') {
                      $fecha_obj = DateTime::createFromFormat('Y-m-d', $entrevista['fecha_propuesta_3']);
                      $fecha_formateada = $fecha_obj ? $fecha_obj->format('d/m/Y') : $entrevista['fecha_propuesta_3'];
                      $hora_formateada = substr($entrevista['hora_propuesta_3'], 0, 5);
                      $fecha_hora_texto = "Cambio solicitado";
                      $fecha_hora_strong = "$fecha_formateada $hora_formateada";
                  } 
                  
                  // Lógica de propuestas para modal
                  $propuestas = [];
                  for ($i = 1; $i <= 3; $i++) {
                      if ($entrevista['fecha_propuesta_' . $i] && $entrevista['hora_propuesta_' . $i]) {
                           $fecha_js = $entrevista['fecha_propuesta_' . $i];
                           $hora_js = substr($entrevista['hora_propuesta_' . $i], 0, 5);
                           $fecha_propuesta_obj = DateTime::createFromFormat('Y-m-d', $entrevista['fecha_propuesta_' . $i]);
                           $fecha_visual = $fecha_propuesta_obj ? $fecha_propuesta_obj->format('Y-m-d') : ''; 
                           
                           $clase = 'verde';
                           $texto_extra = 'Disponible';
                           if ($i == 1) { $clase = 'azul'; $texto_extra = 'Disponibilidad óptima'; }
                           if ($i == 2) { $clase = 'amarilla'; $texto_extra = 'Confirmar fecha'; }

                           $propuestas[] = [
                               'clase' => $clase,
                               'texto' => $fecha_visual . ' – ' . $hora_js . ' – ' . $texto_extra,
                               'fecha_js' => $fecha_js,
                               'hora_js' => $hora_js
                           ];
                      }
                  }
                  $data_propuestas = htmlspecialchars(json_encode($propuestas), ENT_QUOTES, 'UTF-8'); 
          ?>
            <div class="card">
              <h3><?php echo htmlspecialchars($entrevista['titulo']); ?></h3>
              <div class="info">
                <p class="empresa"><?php echo htmlspecialchars($entrevista['empresa']); ?> | Guadalajara</p>
              </div>
              <div class="estado-titulo">Estado: <strong><?php echo $estado; ?></strong></div>
              <div class="fecha"><?php echo $fecha_hora_texto; ?> <strong><?php echo $fecha_hora_strong; ?></strong></div>
              <button class="btn-confirmar mostrar-modal-cambio" data-id-entrevista="<?php echo htmlspecialchars($entrevista['id_entrevista']); ?>" data-propuestas='<?php echo $data_propuestas; ?>'>
                  Solicitar cambio de fecha
              </button>
            </div>
        <?php 
              }
          } else {
              // MANEJO DE VACÍO (Diferente si buscó o si es nuevo)
              if ($busqueda) {
                  // Caso: Buscó algo y no hubo resultados
                  echo "<div style='grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;'>
                          <p>No encontramos entrevistas que coincidan con \"<b>" . htmlspecialchars($busqueda) . "</b>\".</p>
                          <a href='entrevistas.php' style='display:inline-block; margin-top:10px; color:#2b5c8f; text-decoration:underline;'>Ver todas</a>
                        </div>";
              } else {
                  // Caso: Usuario nuevo sin entrevistas (Empty State)
                  ?>
                  <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: #666;">
                      <div style="font-size: 50px; margin-bottom: 20px; opacity: 0.5;">📅</div>
                      <h3 style="color: #1a1a1a; margin-bottom: 10px; font-size: 20px;">No tienes entrevistas programadas</h3>
                      <p style="margin-bottom: 25px; font-size: 15px;">Aplica a vacantes y cuando una empresa te contacte, aparecerá aquí.</p>
                      <a href="inicio.php" style="display: inline-block; background-color: #2b5c8f; color: white; padding: 10px 25px; border-radius: 25px; text-decoration: none; font-weight: 600; transition: 0.3s;">
                          Buscar empleos
                      </a>
                  </div>
                  <?php
              }
          }
        ?>
      </div> 
    </section>
  </main>

  <div class="modal" id="modalCambioFecha" style="display: none;">
    <div class="modal-content">
      <button class="close-btn" id="cerrarModalCambioFecha">&times;</button>
      <h2 class="modal-title">Solicitar cambio de fecha</h2>
      <form method="POST" id="formCambioFecha" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <input type="hidden" id="id_entrevista_cambio" name="id_entrevista" value=""> 

        <div class="form-group">
          <label class="form-label" for="fecha">Selecciona una fecha disponible:</label>
          <input type="date" id="fecha" name="fecha" class="form-input" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="hora">Selecciona la hora:</label>
          <input type="time" id="hora" name="hora" class="form-input" required>
        </div>
        <div class="recomendaciones">
          <h3>Fechas recomendadas:</h3>
          <div class="opciones-fecha" id="opcionesRecomendadas">
            </div>
        </div>
        <button type="submit" class="login-btn">Solicitar cambio</button>
      </form>
    </div>
  </div>

  <script>
      function seleccionarOpcion(btn, fecha, hora) {
        document.querySelectorAll('#opcionesRecomendadas .opcion').forEach(b => b.classList.remove('seleccionada'));
        btn.classList.add('seleccionada');
        document.getElementById('fecha').value = fecha;
        document.getElementById('hora').value = hora;
      }

      document.querySelectorAll('.mostrar-modal-cambio').forEach(btn => {
        btn.onclick = () => {
          const modal = document.getElementById('modalCambioFecha');
          const idEntrevista = btn.getAttribute('data-id-entrevista');
          const dataAttr = btn.getAttribute('data-propuestas');
          let propuestas = [];
          if (dataAttr) {
              try { propuestas = JSON.parse(dataAttr); } catch (e) { console.error(e); }
          }
          const opcionesContainer = document.getElementById('opcionesRecomendadas');
          document.getElementById('id_entrevista_cambio').value = idEntrevista;
          opcionesContainer.innerHTML = ''; 
          if (propuestas.length > 0) {
              propuestas.forEach(propuesta => {
                  const button = document.createElement('button');
                  button.type = 'button';
                  button.className = `opcion ${propuesta.clase}`;
                  button.textContent = propuesta.texto; 
                  button.onclick = () => seleccionarOpcion(button, propuesta.fecha_js, propuesta.hora_js);
                  opcionesContainer.appendChild(button);
              });
          } else {
              opcionesContainer.innerHTML = '<p style="font-size:13px; color:#666;">No hay fechas sugeridas.</p>';
          }
          document.getElementById('fecha').value = '';
          document.getElementById('hora').value = '';
          modal.style.display = 'flex';
        };
      });
    
      document.getElementById('cerrarModalCambioFecha').onclick = function() {
        document.getElementById('modalCambioFecha').style.display = 'none';
      };
      
      document.getElementById('modalCambioFecha').onclick = function(e) {
        if (e.target === this) this.style.display = 'none';
      };
  </script>
</body>
</html>