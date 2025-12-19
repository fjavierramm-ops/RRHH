<?php 
require_once('config.php');
$conexion = connection();

header('Content-Type: text/html; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        // Guardar password en texto plano (mejorar después con password_hash)
        $stmt = $conexion->prepare("INSERT INTO reclutadores (email, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $password);

        if ($stmt->execute()) {
            $mensaje = "Usuario registrado correctamente. <a href='Admlogin.php'>Inicia sesión aquí</a>";
        } else {
            $error = "Error al registrar: " . $stmt->error;
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
  <title>Registro de Administrador</title>
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
    .btn-register {
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
        <h2>📝 Registro</h2>
        <p>Crea tu cuenta de administrador</p>
      </div>

      <?php if (!empty($mensaje)): ?>
        <div class="alerta">
          <strong>✅ Éxito:</strong> <?= $mensaje ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <div class="alerta error">
          <strong>⚠️ Error:</strong> <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label for="email" class="form-label">Correo electrónico</label>
          <input 
            type="email" 
            id="email" 
            name="email" 
            placeholder="tu@correo.com" 
            required
            autofocus
          >
        </div>

        <div class="form-group">
          <label for="password" class="form-label">Contraseña</label>
          <div class="password-container">
            <input 
              type="password" 
              id="password" 
              name="password" 
              placeholder="Ingresa tu contraseña" 
              required
            >
            <button type="button" class="toggle-password" onclick="togglePassword()" title="Mostrar/Ocultar contraseña">
              👁️
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-register">Registrar</button>
      </form>

      <div class="login-links">
        <a href="Admlogin.php">¿Ya tienes cuenta? Inicia sesión</a>
      </div>
    </div>
  </div>

  <script>
    function togglePassword() {
      const passwordField = document.getElementById('password');
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
