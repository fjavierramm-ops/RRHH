<?php
// Incluir la configuración de la base de datos y la sesión
require_once 'config.php';

// 1. VERIFICAR ESTADO DE LA SESIÓN
if (!isset($_SESSION['id'])) {
    header("Location: login.html"); 
    exit;
}
$user_id = $_SESSION['id'];

function getStatusKey($status_text) {
    switch (strtolower($status_text)) {
        case 'en revisión': return 'revision';
        case 'en proceso': return 'proceso';
        case 'aceptado': return 'aceptado';
        case 'rechazado': return 'rechazado';
        case 'contratado': return 'contratado';
        default: return 'revision';
    }
}

// 2. LÓGICA DE BÚSQUEDA
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : null;
$filtro_sql = ""; 

if ($busqueda) {
    $b = $mysqli->real_escape_string($busqueda);
    // Filtramos por Título, Empresa o Ubicación
    $filtro_sql = " AND (v.titulo LIKE '%$b%' OR v.empresa LIKE '%$b%' OR v.ubicacion LIKE '%$b%') ";
}

// 3. CONSULTAR LAS APLICACIONES DEL CANDIDATO
$aplicaciones = [];
$sql = "
    SELECT 
        a.fecha_aplicacion, 
        a.status_aplicacion, 
        v.titulo, 
        v.empresa, 
        v.ubicacion,
        v.id_vacante  -- Importante: Traemos el ID para el enlace
    FROM aplicaciones a
    INNER JOIN vacantes v ON a.id_vacante = v.id_vacante
    WHERE a.id_candidato = ? 
    $filtro_sql 
    ORDER BY a.fecha_aplicacion DESC
";

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $row['status_key'] = getStatusKey($row['status_aplicacion']);
        $aplicaciones[] = $row;
    }
    $stmt->close();
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Aplicaciones - Portal de Empleos</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <h1>Bienvenido</h1>
            <p class="subtitle">Revisa el estado de tus aplicaciones</p>
            
            <form action="aplicaciones.php" method="GET" class="search-bar">
                <input type="text" name="q" value="<?php echo htmlspecialchars($busqueda ?? ''); ?>" placeholder="Buscar en tus aplicaciones..." required>
                <button type="submit">Buscar</button>
            </form>

            <nav class="dashboard-tabs">
                <button class="tab" onclick="window.location.href='inicio.php'">Vacantes</button>
                <button class="tab active">Mis Aplicaciones</button>
                <button class="tab" onclick="window.location.href='entrevistas.php'">Entrevistas</button>
            </nav>
        </header>

        <main class="dashboard-main">
            <section class="aplicaciones-section">
                
                <?php if ($busqueda): ?>
                    <div style="margin-bottom: 20px;">
                        <h2>Resultados para: "<?php echo htmlspecialchars($busqueda); ?>"</h2>
                        <a href="aplicaciones.php" style="font-size: 14px; color: #666; text-decoration: underline;">(Ver todas mis aplicaciones)</a>
                    </div>
                <?php else: ?>
                    <h2>Aplicaciones</h2>
                <?php endif; ?>

                <div class="aplicaciones-grid">

                    <?php if (empty($aplicaciones)): ?>
                        
                        <?php if ($busqueda): ?>
                            <!-- Caso: Buscó algo y no encontró nada -->
                            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">
                                <p>No encontramos aplicaciones que coincidan con "<b><?php echo htmlspecialchars($busqueda); ?></b>".</p>
                            </div>
                        <?php else: ?>
                            <!-- Caso: Usuario nuevo sin aplicaciones (Empty State Bonito) -->
                            <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: #666;">
                                <div style="font-size: 50px; margin-bottom: 20px; opacity: 0.5;">📂</div>
                                <h3 style="color: #1a1a1a; margin-bottom: 10px; font-size: 20px;">Aún no tienes aplicaciones</h3>
                                <p style="margin-bottom: 25px; font-size: 15px;">Tus postulaciones a vacantes aparecerán aquí.</p>
                                <a href="inicio.php" style="display: inline-block; background-color: #2b5c8f; color: white; padding: 10px 25px; border-radius: 25px; text-decoration: none; font-weight: 600; transition: 0.3s;">
                                    Ver vacantes disponibles
                                </a>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- Loop de aplicaciones -->
                        <?php foreach ($aplicaciones as $app): ?>
                            <?php 
                                $status_key = $app['status_key'];
                                $fecha_formateada = date('d/m/Y', strtotime($app['fecha_aplicacion']));
                            ?>
                            
                            <div class="aplicacion-card <?php echo $status_key; ?>">
                                <h3><?php echo htmlspecialchars($app['titulo']); ?></h3>
                                <p class="empresa"><?php echo htmlspecialchars($app['empresa']); ?> | <?php echo htmlspecialchars($app['ubicacion']); ?></p>
                                <p class="estado-titulo">Estado de aplicación</p>
                                <span class="estado etiqueta-<?php echo $status_key; ?>"><?php echo htmlspecialchars($app['status_aplicacion']); ?></span>
                                
                                <div class="barra-progreso"><div class="progreso <?php echo $status_key; ?>"></div></div>
                                
                                <div style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
                                    
                                    <!-- Botón: Ver detalles de la vacante (SIEMPRE VISIBLE) -->
                                    <button class="btn-detalles" onclick="window.location.href='detalle_vacante.php?id=<?php echo $app['id_vacante']; ?>'">
                                        Ver vacante
                                    </button>

                                    <!-- Botones adicionales según estado -->
                                    <?php if ($status_key === 'aceptado'): ?>
                                        <button class="btn-fase" onclick="window.location.href='entrevistas.php'">Ir a Entrevistas</button>
                                    <?php endif; ?>
                                </div>
                                
                                <p class="fecha" style="margin-top: 15px;">Aplicado el: <?php echo $fecha_formateada; ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</body>
</html>