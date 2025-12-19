<?php
// Incluir la configuración y la conexión a la BD, y la función session_start()
// NOTA: Asumo que tu archivo 'config.php' inicia la sesión con session_start()
require_once "config.php";

// Definir variables para los campos del formulario
$email_login = $password_login = "";
$login_err = "";

// Procesar el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Obtener datos y sanitizarlos
    // NOTA: trim() es suficiente, la sanitización con real_escape_string se maneja en el bind_param
    $email_login = trim($_POST["email_login"]);
    $password_login = trim($_POST["password_login"]);

    // 2. Preparar la consulta SQL
    // CORRECCIÓN CLAVE: Usamos 'id' de la tabla 'candidatos'
    $sql = "SELECT id, email, password FROM candidatos WHERE email = ?";

    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("s", $param_email);
        $param_email = $email_login;

        if ($stmt->execute()) {
            $stmt->store_result();

            // 3. Verificar si el email existe
            if ($stmt->num_rows == 1) {
                // Asegúrate de que el primer parámetro en bind_result coincide con la columna 'id' de la BD
                $stmt->bind_result($id, $email, $hashed_password); 
                
                if ($stmt->fetch() && !is_null($hashed_password) && is_string($hashed_password)) {
                    // 4. Verificar la contraseña
                    if (password_verify($password_login, $hashed_password)) {
                        
                        // ¡Inicio de sesión exitoso!
                        // 5. Asignación de la Sesión Estándar
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $id; 
                        $_SESSION["email"] = $email;

                        // Redirigir al dashboard
                        header("location: inicio.php"); 
                        exit;
                        
                    } else {
                        $login_err = "La contraseña que has ingresado no es válida.";
                    }
                }
            } else {
                $login_err = "No existe una cuenta con ese correo electrónico.";
            }
        } else {
            $login_err = "Oops! Algo salió mal. Por favor, inténtalo de nuevo más tarde.";
        }

        $stmt->close();
    }
}
if (!empty($login_err)) {
    // NOTA: Si necesitas mostrar $login_err en login.html, 
    // tendrías que pasar el error como parámetro URL o usar AJAX.
    // Por ahora, solo redirigimos.
    header("location: login.html?error=" . urlencode($login_err));
    exit;
}
?>