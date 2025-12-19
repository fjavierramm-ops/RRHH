<?php
// Incluir la configuración de la base de datos y la sesión
require_once 'config.php';

// 1. VERIFICAR SESIÓN
if (!isset($_SESSION['id'])) {
    header("Location: login.html");
    exit;
}
$id_usuario = $_SESSION['id'];

// 2. OBTENER IDs DE VACANTES A LAS QUE YA APLICÓ EL USUARIO
$ids_aplicados = [];
if (isset($mysqli) && $mysqli) {
    $sql_check = "SELECT id_vacante FROM aplicaciones WHERE id_candidato = ?";
    if ($stmt = $mysqli->prepare($sql_check)) {
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $res_ids = $stmt->get_result();
        while ($row = $res_ids->fetch_assoc()) {
            $ids_aplicados[] = $row['id_vacante'];
        }
        $stmt->close();
    }
}

// ==========================================
// 3. LÓGICA DE BÚSQUEDA Y LISTADO
// ==========================================
$lista_vacantes = []; 
$busqueda = isset($_GET['q']) ? $mysqli->real_escape_string($_GET['q']) : null;

if ($busqueda) {
    // Buscar coincidencia + Filtro de vacantes abiertas/activas
    $sql_vacantes = "SELECT * FROM vacantes 
                     WHERE (titulo LIKE '%$busqueda%' 
                     OR empresa LIKE '%$busqueda%' 
                     OR ubicacion LIKE '%$busqueda%')
                     AND (estado = 'Abierta' OR estado = 'Activa')"; 
} else {
    // Vacantes recientes (activas o abiertas)
    $sql_vacantes = "SELECT * FROM vacantes 
                     WHERE (estado = 'Abierta' OR estado = 'Activa')
                     ORDER BY fecha_publicacion DESC 
                     LIMIT 10"; 
}

if (isset($mysqli) && $mysqli) {
    $result = $mysqli->query($sql_vacantes);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $lista_vacantes[] = $row;
        }
        $result->free();
    }
}

// ==========================================
// 4. OBTENER APLICACIONES EN PROCESO (Para la sección inferior)
// ==========================================
$aplicaciones_proceso = [];
if (isset($mysqli) && $mysqli) {
    $sql_apps = "SELECT 
                    a.id_aplicacion, 
                    a.status_aplicacion, 
                    v.titulo, 
                    v.empresa, 
                    v.ubicacion 
                 FROM aplicaciones a
                 JOIN vacantes v ON a.id_vacante = v.id_vacante
                 WHERE a.id_candidato = ?
                 ORDER BY a.fecha_aplicacion DESC"; 

    if ($stmt_apps = $mysqli->prepare($sql_apps)) {
        $stmt_apps->bind_param("i", $id_usuario);
        $stmt_apps->execute();
        $res_apps = $stmt_apps->get_result();
        while ($row_app = $res_apps->fetch_assoc()) {
            $aplicaciones_proceso[] = $row_app;
        }
        $stmt_apps->close();
    }
}

// 5. CERRAR CONEXIÓN
if (isset($mysqli) && $mysqli) {
    $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido - Portal de Empleos</title>
    <link rel="stylesheet" href="estilo.css">
    <script>
        function getStatusClass(status) {
            if (!status) return 'status-pending';
            let s = status.toLowerCase();
            if (s.includes('revisión') || s.includes('pendiente')) return 'etiqueta-revision'; 
            if (s.includes('proceso') || s.includes('entrevista')) return 'etiqueta-proceso';   
            if (s.includes('aceptado') || s.includes('contratado')) return 'etiqueta-aceptado'; 
            if (s.includes('rechazado')) return 'etiqueta-rechazado'; 
            return 'status-pending'; 
        }
    </script>
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <h1>Bienvenido</h1>
            <p class="subtitle">Encuentra tu próximo trabajo</p>
            <form action="inicio.php" method="GET" class="search-bar">
                <input type="text" name="q" value="<?php echo htmlspecialchars($busqueda ?? ''); ?>" placeholder="Buscar empleos, empresas o ubicación..." required>
                <button type="submit">Buscar</button>
            </form>
            <nav class="dashboard-tabs">
                <button class="tab active">Vacantes</button>
                <button class="tab" onclick="window.location.href='aplicaciones.php'">Mis aplicaciones</button>
                <button class="tab" onclick="window.location.href='entrevistas.php'">Entrevistas</button>
            </nav>
        </header>

        <main class="dashboard-main">
            
            <!-- SECCIÓN VACANTES -->
            <section class="vacantes-section">
                <?php if ($busqueda): ?>
                    <div style="margin-bottom: 20px;">
                        <h3>Resultados de búsqueda para: "<?php echo htmlspecialchars($busqueda); ?>"</h3>
                        <a href="inicio.php" style="font-size: 14px; color: #666; text-decoration: underline;">(Ver todas las vacantes)</a>
                    </div>
                <?php else: ?>
                    <h2>Vacantes recientes</h2>
                <?php endif; ?>
                
                <?php if (empty($lista_vacantes)): ?>
                    <p class="no-results">No se encontraron vacantes disponibles.</p>
                <?php else: ?>
                    <?php foreach ($lista_vacantes as $vacante): 
                        $ya_aplico = in_array($vacante['id_vacante'], $ids_aplicados);
                    ?>
                        <div class="vacante-card">
                            <div>
                                <h3><?php echo htmlspecialchars($vacante['titulo']); ?></h3>
                                <p><?php echo htmlspecialchars($vacante['empresa']); ?> | <?php echo htmlspecialchars($vacante['ubicacion']); ?></p>
                            </div>
                            
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <?php if ($ya_aplico): ?>
                                    <!-- Etiqueta visual de Aplicado -->
                                    <span class="apply-btn applied" style="cursor: default; border: 1px solid #a6d3b0; background-color: #cde8d4; color: #1f5130; padding: 6px 15px; border-radius: 20px; font-size: 13px;">✔ Aplicado</span>
                                <?php endif; ?>
                                
                                <!-- Botón Ver detalles SIEMPRE visible -->
                                <button class="apply-btn" onclick="window.location.href='detalle_vacante.php?id=<?php echo $vacante['id_vacante']; ?>'">
                                    Ver detalles
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <!-- SECCIÓN APLICACIONES -->
            <section class="aplicaciones-section">
                <h2>Mis aplicaciones en proceso</h2>
                
                <?php if (empty($aplicaciones_proceso)): ?>
                    <div style="text-align: center; padding: 20px; color: #666;">
                        <p>Aún no tienes aplicaciones en proceso.</p>
                        <p style="font-size: 13px; margin-top:5px;">¡Aplica a una vacante arriba para comenzar!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($aplicaciones_proceso as $app): 
                        $status = $app['status_aplicacion'] ?? 'En revisión';
                        $status_id = 'status_' . uniqid();
                    ?>
                        <div class="aplicacion-card1" onclick="window.location.href='aplicaciones.php'" style="cursor: pointer;">
                            <div class="barra-lateral"></div>
                            <div class="aplicacion-info">
                                <h3><?php echo htmlspecialchars($app['titulo'] ?? 'Vacante no disponible'); ?></h3>
                                <p><?php echo htmlspecialchars($app['empresa'] ?? 'Empresa'); ?> | <?php echo htmlspecialchars($app['ubicacion'] ?? 'Ubicación'); ?></p>
                            </div>
                            <span id="<?php echo $status_id; ?>" class="status"><?php echo htmlspecialchars($status); ?></span>
                        </div>

                        <script>
                            (function(){
                                var el = document.getElementById('<?php echo $status_id; ?>');
                                if (el) el.classList.add(getStatusClass('<?php echo addslashes($status); ?>'));
                            })();
                        </script>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

        </main>
    </div>
</body>
</html>