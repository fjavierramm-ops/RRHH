<?php
// Incluir la configuración de la base de datos y la sesión
require_once 'config.php';

// Directorio donde se guardarán los archivos (debes crear esta carpeta)
$upload_dir = 'uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Obtener y sanear datos
    $nombre = $mysqli->real_escape_string($_POST['nombre']);
    $email = $mysqli->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $telefono = $mysqli->real_escape_string($_POST['telefono'] ?? '');
    $habilidades_tecnicas = $mysqli->real_escape_string($_POST['habilidades_tecnicas'] ?? '');
    $habilidades_blandas = $mysqli->real_escape_string($_POST['habilidades_blandas'] ?? '');
    $portfolio_url = $mysqli->real_escape_string($_POST['portfolio_url'] ?? '');

    // 2. Validaciones
    if (empty($nombre) || empty($email) || empty($password) || empty($_FILES['cv_file']['name'])) {
        die("Error: Faltan campos requeridos (Nombre, Correo, Contraseña, CV).");
    }

    if ($password !== $confirm_password) {
        die("Error: Las contraseñas no coinciden.");
    }

    // 3. Subir CV y Portafolio
    $cv_path = '';
    $portfolio_file_path = '';
    $subido_exitoso = true;

    // Subir CV
    $cv_file = $_FILES['cv_file'];
    $cv_file_ext = pathinfo($cv_file['name'], PATHINFO_EXTENSION);
    $cv_filename = uniqid('cv_') . '.' . $cv_file_ext;
    $cv_target_file = $upload_dir . $cv_filename;

    if (move_uploaded_file($cv_file['tmp_name'], $cv_target_file)) {
        $cv_path = $cv_target_file;
    } else {
        $subido_exitoso = false;
        die("Error al subir el CV.");
    }

    // Subir Portafolio (Opcional)
    if (!empty($_FILES['portfolio_file']['name'])) {
        $portfolio_file = $_FILES['portfolio_file'];
        $portfolio_file_ext = pathinfo($portfolio_file['name'], PATHINFO_EXTENSION);
        $portfolio_filename = uniqid('port_') . '.' . $portfolio_file_ext;
        $portfolio_target_file = $upload_dir . $portfolio_filename;

        if (move_uploaded_file($portfolio_file['tmp_name'], $portfolio_target_file)) {
            $portfolio_file_path = $portfolio_target_file;
        } else {
            // No es crítico, pero se puede notificar el error
            echo "Advertencia: Error al subir el portafolio (opcional).";
        }
    }

    // 4. Hashear la Contraseña (¡CRUCIAL para la seguridad!)
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 5. Insertar en la Base de Datos
    $stmt = $mysqli->prepare("INSERT INTO candidatos (nombre, email, password, telefono, habilidades_tecnicas, habilidades_blandas, cv_path, portfolio_file_path, portfolio_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Bindear parámetros (s=string)
    $stmt->bind_param("sssssssss", $nombre, $email, $hashed_password, $telefono, $habilidades_tecnicas, $habilidades_blandas, $cv_path, $portfolio_file_path, $portfolio_url);
    
    if ($stmt->execute()) {
        // Registro exitoso, iniciar sesión y redirigir
        $_SESSION['loggedin'] = true;
        $_SESSION['id'] = $mysqli->insert_id;
        $_SESSION['nombre'] = $nombre;
        header("location: Inicio.php"); // Redirige al dashboard
        exit;
    } else {
        // Error en la inserción (puede ser por email duplicado)
        if ($mysqli->errno === 1062) {
             die("Error: El correo electrónico ya está registrado.");
        } else {
            die("Error en el registro: " . $stmt->error);
        }
    }

    $stmt->close();
    $mysqli->close();
} else {
    // Si alguien intenta acceder al archivo directamente
    header("location: login.php");
    exit;
}
?>