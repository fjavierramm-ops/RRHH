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
