<?php
// session_start() ya se llama en config.php, no es necesario aquí
require_once('config.php');
$conexion = connection(); // Usa la función de config.php

header('Content-Type: text/html; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['correo'] ?? '');
    $password_ingresada = $_POST['contrasena'] ?? '';

    if ($email && $password_ingresada) {
        // Preparar y ejecutar consulta
        $stmt = $conexion->prepare("SELECT * FROM reclutadores WHERE email = ?");
        if (!$stmt) {
            $error = "Error en la consulta: " . $conexion->error;
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado && $resultado->num_rows > 0) {
                $admin = $resultado->fetch_assoc();
                $stored = $admin['password'];

                // 1) Intentar verificar como hash (si está guardado con password_hash)
                $ok = false;
                if (!empty($stored) && password_verify($password_ingresada, $stored)) {
                    $ok = true;
                }

                // 2) Si no, comparar texto plano (solo si tu BD guarda sin hash)
                if (!$ok && $password_ingresada === $stored) {
                    $ok = true;
                }

                if ($ok) {
                    $_SESSION['admin_id'] = $admin['idreclutadores'];
                    $_SESSION['admin_email'] = $admin['email'];
                    header("Location: administrador.php");
                    exit;
                } else {
                    $error = "Contraseña incorrecta";
                }
            } else {
                $error = "Correo no encontrado";
            }

            $stmt->close();
        }
    } else {
        $error = "Completa todos los campos";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar Sesión - Administrador</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .login-container {
      width: 100%;
      max-width: 450px;
    }
    .login-header {
      text-align: center;
      margin-bottom: 30px;
    }
    .login-header h2 {
      margin-bottom: 10px;
    }
    .login-header p {
      color: var(--color-texto);
      font-size: 0.95rem;
      opacity: 0.8;
    }
    .form-group {
      margin-bottom: 20px;
    }
    .form-label {
      display: block;
      font-weight: 500;
      color: var(--color-texto);
      margin-bottom: 8px;
      font-size: 0.95rem;
    }
    .password-container {
      position: relative;
    }
    .toggle-password {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      font-size: 18px;
      color: var(--color-texto);
      padding: 0;
      width: 30px;
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0.6;
      transition: opacity 0.2s;
    }
    .toggle-password:hover {
      opacity: 1;
      color: var(--color-primario);
    }
    .btn-login {
      width: 100%;
      margin-top: 10px;
    }
    .alerta {
      margin-bottom: 20px;
    }
    .alerta.error {
      background: var(--color-error);
      color: #8b0000;
    }
    .login-links {
      text-align: center;
      margin-top: 25px;
      padding-top: 20px;
      border-top: 1px solid var(--color-borde);
    }
    .login-links a {
      color: var(--color-primario);
      text-decoration: none;
      font-weight: 500;
      font-size: 0.9rem;
      margin: 0 10px;
      transition: color 0.2s;
    }
    .login-links a:hover {
      color: var(--color-secundario);
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="card">
      <div class="login-header">
        <h2>🔐 Administrador</h2>
        <p>Inicia sesión en el panel de administración</p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alerta error">
          <strong>⚠️ Error:</strong> <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" autocomplete="off">
        <div class="form-group">
          <label for="correo" class="form-label">Correo electrónico</label>
          <input 
            type="email" 
            id="correo" 
            name="correo" 
            placeholder="tu@correo.com" 
            required
            autofocus
          >
        </div>

        <div class="form-group">
          <label for="contrasena" class="form-label">Contraseña</label>
          <div class="password-container">
            <input 
              type="password" 
              id="contrasena" 
              name="contrasena" 
              placeholder="Ingresa tu contraseña" 
              required
            >
            <button type="button" class="toggle-password" onclick="togglePassword()" title="Mostrar/Ocultar contraseña">
              👁️
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-login">Iniciar Sesión</button>
      </form>

      <div class="login-links">
        <a href="Admregistro.php">¿No tienes cuenta? Regístrate</a><br>
        <a href="login.html">¿Eres candidato? Accede aquí</a>
      </div>
    </div>
  </div>

  <script>
    function togglePassword() {
      const passwordField = document.getElementById('contrasena');
      const button = document.querySelector('.toggle-password');
      if (passwordField.type === 'password') {
        passwordField.type = 'text';
        button.textContent = '🙈';
      } else {
        passwordField.type = 'password';
        button.textContent = '👁️';
      }
    }
  </script>
</body>
</html>
