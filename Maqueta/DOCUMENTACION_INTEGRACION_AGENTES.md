# Documentación de Integración de Agentes - Sesión de Trabajo

## Fecha: Diciembre 2024

## Índice
1. [Arquitectura General](#arquitectura-general)
2. [Conexión a Base de Datos](#conexión-a-base-de-datos)
3. [Agente Orquestador](#agente-orquestador)
4. [Agentes Implementados](#agentes-implementados)
5. [Flujos de Trabajo](#flujos-de-trabajo)
6. [Problemas Resueltos](#problemas-resueltos)
7. [Estructura de Archivos](#estructura-de-archivos)

---

## Arquitectura General

### Sistema de Agentes
El sistema utiliza una arquitectura basada en agentes especializados que se comunican a través de un **Agente Orquestador** central. Cada agente tiene responsabilidades específicas y se integra con el sistema existente mediante:

- **Base de datos compartida**: Todos los agentes usan la misma base de datos `recursosh`
- **Configuración centralizada**: Archivo `config.php` para conexiones
- **Orquestador central**: `agente_orquestador.php` coordina la ejecución
- **Logs unificados**: Tabla `log_agentes` para seguimiento

### Diagrama de Flujo
```
Usuario/Interfaz
    ↓
procesar_accion_candidato.php / administrador.php / postular.php
    ↓
AgenteOrquestador
    ↓
Agente Específico (Segmentación, Riesgos, Feedback, etc.)
    ↓
Base de Datos (recursosh)
    ↓
Tablas: aplicaciones, entrevistas, evaluaciones, feedback_rechazo, etc.
```

---

## Conexión a Base de Datos

### Archivo de Configuración: `config.php`

Todos los agentes utilizan el mismo archivo de configuración que proporciona:

```php
// config.php
function connection() {
    // Retorna conexión mysqli a la base de datos 'recursosh'
    // Maneja sesiones unificadas
    session_start(); // Prevención de múltiples session_start()
    // ... configuración de conexión
}
```

**Punto Importante**: El `config.php` maneja `session_start()` de forma centralizada para evitar warnings de múltiples inicializaciones.

### Uso en Agentes

```php
// Ejemplo de uso en cualquier agente
require_once 'config.php';
$conn = connection(); // Obtiene conexión mysqli
// O
$mysqli = connection(); // Dependiendo de la implementación
```

---

## Agente Orquestador

### Archivo: `agente_orquestador.php`

**Propósito**: Coordinar y ejecutar todos los agentes del sistema.

### Estructura Principal

```php
class AgenteOrquestador {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function ejecutarAgente($nombre_agente, $datos = []) {
        // 1. Registrar inicio en log_agentes
        $id_log = $this->registrarInicio($nombre_agente, $datos);
        
        // 2. Ejecutar agente específico según nombre
        switch($nombre_agente) {
            case 'segmentacion':
                $resultado = $this->ejecutarSegmentacion($datos);
                break;
            case 'deteccion_riesgos':
                $resultado = $this->ejecutarDeteccionRiesgos($datos);
                break;
            case 'feedback_rechazo':
                $resultado = $this->ejecutarFeedbackRechazo($datos);
                break;
            case 'feedback_no_seleccionados':
                // Procesa múltiples rechazados automáticamente
                break;
            // ... más casos
        }
        
        // 3. Registrar fin en log_agentes
        $this->registrarFin($id_log, 'completado', $resultado);
        
        return $resultado;
    }
}
```

### Agentes Soportados

| Nombre Agente | Método | Descripción |
|--------------|--------|-------------|
| `segmentacion` | `ejecutarSegmentacion()` | Calcula fit score y segmenta candidatos |
| `deteccion_riesgos` | `ejecutarDeteccionRiesgos()` | Detecta inconsistencias y riesgos |
| `calendarizacion` | `ejecutarCalendarizacion()` | Programa entrevistas automáticamente |
| `feedback_rechazo` | `ejecutarFeedbackRechazo()` | Genera feedback para un rechazo específico |
| `feedback_no_seleccionados` | `procesarRechazados()` | Procesa múltiples rechazados en lote |
| `seguimiento_post_entrevista` | `enviarComunicacionPostEntrevista()` | Envía comunicación después de entrevista |
| `validacion_proceso` | `validar()` | Valida completitud del proceso |
| `seguimiento_ingreso` | `iniciarOnboarding()` | Gestiona proceso de onboarding |

### Registro de Logs

```php
// Registrar inicio
private function registrarInicio($nombre_agente, $datos) {
    $sql = "INSERT INTO log_agentes (id_aplicacion, agente_nombre, estado, datos_entrada) 
            VALUES (?, ?, 'procesando', ?)";
    // ... ejecución
    return $stmt->insert_id;
}

// Registrar fin
private function registrarFin($id_log, $estado, $resultado, $error = null) {
    $sql = "UPDATE log_agentes 
            SET estado = ?, datos_salida = ?, fecha_fin = NOW(), error_mensaje = ? 
            WHERE id_log = ?";
    // ... ejecución
}
```

---

## Agentes Implementados

### 1. Agente de Segmentación

**Archivos**: 
- `procesar_fit.php` (lógica original)
- `agente_orquestador.php` (método `ejecutarSegmentacion()`)

**Propósito**: Calcular el "fit" entre candidato y vacante, asignar segmentos (A, B, C).

**Flujo de Ejecución**:

```php
// Desde postular.php cuando un candidato se postula
require_once 'agente_orquestador.php';
$orquestador = new AgenteOrquestador($mysqli);

$resultado_segmentacion = $orquestador->ejecutarAgente('segmentacion', [
    'id_aplicacion' => $id_app_internal
]);
```

**Lógica de Cálculo**:

```php
// 1. Obtener datos de candidato y vacante
$sql = "SELECT 
            a.id_aplicacion,
            c.habilidades_tecnicas AS skills_candidato,
            c.habilidades_blandas AS soft_candidato,
            v.requisitos AS requisitos_vacante,
            v.descripcion AS descripcion_vacante
        FROM aplicaciones a
        JOIN candidatos c ON a.id_candidato = c.id
        JOIN vacantes v ON a.id_vacante = v.id_vacante
        WHERE a.id_aplicacion = ?";

// 2. Calcular score técnico
// Compara habilidades del candidato vs requisitos de la vacante
$coincidencias_tec = 0;
foreach ($skills as $skill) {
    foreach ($requisitos_array as $req) {
        if (strpos($req, $skill) !== false || strpos($skill, $req) !== false) {
            $coincidencias_tec++;
            break;
        }
    }
}
$score_tecnico = intval(($coincidencias_tec / max($total_skills, $total_requisitos)) * 100);

// 3. Calcular score blando
$score_blando = 50 + ($coincidencias_soft * 10);

// 4. Score global
$score_global = intval(($score_tecnico + $score_blando) / 2);

// 5. Asignar segmento
$segmento = 'C'; // Bajo por defecto
if ($score_global >= 85) {
    $segmento = 'A'; // Alto Potencial
} elseif ($score_global >= 65) {
    $segmento = 'B'; // Coincidencia Media
}
```

**Guardado en Base de Datos**:

```php
// IMPORTANTE: score_global es una columna GENERATED, no se inserta
$sql_insert = "INSERT INTO evaluaciones 
               (id_aplicacion, score_tecnico, score_blando, comentarios_tecnicos, clasificacion_fit, segmento) 
               VALUES (?, ?, ?, ?, ?, ?)
               ON DUPLICATE KEY UPDATE 
               score_tecnico = VALUES(score_tecnico),
               score_blando = VALUES(score_blando),
               comentarios_tecnicos = VALUES(comentarios_tecnicos),
               clasificacion_fit = VALUES(clasificacion_fit),
               segmento = VALUES(segmento)";
```

**Tabla**: `evaluaciones`
- `id_aplicacion` (PK)
- `score_tecnico` (INT)
- `score_blando` (INT)
- `score_global` (GENERATED - calculado automáticamente)
- `segmento` (ENUM: 'A', 'B', 'C')
- `clasificacion_fit` (VARCHAR)

---

### 2. Agente de Detección de Riesgos

**Archivo**: `agente_deteccion_riesgos.php`

**Propósito**: Identificar inconsistencias, información sospechosa y riesgos en aplicaciones.

**Invocación**:

```php
// Desde postular.php después de la segmentación
$resultado_riesgos = $orquestador->ejecutarAgente('deteccion_riesgos', [
    'id_aplicacion' => $id_app_internal
]);
```

**Tipos de Riesgos Detectados**:

```php
// 1. Score muy bajo
if ($score_global !== null && $score_global < 30) {
    $riesgos[] = [
        'tipo' => 'informacion_sospechosa',
        'severidad' => 'alta',
        'descripcion' => 'Score de evaluación extremadamente bajo',
        'evidencia' => 'Score global: ' . $score_global . '%'
    ];
    $score_riesgo_total += 40;
}

// 2. Habilidades técnicas sospechosas o vacías
$palabras_sospechosas = ['no se', 'no me importa', 'nada', 'ninguna', 'sin habilidades'];
$es_sospechoso = false;
foreach ($palabras_sospechosas as $palabra) {
    if (strpos($habilidades_lower, $palabra) !== false) {
        $es_sospechoso = true;
        break;
    }
}

// 3. CV no encontrado
$cv_existe = false;
if (!empty($cv_path)) {
    $ruta_completa = (strpos($cv_path, '/') === 0 || strpos($cv_path, 'C:') === 0) 
        ? $cv_path 
        : __DIR__ . '/' . $cv_path;
    $cv_existe = file_exists($ruta_completa);
}

if (empty($cv_path) || !$cv_existe) {
    $riesgos[] = [
        'tipo' => 'inconsistencia',
        'severidad' => 'media',
        'descripcion' => 'CV no encontrado o no subido',
        'evidencia' => 'Ruta CV: ' . ($cv_path ?: 'No especificada')
    ];
    $score_riesgo_total += 20;
}
```

**Guardado en Base de Datos**:

```php
// Limpiar riesgos previos no revisados
$sql_delete = "DELETE FROM riesgos_detectados WHERE id_aplicacion = ? AND revisado = 0";
// ... ejecución

// Insertar nuevos riesgos
foreach ($riesgos as $riesgo) {
    $sql_insert = "INSERT INTO riesgos_detectados 
                   (id_aplicacion, tipo_riesgo, severidad, descripcion, evidencia, score_riesgo) 
                   VALUES (?, ?, ?, ?, ?, ?)";
    // ... ejecución
}
```

**Tabla**: `riesgos_detectados`
- `id_riesgo` (PK)
- `id_aplicacion` (FK)
- `tipo_riesgo` (VARCHAR)
- `severidad` (ENUM: 'alta', 'media', 'baja')
- `descripcion` (TEXT)
- `evidencia` (TEXT)
- `score_riesgo` (INT)
- `revisado` (BOOLEAN, default: 0)

---

### 3. Agente de Calendarización

**Archivo**: `api_agente.php`

**Propósito**: Programar entrevistas automáticamente usando IA para encontrar horarios disponibles.

**Flujo Principal**:

```php
// 1. Obtener o crear aplicación
function guardarPropuestaIA($conn, $idCliente, $idReclutador, $idVacante, $slot, $estado) {
    // Verificar si existe aplicación
    $sql_app = "SELECT id_aplicacion FROM aplicaciones 
                WHERE id_candidato = ? AND id_vacante = ? LIMIT 1";
    // Si no existe, crear una nueva
    if (!$id_aplicacion) {
        $sql_create_app = "INSERT INTO aplicaciones 
                          (id_candidato, id_vacante, fecha_aplicacion, status_aplicacion) 
                          VALUES (?, ?, NOW(), 'En proceso')";
    }
    
    // 2. Verificar si ya existe entrevista
    $sql_check_interview = "SELECT id_entrevista FROM entrevistas 
                           WHERE id_aplicacion = ? AND idClientes = ? AND idVacante = ? 
                           AND idReclutador = ? AND fecha = ? AND hora_inicio = ? AND hora_fin = ?";
    
    // 3. Si existe, actualizar; si no, insertar
    if ($result_check->num_rows > 0) {
        // UPDATE
        $sql_update = "UPDATE entrevistas SET estado = ? WHERE id_entrevista = ?";
    } else {
        // INSERT
        $sql = "INSERT INTO entrevistas 
               (id_aplicacion, idClientes, idVacante, idReclutador, fecha, hora_inicio, hora_fin, estado)
               VALUES (?,?,?,?,?,?,?,?)";
    }
}
```

**Estrategias de Búsqueda de Horarios**:

```php
function findNextAvailableSlot($conn, $idCliente, $idReclutador, $slotsRechazados = []) {
    // Fase 1: Coincidencia exacta
    // Buscar horarios donde candidato y reclutador tienen disponibilidad coincidente
    
    // Fase 2: Candidato flexible
    // Buscar horarios donde el candidato tiene disponibilidad y el reclutador puede ajustarse
    
    // Fase 3: Horarios extendidos
    // Generar horarios basados en disponibilidad del reclutador
    return generarHorariosExtendidos($conn, $idReclutador, $slotsRechazados);
}
```

**Manejo de Rechazo de Horarios**:

```php
// Cuando el candidato rechaza un horario
if ($accion === 'RECHAZAR') {
    // Agregar a lista de rechazados
    $slotsRechazados[] = ['fecha' => $fecha, 'hora_inicio' => $hora_inicio, 'hora_fin' => $hora_fin];
    
    // Buscar siguiente horario disponible
    $result = findNextAvailableSlot($conn, $idCliente, $idReclutador, $slotsRechazados);
    
    if ($result) {
        $slot = $result['slot'];
        $slotHistory[] = $slot;
        $currentSlotIndex = count($slotHistory) - 1;
        
        // Actualizar campos ocultos del formulario con JavaScript
        echo "<script>";
        echo "document.querySelector('input[name=\"fecha\"]').value = '" . htmlspecialchars($slot['fecha']) . "';";
        echo "document.querySelector('input[name=\"hora_inicio\"]').value = '" . htmlspecialchars($slot['hora_inicio']) . "';";
        // ... más campos
        echo "</script>";
    }
}
```

**Registro en Canal de Comunicación**:

```php
function guardarLogComunicacion($conn, $idCliente, $mensaje, $canal, $estado = 'Enviado') {
    $q = $conn->prepare("
        INSERT INTO canal_comunicacion
        (idClientes, tipo_origen, id_origen, tipo_destino, id_destino, canal, fecha, hora, mensaje, estado, automatica)
        VALUES (?, 'Sistema', 1, 'Candidato', ?, ?, CURDATE(), CURTIME(), ?, ?, 1)
    ");
    // ... ejecución
}
```

**Tabla**: `entrevistas`
- `id_entrevista` (PK)
- `id_aplicacion` (FK) - **RESTRICCIÓN ÚNICA** (solo una entrevista por aplicación)
- `idClientes` (FK)
- `idVacante` (FK)
- `idReclutador` (FK)
- `fecha` (DATE)
- `hora_inicio` (TIME)
- `hora_fin` (TIME)
- `estado` (VARCHAR)
- `notas` (TEXT)

---

### 4. Agente de Feedback de Rechazo

**Archivos**: 
- `agente_feedback_rechazo.php` (rechazo individual desde admin)
- `agente_feedback_no_seleccionados.php` (procesamiento automático en lote)

**Propósito**: Generar y enviar feedback personalizado a candidatos rechazados.

#### 4.1. Rechazo Individual (`agente_feedback_rechazo.php`)

**Invocación**:

```php
// Desde procesar_accion_candidato.php cuando se rechaza un candidato
case 'rechazar':
    // PRIMERO actualizar status (siempre se hace)
    $sql = "UPDATE aplicaciones SET status_aplicacion = 'Rechazado' WHERE id_aplicacion = ?";
    $stmt->execute();
    
    // LUEGO generar feedback (opcional, no bloquea el rechazo)
    $resultado = $orquestador->ejecutarAgente('feedback_rechazo', [
        'id_aplicacion' => $id_aplicacion,
        'razones' => $razones
    ]);
```

**Generación de Mensaje**:

```php
private function generarMensaje($info, $razones) {
    $mensaje = "Hola " . $info['nombre'] . ",\n\n";
    $mensaje .= "Gracias por tu interés en la posición de " . $info['vacante'] . ".\n\n";
    $mensaje .= "Después de revisar cuidadosamente tu perfil y aplicación, lamentamos informarte que en esta ocasión no podremos avanzar con tu candidatura.\n\n";
    
    $mensaje .= "Razones principales:\n";
    foreach ($razones as $razon) {
        $mensaje .= "- " . $razon . "\n";
    }
    
    if ($info['score_global']) {
        $mensaje .= "\nTu evaluación general fue de " . $info['score_global'] . "%.\n";
    }
    
    $mensaje .= "\nAgradecemos el tiempo que invertiste en aplicar y te deseamos éxito en tu búsqueda profesional.\n\n";
    $mensaje .= "Saludos cordiales,\nEquipo de RRHH";
    
    return $mensaje;
}
```

**Guardado con Prevención de Duplicados**:

```php
// Usar ON DUPLICATE KEY UPDATE para evitar duplicados
$sql_insert = "INSERT INTO feedback_rechazo 
              (id_aplicacion, mensaje_generado, razones_rechazo, sugerencias_mejora, estado_envio) 
              VALUES (?, ?, ?, ?, 'pendiente')
              ON DUPLICATE KEY UPDATE 
              mensaje_generado = VALUES(mensaje_generado),
              razones_rechazo = VALUES(razones_rechazo),
              sugerencias_mejora = VALUES(sugerencias_mejora)";
```

**Registro en Canal de Comunicación**:

```php
// Obtener id_candidato desde aplicación
$sql_candidato = "SELECT id_candidato FROM aplicaciones WHERE id_aplicacion = ?";
$id_candidato = $row_candidato['id_candidato'] ?? null;

// Guardar en canal_comunicacion con verificación de duplicados
private function guardarLogComunicacion($conn, $idCliente, $mensaje, $canal, $estado = 'Enviado') {
    // Verificar si ya existe un log similar
    $sql_check = "SELECT id_comunicacion FROM canal_comunicacion 
                 WHERE idClientes = ? AND mensaje = ? AND canal = ? AND fecha = CURDATE()";
    
    if (!$existe) {
        // Insertar solo si no existe
        $q = $conn->prepare("INSERT INTO canal_comunicacion ...");
    }
}
```

#### 4.2. Procesamiento Automático (`agente_feedback_no_seleccionados.php`)

**Invocación**:

```php
// Desde procesar_feedback_automatico.php (ejecutado vía cron)
require_once 'agente_orquestador.php';
$orquestador = new AgenteOrquestador($mysqli);
$resultado = $orquestador->ejecutarAgente('feedback_no_seleccionados', []);
```

**Búsqueda de Rechazados**:

```php
public function procesarRechazados() {
    // Buscar aplicaciones rechazadas sin feedback
    $sql = "SELECT 
                a.id_aplicacion,
                a.id_candidato,
                c.nombre,
                c.email,
                v.titulo AS vacante,
                e.score_global,
                e.score_tecnico,
                e.score_blando
            FROM aplicaciones a
            JOIN candidatos c ON a.id_candidato = c.id
            JOIN vacantes v ON a.id_vacante = v.id_vacante
            LEFT JOIN evaluaciones e ON a.id_aplicacion = e.id_aplicacion
            LEFT JOIN feedback_rechazo f ON a.id_aplicacion = f.id_aplicacion
            WHERE LOWER(TRIM(a.status_aplicacion)) = 'rechazado'
              AND f.id_feedback IS NULL
            LIMIT 10"; // Procesar en lotes
    
    // Procesar cada rechazado
    while ($row = $result->fetch_assoc()) {
        $this->generarYEnviarFeedback($row);
    }
}
```

**Tabla**: `feedback_rechazo`
- `id_feedback` (PK)
- `id_aplicacion` (FK) - **RESTRICCIÓN ÚNICA**
- `mensaje_generado` (TEXT)
- `razones_rechazo` (JSON)
- `sugerencias_mejora` (TEXT)
- `estado_envio` (ENUM: 'pendiente', 'enviado')
- `fecha_envio` (DATETIME)

---

### 5. Agente de Seguimiento Post-Entrevista

**Archivo**: `agente_seguimiento_post_entrevista.php`

**Propósito**: Enviar comunicación automática después de una entrevista programada.

**Invocación**:

```php
// Desde administrador.php después de programar entrevista
if ($ok1 && $id_entrevista_creada) {
    require_once 'agente_orquestador.php';
    $orquestador = new AgenteOrquestador($mysqli);
    $resultado_seguimiento = $orquestador->ejecutarAgente('seguimiento_post_entrevista', [
        'id_entrevista' => $id_entrevista_creada
    ]);
}
```

**Lógica**:

```php
public function enviarComunicacionPostEntrevista($id_entrevista) {
    // 1. Obtener datos de la entrevista
    $sql = "SELECT e.*, c.nombre, c.email, v.titulo AS vacante
            FROM entrevistas e
            JOIN candidatos c ON e.idClientes = c.id
            JOIN vacantes v ON e.idVacante = v.id_vacante
            WHERE e.id_entrevista = ?";
    
    // 2. Generar mensaje
    $mensaje = "Hola " . $datos['nombre'] . ",\n\n";
    $mensaje .= "Confirmamos tu entrevista para el puesto de " . $datos['vacante'] . ".\n\n";
    $mensaje .= "Fecha: " . $datos['fecha'] . "\n";
    $mensaje .= "Hora: " . $datos['hora_inicio'] . " - " . $datos['hora_fin'] . "\n\n";
    $mensaje .= "Te esperamos. Saludos cordiales.";
    
    // 3. Guardar en canal_comunicacion
    $sql_log = "INSERT INTO canal_comunicacion 
               (idClientes, tipo_origen, id_origen, tipo_destino, id_destino, canal, fecha, hora, mensaje, estado, automatica)
               VALUES (?, 'Sistema', 1, 'Candidato', ?, 'Email', CURDATE(), CURTIME(), ?, 'Enviado', 1)";
}
```

---

### 6. Agente de Redacción IA

**Archivo**: `agente_redaccion_vacantes.php`

**Propósito**: Generar descripciones de vacantes usando IA.

**Invocación**:

```php
// Desde crear-vacante.php vía AJAX
// generar_descripcion_ia.php
require_once 'agente_redaccion_vacantes.php';
$agente = new AgenteRedaccionVacantes();
$descripcion = $agente->generarDescripcion($datos_vacante);
echo json_encode(['success' => true, 'descripcion' => $descripcion]);
```

**Lógica de Generación**:

```php
public function generarDescripcion($datos) {
    // 1. Construir descripción estructurada
    $descripcion = "# " . $datos['titulo'] . "\n\n";
    $descripcion .= "## Descripción del Puesto\n";
    $descripcion .= $datos['descripcion'] . "\n\n";
    
    // 2. Validar lenguaje inclusivo
    $descripcion = $this->validarLenguajeInclusivo($descripcion);
    
    // 3. Agregar requisitos
    $descripcion .= "## Requisitos\n";
    $descripcion .= $datos['requisitos'] . "\n\n";
    
    return $descripcion;
}

private function validarLenguajeInclusivo($texto) {
    $reemplazos = [
        'todos los' => 'todas las personas',
        'el candidato' => 'la persona candidata',
        // ... más reemplazos
    ];
    
    foreach ($reemplazos as $buscar => $reemplazar) {
        $texto = str_ireplace($buscar, $reemplazar, $texto);
    }
    
    return $texto;
}
```

---

## Flujos de Trabajo

### Flujo 1: Postulación de Candidato

```
1. Candidato se postula (postular.php)
   ↓
2. Se crea registro en aplicaciones
   ↓
3. Se ejecuta Agente de Segmentación
   - Calcula scores técnico y blando
   - Asigna segmento (A, B, C)
   - Guarda en evaluaciones
   ↓
4. Se ejecuta Agente de Detección de Riesgos
   - Detecta inconsistencias
   - Guarda riesgos en riesgos_detectados
   ↓
5. Si hay riesgos altos, se registra alerta
```

**Código Clave**:

```php
// postular.php
$id_app_internal = $stmt_insert->insert_id;

// Ejecutar agentes automáticamente
require_once 'agente_orquestador.php';
$orquestador = new AgenteOrquestador($mysqli);

// Segmentación
$resultado_segmentacion = $orquestador->ejecutarAgente('segmentacion', [
    'id_aplicacion' => $id_app_internal
]);

// Detección de riesgos
$resultado_riesgos = $orquestador->ejecutarAgente('deteccion_riesgos', [
    'id_aplicacion' => $id_app_internal
]);

// Alerta si hay riesgos altos
if ($resultado_riesgos['success'] && $resultado_riesgos['score_riesgo'] > 70) {
    error_log("ALERTA: Aplicación #$id_app_internal tiene score de riesgo alto");
}
```

### Flujo 2: Rechazo de Candidato

```
1. RRHH rechaza candidato desde admin_evaluacion.php
   ↓
2. procesar_accion_candidato.php recibe acción 'rechazar'
   ↓
3. PRIMERO: Actualiza status_aplicacion = 'Rechazado' (SIEMPRE)
   ↓
4. LUEGO: Ejecuta Agente de Feedback de Rechazo
   - Genera mensaje personalizado
   - Guarda en feedback_rechazo
   - Guarda en canal_comunicacion
   ↓
5. Redirige a admin_evaluacion.php con mensaje de éxito
```

**Código Clave**:

```php
// procesar_accion_candidato.php
case 'rechazar':
    // PRIMERO actualizar el status (esto debe hacerse siempre)
    $sql = "UPDATE aplicaciones SET status_aplicacion = 'Rechazado' WHERE id_aplicacion = ?";
    $stmt->execute();
    
    // LUEGO ejecutar agente (opcional, no bloquea el rechazo)
    try {
        $resultado = $orquestador->ejecutarAgente('feedback_rechazo', [
            'id_aplicacion' => $id_aplicacion,
            'razones' => $razones
        ]);
    } catch (Exception $e) {
        // Si el agente falla, igual se marca como rechazado
        error_log("Error en agente feedback_rechazo: " . $e->getMessage());
    }
    
    header("Location: admin_evaluacion.php?success=candidato_rechazado");
```

### Flujo 3: Programación de Entrevista

```
1. RRHH programa entrevista desde administrador.php
   ↓
2. Verificar si existe aplicación (si no, crear)
   ↓
3. Verificar si ya existe entrevista para esa aplicación
   - Si existe: ACTUALIZAR
   - Si no existe: INSERTAR
   ↓
4. Guardar en canal_comunicacion
   ↓
5. Ejecutar Agente de Seguimiento Post-Entrevista
   ↓
6. Redirigir con mensaje de éxito
```

**Código Clave**:

```php
// administrador.php
// 1. Obtener o crear aplicación
$sql_app = "SELECT id_aplicacion FROM aplicaciones 
            WHERE id_candidato = ? AND id_vacante = ? LIMIT 1";
if (!$id_aplicacion) {
    $sql_create_app = "INSERT INTO aplicaciones 
                      (id_candidato, id_vacante, fecha_aplicacion, status_aplicacion) 
                      VALUES (?, ?, NOW(), 'En proceso')";
}

// 2. Verificar si existe entrevista
$sql_check_entrevista = "SELECT id_entrevista FROM entrevistas WHERE id_aplicacion = ? LIMIT 1";

if ($existe) {
    // ACTUALIZAR
    $sql_update = "UPDATE entrevistas 
                  SET idClientes = ?, idVacante = ?, idReclutador = ?, 
                      fecha = ?, hora_inicio = ?, hora_fin = ?, estado = 'Programada', notas = ? 
                  WHERE id_entrevista = ?";
} else {
    // INSERTAR con ON DUPLICATE KEY UPDATE como respaldo
    $stmt = $conn->prepare("INSERT INTO entrevistas ... ON DUPLICATE KEY UPDATE ...");
}

// 3. Ejecutar seguimiento post-entrevista
if ($ok1 && $id_entrevista_creada) {
    $resultado_seguimiento = $orquestador->ejecutarAgente('seguimiento_post_entrevista', [
        'id_entrevista' => $id_entrevista_creada
    ]);
}
```

### Flujo 4: Procesamiento Automático de Feedback

```
1. Cron job ejecuta procesar_feedback_automatico.php
   ↓
2. Llama a Agente de Feedback No Seleccionados
   ↓
3. Busca aplicaciones con status 'Rechazado' sin feedback
   ↓
4. Para cada una:
   - Genera mensaje personalizado
   - Guarda en feedback_rechazo
   - Guarda en canal_comunicacion
   - Actualiza estado_envio
   ↓
5. Retorna número de procesados
```

**Código Clave**:

```php
// procesar_feedback_automatico.php
require_once 'agente_orquestador.php';
$orquestador = new AgenteOrquestador($mysqli);
$resultado = $orquestador->ejecutarAgente('feedback_no_seleccionados', []);

echo "Procesados: " . ($resultado['procesados'] ?? 0) . " candidatos rechazados\n";
```

---

## Problemas Resueltos

### 1. Foreign Key Constraint: `id_aplicacion` en `entrevistas`

**Problema**: Al intentar insertar una entrevista, fallaba porque no existía `id_aplicacion` en `aplicaciones`.

**Solución**:

```php
// Verificar si existe aplicación
$sql_app = "SELECT id_aplicacion FROM aplicaciones 
            WHERE id_candidato = ? AND id_vacante = ? LIMIT 1";

// Si no existe, crear una nueva
if (!$id_aplicacion) {
    $sql_create_app = "INSERT INTO aplicaciones 
                      (id_candidato, id_vacante, fecha_aplicacion, status_aplicacion) 
                      VALUES (?, ?, NOW(), 'En proceso')";
    // ... ejecución y obtener insert_id
}
```

**Archivos afectados**: `api_agente.php`, `administrador.php`

---

### 2. Duplicate Entry: `id_aplicacion` en `entrevistas`

**Problema**: La tabla `entrevistas` tiene restricción única en `id_aplicacion`, causando errores al intentar crear múltiples entrevistas.

**Solución**:

```php
// Verificar si ya existe entrevista
$sql_check_entrevista = "SELECT id_entrevista FROM entrevistas WHERE id_aplicacion = ? LIMIT 1";

if ($existe) {
    // ACTUALIZAR entrevista existente
    $sql_update = "UPDATE entrevistas SET ... WHERE id_entrevista = ?";
} else {
    // INSERTAR nueva con ON DUPLICATE KEY UPDATE como respaldo
    $stmt = $conn->prepare("INSERT INTO entrevistas ... ON DUPLICATE KEY UPDATE ...");
}
```

**Archivos afectados**: `administrador.php`, `api_agente.php`

---

### 3. UI No Actualiza al Rechazar Horario

**Problema**: Al rechazar un horario propuesto, el formulario no mostraba el nuevo horario.

**Solución**:

```php
// Después de encontrar nuevo horario, actualizar campos ocultos con JavaScript
if ($result) {
    $slot = $result['slot'];
    $slotHistory[] = $slot;
    $currentSlotIndex = count($slotHistory) - 1;
    
    // Actualizar campos del formulario
    echo "<script>";
    echo "document.querySelector('input[name=\"fecha\"]').value = '" . htmlspecialchars($slot['fecha']) . "';";
    echo "document.querySelector('input[name=\"hora_inicio\"]').value = '" . htmlspecialchars($slot['hora_inicio']) . "';";
    echo "document.querySelector('input[name=\"hora_fin\"]').value = '" . htmlspecialchars($slot['hora_fin']) . "';";
    echo "document.querySelector('input[name=\"currentSlotIndex\"]').value = '" . $currentSlotIndex . "';";
    echo "</script>";
}
```

**Archivos afectados**: `api_agente.php`

---

### 4. `score_global` es GENERATED, No Se Inserta

**Problema**: Error al intentar insertar `score_global` en `evaluaciones` porque es una columna calculada.

**Solución**:

```php
// NO incluir score_global en el INSERT
$sql_insert = "INSERT INTO evaluaciones 
               (id_aplicacion, score_tecnico, score_blando, comentarios_tecnicos, clasificacion_fit, segmento) 
               VALUES (?, ?, ?, ?, ?, ?)
               ON DUPLICATE KEY UPDATE 
               score_tecnico = VALUES(score_tecnico),
               score_blando = VALUES(score_blando),
               comentarios_tecnicos = VALUES(comentarios_tecnicos),
               clasificacion_fit = VALUES(clasificacion_fit),
               segmento = VALUES(segmento)";
// score_global se calcula automáticamente por la base de datos
```

**Archivos afectados**: `procesar_fit.php`, `agente_orquestador.php`

---

### 5. Status No Se Actualiza al Rechazar

**Problema**: Al rechazar un candidato, el status no se actualizaba si el agente fallaba.

**Solución**:

```php
// PRIMERO actualizar status (siempre se hace)
$sql = "UPDATE aplicaciones SET status_aplicacion = 'Rechazado' WHERE id_aplicacion = ?";
$stmt->execute();

// LUEGO ejecutar agente (opcional, no bloquea el rechazo)
try {
    $resultado = $orquestador->ejecutarAgente('feedback_rechazo', [...]);
} catch (Exception $e) {
    // Si falla, igual se marca como rechazado
    error_log("Error: " . $e->getMessage());
}
```

**Archivos afectados**: `procesar_accion_candidato.php`

---

### 6. `id_entrevista` NULL en Feedback de Entrevistador

**Problema**: Error al guardar feedback porque `id_entrevista` era NULL.

**Solución**:

```php
// Obtener id_entrevista desde POST o GET
$idEntrevista_fb = intval($_POST['idEntrevista'] ?? $idEntrevista ?? 0);

if ($idEntrevista_fb <= 0) {
    $_SESSION['mensaje_exito'] = "❌ Error: ID de entrevista no válido.";
    header("Location: post-entrevista.php");
    exit;
}

// Usar el ID validado
$stmt_fb->bind_param("isi", $idEntrevista_fb, $feedback_texto, $calificacion);
```

**Archivos afectados**: `post-entrevista.php`

---

## Estructura de Archivos

### Archivos de Agentes

```
Maqueta/
├── agente_orquestador.php          # Coordinador central de agentes
├── agente_deteccion_riesgos.php    # Detecta riesgos e inconsistencias
├── agente_feedback_rechazo.php     # Feedback individual de rechazo
├── agente_feedback_no_seleccionados.php  # Procesamiento automático de rechazados
├── agente_seguimiento_post_entrevista.php  # Comunicación post-entrevista
├── agente_redaccion_vacantes.php   # Generación IA de descripciones
├── agente_seguimiento_ingreso.php # Gestión de onboarding
└── agente_validacion_proceso.php   # Validación de procesos
```

### Archivos de Procesamiento

```
Maqueta/
├── procesar_fit.php                # Lógica de segmentación (legacy)
├── procesar_feedback_automatico.php # Cron job para feedback automático
├── procesar_accion_candidato.php   # Procesa acciones (rechazar, contratar, etc.)
└── generar_descripcion_ia.php      # Endpoint AJAX para descripción IA
```

### Archivos de Interfaz

```
Maqueta/
├── administrador.php               # Panel de administración (programar entrevistas)
├── admin_evaluacion.php            # Evaluación de candidatos
├── post-entrevista.php             # Feedback post-entrevista
├── crear-vacante.php               # Creación/edición de vacantes
└── logs-comunicacion.php           # Visualización de logs
```

### Archivos de API/Agentes Especiales

```
Maqueta/
└── api_agente.php                  # Agente de calendarización (IA)
```

---

## Tablas de Base de Datos

### Tablas Principales

| Tabla | Propósito | Columnas Clave |
|-------|-----------|----------------|
| `aplicaciones` | Aplicaciones de candidatos | `id_aplicacion`, `id_candidato`, `id_vacante`, `status_aplicacion` |
| `entrevistas` | Entrevistas programadas | `id_entrevista`, `id_aplicacion` (UNIQUE), `fecha`, `hora_inicio`, `hora_fin` |
| `evaluaciones` | Scores y segmentación | `id_aplicacion`, `score_tecnico`, `score_blando`, `score_global` (GENERATED), `segmento` |
| `riesgos_detectados` | Riesgos identificados | `id_riesgo`, `id_aplicacion`, `tipo_riesgo`, `severidad`, `score_riesgo` |
| `feedback_rechazo` | Feedback de rechazo | `id_feedback`, `id_aplicacion` (UNIQUE), `mensaje_generado`, `estado_envio` |
| `feedback_entrevista` | Feedback de entrevista | `id_feedback`, `id_entrevista`, `tipo`, `feedback_texto`, `calificacion` |
| `canal_comunicacion` | Logs de comunicación | `id_comunicacion`, `idClientes`, `canal`, `mensaje`, `fecha`, `hora` |
| `log_agentes` | Logs de ejecución de agentes | `id_log`, `id_aplicacion`, `agente_nombre`, `estado`, `datos_entrada`, `datos_salida` |

### Relaciones Importantes

```
aplicaciones (1) ──→ (N) entrevistas
aplicaciones (1) ──→ (1) evaluaciones
aplicaciones (1) ──→ (N) riesgos_detectados
aplicaciones (1) ──→ (1) feedback_rechazo
entrevistas (1) ──→ (N) feedback_entrevista
candidatos (1) ──→ (N) canal_comunicacion
```

---

## Puntos Importantes

### 1. Prevención de Duplicados

Todos los agentes implementan estrategias para evitar duplicados:

- **Verificación previa**: Consultar si existe antes de insertar
- **ON DUPLICATE KEY UPDATE**: Actualizar si existe
- **Verificación en canal_comunicacion**: Evitar logs duplicados

### 2. Manejo de Errores

- Los agentes siempre retornan `['success' => true/false, ...]`
- Los errores se registran en `log_agentes` con `error_mensaje`
- Las operaciones críticas (como actualizar status) se hacen ANTES de ejecutar agentes opcionales

### 3. Transacciones y Consistencia

- No se usan transacciones explícitas, pero se mantiene consistencia:
  - Status se actualiza PRIMERO
  - Agentes se ejecutan DESPUÉS
  - Si un agente falla, el status ya está actualizado

### 4. Restricciones de Base de Datos

- `entrevistas.id_aplicacion`: **UNIQUE** (solo una entrevista por aplicación)
- `feedback_rechazo.id_aplicacion`: **UNIQUE** (solo un feedback por aplicación)
- `evaluaciones.score_global`: **GENERATED** (no se inserta, se calcula)

### 5. Case-Insensitive en Búsquedas

```php
// Siempre usar LOWER(TRIM()) para comparar status
WHERE LOWER(TRIM(a.status_aplicacion)) = 'rechazado'
```

---

## Conclusión

Este sistema de agentes proporciona automatización inteligente para:

- **Segmentación automática** de candidatos
- **Detección proactiva** de riesgos
- **Calendarización inteligente** de entrevistas
- **Feedback automatizado** para rechazados
- **Seguimiento post-entrevista** automático
- **Generación IA** de contenido

Todos los agentes están integrados a través del **Agente Orquestador** y utilizan la misma base de datos y configuración, garantizando consistencia y facilidad de mantenimiento.

---

## Notas Finales

- **Configuración**: Todos los agentes usan `config.php` para conexión
- **Logs**: Todos los agentes registran ejecución en `log_agentes`
- **Comunicación**: Todos los mensajes se registran en `canal_comunicacion`
- **Extensibilidad**: Nuevos agentes se agregan fácilmente al orquestador

---

**Última actualización**: Diciembre 2024
**Versión**: 1.0

