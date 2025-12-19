<?php
// Este archivo se puede ejecutar vía cron job diariamente
require_once 'config.php';
require_once 'agente_orquestador.php';

$orquestador = new AgenteOrquestador($mysqli);
$resultado = $orquestador->ejecutarAgente('feedback_no_seleccionados', []);

echo "Procesados: " . ($resultado['procesados'] ?? 0) . " candidatos rechazados\n";
?>