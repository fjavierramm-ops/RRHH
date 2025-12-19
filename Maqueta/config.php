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
