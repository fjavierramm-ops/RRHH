<?php
require_once 'config.php';
require_once 'agente_redaccion_vacantes.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Datos no recibidos']);
    exit;
}

$agente = new AgenteRedaccionVacantes($mysqli);
$descripcion = $agente->generarDescripcion($input);

echo json_encode(['success' => true, 'descripcion' => $descripcion]);
?>

