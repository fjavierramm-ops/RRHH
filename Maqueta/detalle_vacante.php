<?php
// Establece la codificación de caracteres
header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';

// 1. OBTENER EL ID DE LA VACANTE
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_vacante = $_GET['id'];
} else {
    header("Location: inicio.php");
    exit;
}

// 2. CONSULTAR DATOS DE LA VACANTE
$vacante = null;
$sql = "SELECT titulo, empresa, ubicacion, descripcion, requisitos, salario, tipo_trabajo FROM vacantes WHERE id_vacante = ?";

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $id_vacante);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $vacante = $result->fetch_assoc();
    }
    $stmt->close();
}

if ($vacante === null) {
    echo "<h1>Error 404: Vacante no encontrada.</h1>";
    exit;
}

// 3. VERIFICAR EL ESTADO DE LA SESIÓN Y SI YA SE POSTULÓ
$usuario_logueado = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$id_usuario = isset($_SESSION['id']) ? $_SESSION['id'] : null;

$ya_postulado = false;

// Si está logueado, verificamos en la BD si ya aplicó a ESTA vacante
if ($usuario_logueado && $id_usuario) {
    $sql_check = "SELECT id_aplicacion FROM aplicaciones WHERE id_candidato = ? AND id_vacante = ?";
    if ($stmt_check = $mysqli->prepare($sql_check)) {
        $stmt_check->bind_param("ii", $id_usuario, $id_vacante);
        $stmt_check->execute();
        $stmt_check->store_result();
        
        if ($stmt_check->num_rows > 0) {
            $ya_postulado = true;
        }
        $stmt_check->close();
    }
}

// 4. PREPARAR DATOS PARA EL HTML
$titulo = htmlspecialchars($vacante['titulo']);
$empresa = htmlspecialchars($vacante['empresa']);
$ubicacion = htmlspecialchars($vacante['ubicacion']);
$descripcion = nl2br(htmlspecialchars($vacante['descripcion']));
$requisitos = $vacante['requisitos']; // Asumimos HTML seguro
$salario = htmlspecialchars($vacante['salario']);
$tipo_trabajo = htmlspecialchars($vacante['tipo_trabajo']);

$beneficios_estaticos = '
    <ul>
        <li>Trabajo ' . $tipo_trabajo . '.</li>
        <li>Salario: ' . ($salario ? $salario : 'A discutir.') . '</li>
        <li>Seguro médico y prestaciones superiores.</li>
        <li>Bonos por desempeño.</li>
    </ul>
';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Vacante - <?php echo $titulo; ?></title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <div class="detalle-container">
        <header class="detalle-header">
            <h1><?php echo $titulo; ?></h1>
            <p class="empresa"><?php echo $empresa; ?> | <?php echo $ubicacion; ?></p>
        </header>

        <section class="detalle-info">
            <h2>Descripción del puesto</h2>
            <p><?php echo $descripcion; ?></p>

            <h2>Requisitos</h2>
            <?php echo $requisitos; ?>

            <h2>Detalles y Beneficios</h2>
            <?php echo $beneficios_estaticos; ?>
        </section>

        <section class="postulacion-section">
            <h2>¿Te interesa esta vacante?</h2>

            <?php if ($usuario_logueado): ?>
                <!-- Caso 2: Usuario logueado -->
                <div id="logueado" class="postulacion-estado">
                    
                    <?php if ($ya_postulado): ?>
                        <!-- SUB-CASO: YA APLICÓ -->
                        <div style="background-color: #d4edda; color: #155724; padding: 20px; border-radius: 8px; border: 1px solid #c3e6cb; text-align: center;">
                            <h3 style="margin: 0 0 10px 0; font-size: 18px;">✔ Ya te has postulado</h3>
                            <p style="margin: 0; font-size: 14px;">Puedes ver el estado de tu solicitud en "Mis Aplicaciones".</p>
                            <button class="btn-secundario" style="margin-top: 15px;" onclick="window.location.href='aplicaciones.php'">Ir a Mis Aplicaciones</button>
                        </div>
                    <?php else: ?>
                        <!-- SUB-CASO: NO HA APLICADO (Mostrar botón) -->
                        <button class="btn-principal" onclick="postular(<?php echo $id_vacante; ?>)">Postularme</button>
                    <?php endif; ?>

                </div>
            <?php else: ?>
                <!-- Caso 1: Usuario NO logueado -->
                <div id="no-logueado" class="postulacion-estado">
                    <p>Para postularte, inicia sesión.</p>
                    <div class="acciones-postulacion">
                        <button class="btn-principal" onclick="window.location.href='login.html'">Iniciar sesión</button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Modal de confirmación -->
            <div id="modal-confirmacion" class="modal" style="display:none;">
                <div class="modal-contenido">
                    <p id="modal-mensaje" class="p">Tu postulación ha sido enviada correctamente.</p>
                    <button class="btn-principal" onclick="cerrarModal()">Aceptar</button>
                </div>
            </div>

        </section>

    </div>

    <script>
        async function postular(idVacante) {
            const modal = document.getElementById("modal-confirmacion");
            const modalMensaje = document.getElementById("modal-mensaje");

            modalMensaje.textContent = "Procesando postulación...";
            modal.style.display = "flex";

            try {
                const formData = new FormData();
                formData.append('id_vacante', idVacante);

                const response = await fetch('postular.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    modalMensaje.textContent = result.message;
                    // Ocultamos el botón para evitar doble click inmediato
                    const btn = document.querySelector('.btn-principal');
                    if(btn) btn.style.display = 'none'; 
                } else {
                    modalMensaje.textContent = "Error al postular: " + result.message;
                }

            } catch (error) {
                modalMensaje.textContent = "Error de red al conectar con el servidor.";
                console.error("Error de Fetch:", error);
            }
        }

        function cerrarModal() {
            document.getElementById("modal-confirmacion").style.display = "none";
            // Recargamos la página para que el PHP detecte que ya se postuló y cambie el botón por el mensaje
            window.location.reload();
        }
    </script>

</body>
</html>