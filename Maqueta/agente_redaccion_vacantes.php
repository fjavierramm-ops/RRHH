<?php
require_once 'config.php';

class AgenteRedaccionVacantes {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    /**
     * Genera descripción de vacante usando plantilla inteligente
     */
    public function generarDescripcion($datos_vacante) {
        $titulo = $datos_vacante['titulo'] ?? '';
        $departamento = $datos_vacante['departamento'] ?? '';
        $tipo = $datos_vacante['tipo'] ?? '';
        $ubicacion = $datos_vacante['ubicacion'] ?? '';
        $requisitos = $datos_vacante['requisitos'] ?? '';
        
        // Plantilla base
        $descripcion = "## Oportunidad: $titulo\n\n";
        $descripcion .= "Estamos buscando un(a) **$titulo** para unirse a nuestro equipo de **$departamento**.\n\n";
        
        // Sección de responsabilidades (generada desde requisitos)
        $descripcion .= "### Responsabilidades:\n";
        $requisitos_array = explode(',', $requisitos);
        foreach ($requisitos_array as $req) {
            $req = trim($req);
            if (!empty($req)) {
                $descripcion .= "- $req\n";
            }
        }
        
        $descripcion .= "\n### Requisitos:\n";
        $descripcion .= $requisitos;
        
        $descripcion .= "\n\n### Tipo de contrato: $tipo\n";
        $descripcion .= "### Ubicación: $ubicacion\n";
        
        // Validar lenguaje inclusivo
        $descripcion = $this->validarLenguajeInclusivo($descripcion);
        
        return $descripcion;
    }
    
    /**
     * Valida y corrige lenguaje inclusivo
     */
    private function validarLenguajeInclusivo($texto) {
        // Reemplazar términos no inclusivos
        $reemplazos = [
            'desarrollador' => 'desarrollador(a)',
            'programador' => 'programador(a)',
            'diseñador' => 'diseñador(a)',
            'ingeniero' => 'ingeniero(a)',
        ];
        
        foreach ($reemplazos as $viejo => $nuevo) {
            $texto = str_ireplace($viejo, $nuevo, $texto);
        }
        
        return $texto;
    }
}
?>

