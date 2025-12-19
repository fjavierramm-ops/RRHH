# Guía Completa de Integración: Sistema "Nueva carpeta" → "Maqueta"

## 📋 Objetivo

Integrar completamente el sistema de "Nueva carpeta" en tu sistema "Maqueta", manteniendo un solo `config.php` y toda la funcionalidad existente.

---

## 🗂️ PARTE 1: MODIFICACIONES A LA BASE DE DATOS

### PASO 1.1: Ejecutar Script SQL

Ejecuta este script completo en tu base de datos `recursosh`:

```sql
-- ============================================
-- TABLA: reclutadores (para panel administrativo)
-- ============================================
CREATE TABLE IF NOT EXISTS `reclutadores` (
  `idreclutadores` int(15) UNSIGNED NOT NULL AUTO_INCREMENT,
  `NombreCompleto` varchar(100) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `estados` varchar(30) NOT NULL DEFAULT 'Activo',
  `roles` varchar(30) NOT NULL DEFAULT 'Reclutadora',
  PRIMARY KEY (`idreclutadores`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- TABLA: disponibilidaddelequipo (disponibilidad candidatos)
-- ============================================
CREATE TABLE IF NOT EXISTS `disponibilidaddelequipo` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `idClientes` int(11) NOT NULL COMMENT 'FK a candidatos.id',
  `dia_semana` varchar(20) NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `fecha_referencia` date DEFAULT NULL,
  `puesto` varchar(100) NOT NULL,
  `estado` varchar(30) DEFAULT 'Disponible',
  PRIMARY KEY (`id`),
  KEY `fk_disponibilidad_candidato` (`idClientes`),
  CONSTRAINT `fk_disponibilidad_candidato` FOREIGN KEY (`idClientes`) 
    REFERENCES `candidatos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- TABLA: disponibilidades_rrhh (disponibilidad reclutadores)
-- ============================================
CREATE TABLE IF NOT EXISTS `disponibilidades_rrhh` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idreclutadores` int(15) UNSIGNED NOT NULL,
  `dia_semana` varchar(20) NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `fecha_referencia` date NOT NULL,
  `puesto` varchar(100) NOT NULL,
  `estado` varchar(30) DEFAULT 'Disponible',
  PRIMARY KEY (`id`),
  KEY `fk_disponibilidad_reclutador` (`idreclutadores`),
  CONSTRAINT `fk_disponibilidad_reclutador` FOREIGN KEY (`idreclutadores`) 
    REFERENCES `reclutadores` (`idreclutadores`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- TABLA: canal_comunicacion (logs de comunicación)
-- ============================================
CREATE TABLE IF NOT EXISTS `canal_comunicacion` (
  `idComunicacion` int(11) NOT NULL AUTO_INCREMENT,
  `idClientes` int(11) NOT NULL COMMENT 'FK a candidatos.id',
  `tipo_origen` varchar(50) NOT NULL,
  `id_origen` int(11) NOT NULL,
  `tipo_destino` varchar(50) NOT NULL,
  `id_destino` int(11) NOT NULL,
  `canal` varchar(30) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `mensaje` text NOT NULL,
  `estado` varchar(20) NOT NULL,
  `automatica` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idComunicacion`),
  KEY `fk_comunicacion_candidato` (`idClientes`),
  CONSTRAINT `fk_comunicacion_candidato` FOREIGN KEY (`idClientes`) 
    REFERENCES `candidatos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- TABLA: resultados_entrevista (resultados post-entrevista)
-- ============================================
CREATE TABLE IF NOT EXISTS `resultados_entrevista` (
  `idResultado` int(11) NOT NULL AUTO_INCREMENT,
  `idEntrevista` int(11) NOT NULL COMMENT 'FK a entrevistas.id_entrevista',
  `resultado` enum('Aceptacion','SiguienteFase','Rechazo') NOT NULL,
  `salario_ofrecido` decimal(10,2) DEFAULT NULL,
  `fecha_siguiente` date DEFAULT NULL,
  `hora_siguiente` time DEFAULT NULL,
  `tipo_entrevista` varchar(100) DEFAULT NULL,
  `feedback_area` varchar(100) DEFAULT NULL,
  `feedback_detalle` text DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`idResultado`),
  KEY `fk_resultado_entrevista` (`idEntrevista`),
  CONSTRAINT `fk_resultado_entrevista` FOREIGN KEY (`idEntrevista`) 
    REFERENCES `entrevistas` (`id_entrevista`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- MODIFICAR TABLA: entrevistas (agregar columnas nuevas)
-- ============================================
-- Agregar columnas nuevas sin afectar las existentes
ALTER TABLE `entrevistas` 
  ADD COLUMN IF NOT EXISTS `idClientes` int(11) DEFAULT NULL COMMENT 'FK a candidatos.id' AFTER `id_entrevista`,
  ADD COLUMN IF NOT EXISTS `idVacante` int(11) DEFAULT NULL COMMENT 'FK a vacantes.id_vacante' AFTER `idClientes`,
  ADD COLUMN IF NOT EXISTS `idReclutador` int(15) UNSIGNED DEFAULT NULL COMMENT 'FK a reclutadores.idreclutadores' AFTER `idVacante`,
  ADD COLUMN IF NOT EXISTS `fecha` date DEFAULT NULL COMMENT 'Fecha final confirmada' AFTER `hora_final`,
  ADD COLUMN IF NOT EXISTS `hora_inicio` time DEFAULT NULL COMMENT 'Hora inicio' AFTER `fecha`,
  ADD COLUMN IF NOT EXISTS `hora_fin` time DEFAULT NULL COMMENT 'Hora fin' AFTER `hora_inicio`,
  ADD COLUMN IF NOT EXISTS `estado` varchar(30) DEFAULT 'Programada' AFTER `hora_fin`,
  ADD COLUMN IF NOT EXISTS `notas` text DEFAULT NULL AFTER `estado`,
  ADD COLUMN IF NOT EXISTS `fecha_creacion` datetime DEFAULT current_timestamp() AFTER `notas`;

-- Agregar índices para las nuevas columnas
ALTER TABLE `entrevistas`
  ADD KEY IF NOT EXISTS `fk_e_cli` (`idClientes`),
  ADD KEY IF NOT EXISTS `fk_e_vac` (`idVacante`),
  ADD KEY IF NOT EXISTS `fk_e_rec` (`idReclutador`);

-- Agregar foreign keys (si no existen)
-- Nota: Si ya existen, estos comandos fallarán, pero no afectará nada
ALTER TABLE `entrevistas`
  ADD CONSTRAINT `fk_e_cli` FOREIGN KEY (`idClientes`) 
    REFERENCES `candidatos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
    
ALTER TABLE `entrevistas`
  ADD CONSTRAINT `fk_e_vac` FOREIGN KEY (`idVacante`) 
    REFERENCES `vacantes` (`id_vacante`) ON DELETE CASCADE ON UPDATE CASCADE;
    
ALTER TABLE `entrevistas`
  ADD CONSTRAINT `fk_e_rec` FOREIGN KEY (`idReclutador`) 
    REFERENCES `reclutadores` (`idreclutadores`) ON DELETE CASCADE ON UPDATE CASCADE;

-- ============================================
-- MODIFICAR TABLA: vacantes (agregar columnas opcionales)
-- ============================================
-- Agregar columnas que usa el nuevo sistema pero no afectan las existentes
ALTER TABLE `vacantes`
  ADD COLUMN IF NOT EXISTS `departamento` varchar(100) DEFAULT NULL AFTER `empresa`,
  ADD COLUMN IF NOT EXISTS `tipo` varchar(50) DEFAULT NULL AFTER `tipo_trabajo`,
  ADD COLUMN IF NOT EXISTS `fechaApertura` date DEFAULT NULL AFTER `fecha_publicacion`,
  ADD COLUMN IF NOT EXISTS `fechaCierre` date DEFAULT NULL AFTER `fechaApertura`,
  ADD COLUMN IF NOT EXISTS `responsable` varchar(150) DEFAULT NULL AFTER `fechaCierre`,
  ADD COLUMN IF NOT EXISTS `fecha_creacion` date DEFAULT NULL AFTER `responsable`;

-- Llenar columnas nuevas con datos existentes (una sola vez)
UPDATE `vacantes` 
SET 
  `departamento` = COALESCE(`departamento`, `empresa`),
  `tipo` = COALESCE(`tipo`, `tipo_trabajo`),
  `fechaApertura` = COALESCE(`fechaApertura`, `fecha_publicacion`),
  `fechaCierre` = COALESCE(`fechaCierre`, `fecha_publicacion`),
  `fecha_creacion` = COALESCE(`fecha_creacion`, `fecha_publicacion`)
WHERE `departamento` IS NULL OR `tipo` IS NULL OR `fechaApertura` IS NULL;
```

---

## 📝 PARTE 2: MODIFICAR ARCHIVO `config.php`

### PASO 2.1: Modificar `Maqueta/config.php`

**Reemplaza TODO el contenido de `config.php` con esto:**

```php
<?php
// Credenciales de la base de datos para XAMPP (configuración por defecto)
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Usuario por defecto de XAMPP/MySQL
define('DB_PASSWORD', '');     // Contraseña por defecto (vacía)
define('DB_NAME', 'recursosh'); // El nombre de tu base de datos

// Conexión a la base de datos (variable global para compatibilidad con sistema existente)
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verificar la conexión
if ($mysqli === false) {
    die("ERROR: No se pudo conectar a la base de datos. " . $mysqli->connect_error);
}

// Establecer el juego de caracteres a utf8mb4
$mysqli->set_charset("utf8mb4");

// Iniciar sesión (necesario para el login)
session_start();

// ============================================
// FUNCIÓN DE COMPATIBILIDAD para sistema "Nueva carpeta"
// ============================================
function connection() {
    global $mysqli;
    // Verificar que la conexión siga activa
    if (!$mysqli->ping()) {
        $mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
        $mysqli->set_charset("utf8mb4");
    }
    return $mysqli;
}
?>
```

---

## 📁 PARTE 3: CREAR NUEVOS ARCHIVOS EN MAQUETA

### PASO 3.1: Crear `Maqueta/helpers_db.php`

Crea este archivo nuevo:

```php
<?php
/**
 * Helpers para compatibilidad entre sistemas
 * Centraliza las consultas adaptadas
 */
require_once('config.php');

/**
 * Obtiene candidatos con formato compatible con sistema nuevo
 */
function obtenerCandidatosActivos($conn) {
    $sql = "SELECT 
                id AS idClientes, 
                nombre AS NombreCompleto,
                email,
                telefono,
                'Candidato' AS roles,
                'Activo' AS estados
            FROM candidatos
            ORDER BY nombre";
    return $conn->query($sql);
}

/**
 * Obtiene vacantes con formato compatible
 */
function obtenerVacantesCompatibles($conn) {
    $sql = "SELECT 
                id_vacante AS idVacante,
                titulo,
                COALESCE(departamento, empresa) AS departamento,
                COALESCE(tipo, tipo_trabajo) AS tipo,
                ubicacion,
                descripcion,
                requisitos,
                CAST(REPLACE(REPLACE(REPLACE(salario, '$', ''), ',', ''), ' ', '') AS UNSIGNED) AS salario,
                COALESCE(fechaApertura, fecha_publicacion) AS fechaApertura,
                COALESCE(fechaCierre, fecha_publicacion) AS fechaCierre,
                COALESCE(responsable, 'RRHH') AS responsable,
                estado
            FROM vacantes
            ORDER BY id_vacante DESC";
    return $conn->query($sql);
}

/**
 * Obtiene reclutadores activos
 */
function obtenerReclutadoresActivos($conn) {
    $sql = "SELECT 
                idreclutadores AS id,
                NombreCompleto AS nombre
            FROM reclutadores 
            WHERE estados = 'Activo'
            ORDER BY NombreCompleto";
    return $conn->query($sql);
}
?>
```

---

## 🔧 PARTE 4: CREAR ARCHIVOS DEL PANEL ADMINISTRATIVO

### PASO 4.1: Crear `Maqueta/Admlogin.php`

Crea este archivo nuevo (basado en "Nueva carpeta/Admlogin.php" pero adaptado):

```php
<?php
session_start();
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
  <title>Iniciar Sesión - Administrador</title>
  <style>
    body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; }
    input { margin: 8px; padding: 8px; width: 200px; }
    .password-container { position: relative; display: inline-block; }
    .toggle-password {
      position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
      cursor: pointer; border: none; background: none; font-size: 16px;
    }
    .msg { margin: 10px 0; }
  </style>
</head>
<body>
  <h2>Iniciar Sesión - Administrador</h2>

  <?php if (!empty($error)) echo "<p class='msg' style='color:red;'>$error</p>"; ?>

  <form method="POST" autocomplete="off">
    <input type="email" name="correo" placeholder="Correo" required><br>

    <div class="password-container">
      <input type="password" id="contrasena" name="contrasena" placeholder="Contraseña" required>
      <button type="button" class="toggle-password" onclick="togglePassword()">👁️</button>
    </div><br>

    <button type="submit">Entrar</button>
  </form>

  <p>¿No tienes cuenta? <a href="Admregistro.php">Regístrate aquí</a></p>
  <p>¿Eres candidato? <a href="login.html">Accede aquí</a></p>

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
```

### PASO 4.2: Crear `Maqueta/Admregistro.php`

Crea este archivo nuevo:

```php
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
  <title>Registro de Administrador</title>
  <style>
    body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; }
    input { margin: 8px; padding: 8px; width: 200px; }
    .password-container {
      position: relative;
      display: inline-block;
    }
    .toggle-password {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      border: none;
      background: none;
      font-size: 16px;
    }
  </style>
</head>
<body>
  <h2>Crear Cuenta - Administrador</h2>

  <?php
  if (!empty($mensaje)) echo "<p style='color:green;'>$mensaje</p>";
  if (!empty($error)) echo "<p style='color:red;'>$error</p>";
  ?>

  <form method="POST">
    <input type="email" name="email" placeholder="Correo" required><br>

    <div class="password-container">
      <input type="password" id="password" name="password" placeholder="Contraseña" required>
      <button type="button" class="toggle-password" onclick="togglePassword()">👁️</button>
    </div><br>

    <button type="submit">Registrar</button>
  </form>

  <p>¿Ya tienes cuenta? <a href="Admlogin.php">Inicia sesión</a></p>

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
```

---

## 📄 PARTE 5: MODIFICAR ARCHIVOS EXISTENTES Y CREAR NUEVOS

Debido a la extensión, voy a darte las modificaciones clave. Los archivos completos los puedes copiar de "Nueva carpeta" y aplicar estos cambios:

### PASO 5.1: Crear `Maqueta/administrador.php`

**Este archivo es muy largo. Las modificaciones clave son:**

1. **Línea 2-3:** Cambiar de:
   ```php
   include("conexion.php");
   $conn = connection();
   ```
   A:
   ```php
   require_once("config.php");
   $conn = connection();
   ```

2. **Línea 193:** Cambiar consulta de clientes:
   ```php
   // ANTES:
   $sql = "SELECT idClientes, NombreCompleto FROM clientes WHERE estados='Activo' ORDER BY NombreCompleto";
   
   // DESPUÉS:
   $sql = "SELECT id AS idClientes, nombre AS NombreCompleto FROM candidatos ORDER BY nombre";
   ```

3. **Línea 207:** Cambiar consulta de reclutadores (ya está bien, solo verificar que la tabla existe)

4. **Línea 227:** Cambiar consulta de vacantes:
   ```php
   // ANTES:
   $sql = "SELECT idVacante, titulo, departamento, tipo, estado FROM vacantes ORDER BY idVacante DESC";
   
   // DESPUÉS:
   $sql = "SELECT id_vacante AS idVacante, titulo, COALESCE(departamento, empresa) AS departamento, COALESCE(tipo, tipo_trabajo) AS tipo, estado FROM vacantes ORDER BY id_vacante DESC";
   ```

5. **Línea 116-120:** Cambiar UPDATE de clientes:
   ```php
   // ANTES:
   $sql = "UPDATE clientes SET NombreCompleto='$nombre', email='$email', telefono='$telefono', roles='$rol', estados='$estado' WHERE email='$email_original'";
   
   // DESPUÉS:
   $sql = "UPDATE candidatos SET nombre='$nombre', email='$email', telefono='$telefono' WHERE email='$email_original'";
   ```

6. **Línea 135-137:** Cambiar INSERT de clientes:
   ```php
   // ANTES:
   $sql = "INSERT INTO clientes (NombreCompleto, email, telefono, roles, estados) VALUES ('$nombre', '$email', '$telefono', '$rol', '$estado')";
   
   // DESPUÉS:
   $sql = "INSERT INTO candidatos (nombre, email, telefono) VALUES ('$nombre', '$email', '$telefono')";
   ```

7. **Línea 76-77:** Cambiar DELETE de clientes:
   ```php
   // ANTES:
   $conn->query("DELETE FROM clientes WHERE REPLACE(LOWER(TRIM(email)), ' ', '') = REPLACE('$email',' ','')");
   
   // DESPUÉS:
   $conn->query("DELETE FROM candidatos WHERE REPLACE(LOWER(TRIM(email)), ' ', '') = REPLACE('$email',' ','')");
   ```

8. **Línea 151-152:** Cambiar COUNT de clientes:
   ```php
   // ANTES:
   $sql1 = "SELECT COUNT(*) AS total FROM clientes WHERE estados='Activo'";
   
   // DESPUÉS:
   $sql1 = "SELECT COUNT(*) AS total FROM candidatos";
   ```

9. **Línea 167:** Cambiar SELECT de clientes:
   ```php
   // ANTES:
   $sql1 = "SELECT NombreCompleto, email, roles, estados FROM clientes";
   
   // DESPUÉS:
   $sql1 = "SELECT nombre AS NombreCompleto, email, 'Candidato' AS roles, 'Activo' AS estados FROM candidatos";
   ```

**Para el resto del archivo, copia el contenido completo de `Nueva carpeta/Nueva carpeta/administrador.php` y aplica estos cambios.**

---

### PASO 5.2: Crear `Maqueta/disponibilidades.php`

**Modificaciones clave:**

1. **Línea 5-6:** Cambiar conexión:
   ```php
   require_once("config.php");
   $conn = connection();
   ```

2. **Línea 325:** Cambiar consulta de clientes:
   ```php
   // ANTES:
   $clientes = $conn->query("SELECT idClientes, NombreCompleto FROM clientes ORDER BY NombreCompleto ASC");
   
   // DESPUÉS:
   $clientes = $conn->query("SELECT id AS idClientes, nombre AS NombreCompleto FROM candidatos ORDER BY nombre ASC");
   ```

3. **Línea 346-352:** Cambiar consulta de disponibilidades:
   ```php
   // ANTES:
   $disponibilidades = $conn->query("
       SELECT d.*, c.NombreCompleto
       FROM disponibilidaddelequipo d
       JOIN clientes c ON c.idClientes = d.idClientes
   ");
   
   // DESPUÉS:
   $disponibilidades = $conn->query("
       SELECT d.*, c.nombre AS NombreCompleto
       FROM disponibilidaddelequipo d
       JOIN candidatos c ON c.id = d.idClientes
   ");
   ```

4. **Línea 355-362:** Cambiar consulta de entrevistas:
   ```php
   // ANTES:
   $entrevistas = $conn->query("
       SELECT e.*, c.NombreCompleto AS candidato, v.titulo AS vacante, r.NombreCompleto AS reclutador
       FROM entrevistas e
       JOIN clientes c ON c.idClientes = e.idClientes
       JOIN vacantes v ON v.idVacante = e.idVacante
       JOIN reclutadores r ON r.idreclutadores = e.idReclutador
   ");
   
   // DESPUÉS:
   $entrevistas = $conn->query("
       SELECT e.*, c.nombre AS candidato, v.titulo AS vacante, r.NombreCompleto AS reclutador
       FROM entrevistas e
       LEFT JOIN candidatos c ON c.id = e.idClientes
       LEFT JOIN vacantes v ON v.id_vacante = e.idVacante
       LEFT JOIN reclutadores r ON r.idreclutadores = e.idReclutador
   ");
   ```

**Copia el resto del archivo de `Nueva carpeta/Nueva carpeta/disponibilidades.php`.**

---

### PASO 5.3: Crear `Maqueta/api_agente.php`

**Modificaciones clave:**

1. **Línea 7-8:** Cambiar conexión:
   ```php
   require_once("config.php");
   $conn = connection();
   ```

2. **Línea 14-28:** Función `obtenerDisponibilidadCandidato` - ya está bien, solo verifica que `idClientes` apunte a `candidatos.id`

3. **Línea 143-163:** Función `slotYaUsado` - verificar que las columnas coincidan con tu tabla `entrevistas`

**Copia el resto del archivo de `Nueva carpeta/Nueva carpeta/api_agente.php`.**

---

### PASO 5.4: Crear `Maqueta/crear-vacante.php`

**Modificaciones clave:**

1. **Línea 2-4:** Cambiar conexión:
   ```php
   require_once("config.php");
   $conn = connection();
   $conn->set_charset("utf8mb4");
   ```

2. **Línea 15:** Cambiar consulta:
   ```php
   // ANTES:
   $res = $conn->query("SELECT * FROM vacantes WHERE idVacante = $id");
   
   // DESPUÉS:
   $res = $conn->query("SELECT * FROM vacantes WHERE id_vacante = $id");
   ```

3. **Línea 42-55:** Adaptar UPDATE:
   ```php
   // Modificar para usar columnas de tu BD
   $stmt = $conn->prepare("
       UPDATE vacantes SET
       titulo=?, 
       empresa=?,  -- departamento se mapea a empresa
       tipo_trabajo=?,  -- tipo se mapea a tipo_trabajo
       ubicacion=?,
       descripcion=?, 
       requisitos=?, 
       salario=?,
       fecha_publicacion=?,  -- fechaApertura se mapea
       estado=?
       WHERE id_vacante=?
   ");
   ```

4. **Línea 59-75:** Adaptar INSERT:
   ```php
   $stmt = $conn->prepare("
       INSERT INTO vacantes
       (titulo, empresa, tipo_trabajo, ubicacion, descripcion, requisitos,
        salario, fecha_publicacion, estado)
       VALUES (?,?,?,?,?,?,?,?,?)
   ");
   ```

**Copia el resto del archivo y adapta los campos del formulario.**

---

### PASO 5.5: Crear `Maqueta/ver-vacante.php`

**Modificaciones similares a `crear-vacante.php`:**

1. Cambiar conexión
2. Cambiar `idVacante` → `id_vacante`
3. Adaptar columnas en SELECT/UPDATE/INSERT

---

### PASO 5.6: Crear `Maqueta/post-entrevista.php`

**Modificaciones clave:**

1. **Línea 3-4:** Cambiar conexión:
   ```php
   require_once("config.php");
   $conn = connection();
   ```

2. **Línea 75-86:** Cambiar consulta:
   ```php
   // ANTES:
   $sql = "SELECT 
               e.idEntrevista,
               c.NombreCompleto AS candidato,
               v.titulo AS vacante
           FROM entrevistas e
           INNER JOIN clientes c ON e.idClientes = c.idClientes
           INNER JOIN vacantes v ON v.idVacante = e.idVacante
           WHERE e.idEntrevista = ?";
   
   // DESPUÉS:
   $sql = "SELECT 
               e.id_entrevista AS idEntrevista,
               c.nombre AS candidato,
               v.titulo AS vacante
           FROM entrevistas e
           LEFT JOIN candidatos c ON c.id = e.idClientes
           LEFT JOIN vacantes v ON v.id_vacante = e.idVacante
           WHERE e.id_entrevista = ?";
   ```

3. **Línea 22-25:** Verificar que `resultados_entrevista.idEntrevista` apunte a `entrevistas.id_entrevista`

**Copia el resto del archivo.**

---

### PASO 5.7: Crear `Maqueta/logs-comunicacion.php`

**Modificaciones clave:**

1. **Línea 2-3:** Cambiar conexión:
   ```php
   require_once("config.php");
   $conn = connection();
   ```

2. **Línea 8-17:** Cambiar consulta:
   ```php
   // ANTES:
   $sql = "SELECT CC.fecha, CC.hora, C.NombreCompleto AS usuario, ...
           FROM canal_comunicacion CC
           INNER JOIN clientes C ON CC.idClientes = C.idClientes";
   
   // DESPUÉS:
   $sql = "SELECT CC.fecha, CC.hora, C.nombre AS usuario, ...
           FROM canal_comunicacion CC
           INNER JOIN candidatos C ON CC.idClientes = C.id";
   ```

**Copia el resto del archivo.**

---

### PASO 5.8: Crear `Maqueta/index_admin.php` (Panel de perfil admin)

Copia `Nueva carpeta/Nueva carpeta/index.php` y renómbralo a `index_admin.php`.

**Modificaciones:**

1. Cambiar conexión a `config.php`
2. Cambiar consulta de reclutadores
3. Cambiar link de `Administrador.php` a `administrador.php`

---

### PASO 5.9: Crear `Maqueta/logout_admin.php`

Crea este archivo simple:

```php
<?php
session_start();
session_unset();
session_destroy();
header('Location: Admlogin.php');
exit();
?>
```

---

## ✅ PARTE 6: CHECKLIST DE VERIFICACIÓN

### Base de Datos
- [ ] Ejecutado script SQL completo
- [ ] Tabla `reclutadores` creada
- [ ] Tabla `disponibilidaddelequipo` creada
- [ ] Tabla `disponibilidades_rrhh` creada
- [ ] Tabla `canal_comunicacion` creada
- [ ] Tabla `resultados_entrevista` creada
- [ ] Columnas nuevas agregadas a `entrevistas`
- [ ] Columnas nuevas agregadas a `vacantes`
- [ ] Foreign keys creadas correctamente

### Archivos Modificados
- [ ] `config.php` modificado con función `connection()`

### Archivos Nuevos Creados
- [ ] `helpers_db.php`
- [ ] `Admlogin.php`
- [ ] `Admregistro.php`
- [ ] `administrador.php`
- [ ] `disponibilidades.php`
- [ ] `api_agente.php`
- [ ] `crear-vacante.php`
- [ ] `ver-vacante.php`
- [ ] `post-entrevista.php`
- [ ] `logs-comunicacion.php`
- [ ] `index_admin.php`
- [ ] `logout_admin.php`

### Pruebas
- [ ] Acceder a `Admlogin.php` funciona
- [ ] Registrar nuevo reclutador funciona
- [ ] Login de reclutador funciona
- [ ] Panel `administrador.php` carga
- [ ] Listar candidatos funciona
- [ ] Listar vacantes funciona
- [ ] Crear vacante funciona
- [ ] Disponibilidades funciona
- [ ] Agente de calendarización funciona

---

## 🚀 PARTE 7: ORDEN DE EJECUCIÓN RECOMENDADO

1. **Backup de BD:** Haz backup de `recursosh`
2. **Ejecutar SQL:** Ejecuta todo el script SQL de PARTE 1
3. **Modificar config.php:** Aplica cambios de PARTE 2
4. **Crear helpers:** Crea `helpers_db.php`
5. **Crear archivos admin:** Crea `Admlogin.php` y `Admregistro.php`
6. **Probar login admin:** Crea un usuario de prueba
7. **Crear archivos principales:** Crea `administrador.php`, `disponibilidades.php`, etc.
8. **Probar funcionalidades:** Una por una
9. **Integrar agentes:** Siguiente paso

---

## ⚠️ NOTAS IMPORTANTES

1. **Sesiones:** El sistema admin usa `$_SESSION['admin_id']`, el sistema candidatos usa `$_SESSION['id']`. Mantén separados.

2. **Rutas:** Todos los archivos están en la misma carpeta `Maqueta/`, así que los `require_once` son relativos.

3. **Compatibilidad:** Las nuevas columnas en `entrevistas` y `vacantes` permiten que ambos sistemas funcionen.

4. **Datos existentes:** Los registros antiguos tendrán NULL en las nuevas columnas, pero no afectará el funcionamiento.

---

**¿Necesitas que detalle algún archivo específico o tienes dudas sobre algún paso?**

