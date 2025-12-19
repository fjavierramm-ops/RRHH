# 📘 GUÍA COMPLETA DE INTEGRACIÓN DE AGENTES AL SISTEMA

## 🎯 RESUMEN EJECUTIVO

Esta guía detalla paso a paso cómo integrar los agentes XML especificados en el sistema de reclutamiento existente. El sistema actual ya tiene implementaciones básicas de algunos agentes, y esta guía muestra cómo mejorarlos y agregar los nuevos.

---

## 📊 ANÁLISIS DE COMPATIBILIDAD DE AGENTES

### ✅ AGENTES COMPATIBLES Y ESTADO ACTUAL

| Agente | Estado Actual | Acción Requerida | Prioridad |
|--------|---------------|------------------|-----------|
| **Agente de Calendarización** | ✅ Ya implementado (`api_agente.php`) | Mejorar con funcionalidades del XML | ALTA |
| **Agente de Candidatos No Seleccionados** | ❌ No existe | Crear nuevo archivo | ALTA |
| **Agente de Detección de Riesgos** | ✅ Ya existe (`agente_deteccion_riesgos.php`) | Mejorar integración | MEDIA |
| **Agente de Segmentación** | ✅ Ya existe (`procesar_fit.php`) | Mejorar algoritmo | MEDIA |
| **Agente Seguimiento Post-Entrevista** | ⚠️ Parcial (`post-entrevista.php`) | Extender funcionalidad | ALTA |
| **Agente Seguimiento Ofertas** | ⚠️ Parcial (`crear-vacante.php`) | Agregar seguimiento | MEDIA |
| **Agente Redacción IA** | ⚠️ Parcial (`crear-vacante.php`) | Agregar generación IA | MEDIA |
| **Agente Seguimiento Ingreso** | ✅ Ya existe (`agente_seguimiento_ingreso.php`) | Verificar integración | BAJA |
| **Agente Validación Proceso** | ✅ Ya existe (`agente_validacion_proceso.php`) | Verificar integración | BAJA |

### ❌ AGENTES NO COMPATIBLES (SOBRAN)

| Agente | Razón |
|--------|-------|
| **Agente de Diseño Visual para Vacantes** | El sistema no tiene funcionalidad de diseño gráfico. Este agente requiere herramientas de diseño (Canva, Figma, Adobe) que no están integradas. |

---

## 🔧 INTEGRACIÓN PASO A PASO

### **1. AGENTE DE CALENDARIZACIÓN (Mejora del existente)**

**Archivo actual:** `api_agente.php`  
**Archivo XML:** `Agente de Calendarización de Entrevistas Automatizadas para Procesos de Reclutamiento.xml`

#### **¿Qué hace el agente?**
- Encuentra horarios disponibles entre candidato y reclutador
- Genera propuestas de horarios con 3 fases (Coincidencia → Candidato Flexible → Extendido)
- Guarda propuestas en base de datos
- Registra comunicaciones en `canal_comunicacion`

#### **Modificaciones necesarias:**

**PASO 1.1:** Agregar integración con Google Calendar (opcional)

```php
// En api_agente.php, línea 213, reemplazar función programarEnGoogleCalendar()
function programarEnGoogleCalendar($slot, $detalles) {
    // TODO: Implementar integración real con Google Calendar API
    // Por ahora retorna true (simulación)
    
    // Código futuro:
    // require_once 'vendor/autoload.php'; // Si usas Google API Client
    // $client = new Google_Client();
    // $client->setAuthConfig('credentials.json');
    // $client->addScope(Google_Service_Calendar::CALENDAR);
    // $service = new Google_Service_Calendar($client);
    // ... crear evento
    
    return true;
}
```

**PASO 1.2:** Agregar soporte para múltiples canales de comunicación

```php
// Agregar después de línea 193 (función guardarLogComunicacion)
function enviarNotificacionMulticanal($conn, $idCliente, $mensaje, $canal_preferido = 'Email') {
    // Determinar canal preferido del candidato
    $sql_pref = "SELECT canal_preferido FROM candidatos WHERE id = ?";
    $stmt_pref = $conn->prepare($sql_pref);
    $stmt_pref->bind_param("i", $idCliente);
    $stmt_pref->execute();
    $result_pref = $stmt_pref->get_result();
    $pref = $result_pref->fetch_assoc();
    $canal = $pref['canal_preferido'] ?? $canal_preferido;
    
    // Guardar log según canal
    guardarLogComunicacion($conn, $idCliente, $mensaje, $canal, 'Enviado');
    
    // TODO: Implementar envío real según canal:
    // - Email: usar mail() o PHPMailer
    // - WhatsApp: usar API de WhatsApp Business
    // - SMS: usar API de Twilio o similar
}
```

**PASO 1.3:** Mejorar la función de generación de horarios extendidos

```php
// Reemplazar función generarHorariosExtendidos() (línea 118)
function generarHorariosExtendidos($idReclutador = null) {
    $slots = [];
    $today = new DateTime('today');
    
    // Si hay reclutador, usar su disponibilidad como base
    if ($idReclutador) {
        global $conn;
        $dispRec = obtenerDisponibilidadReclutador($conn, $idReclutador);
        // Generar slots basados en disponibilidad del reclutador
        foreach ($dispRec as $disp) {
            $slots = array_merge($slots, generarSubHorarios(
                $disp['fecha'], 
                $disp['hora_inicio'], 
                $disp['hora_fin']
            ));
        }
    } else {
        // Generar horarios genéricos (8:00-20:00, próximos 5 días)
        for ($i = 0; $i < 5; $i++) {
            $date = (clone $today)->modify("+$i day");
            $dayOfWeek = $date->format('w');
            if ($dayOfWeek == 0 || $dayOfWeek == 6) continue;
            $fecha = $date->format('Y-m-d');
            $slots = array_merge($slots, generarSubHorarios($fecha, '08:00:00', '20:00:00'));
        }
    }
    
    return $slots;
}
```

**PASO 1.4:** Agregar tabla para preferencias de canal (si no existe)

```sql
-- Ejecutar en MySQL
ALTER TABLE candidatos 
ADD COLUMN canal_preferido VARCHAR(20) DEFAULT 'Email' 
AFTER telefono;

-- Valores posibles: 'Email', 'WhatsApp', 'SMS', 'Llamada'
```

#### **Cómo verificar que funciona:**

1. **Test básico:**
   - Ir a `disponibilidades.php`
   - Hacer clic en botón "🤖 IA" de una disponibilidad
   - Seleccionar reclutador y vacante
   - Verificar que aparezca propuesta de horario

2. **Test de fases:**
   - Rechazar 3 propuestas consecutivas
   - Verificar que aparezca mensaje de "Candidato_Extendido"
   - Confirmar que se genere horario fuera de disponibilidad original

3. **Verificar logs:**
   - Ir a `logs-comunicacion.php`
   - Buscar registros con canal "Calendar"
   - Verificar que se registren las confirmaciones

---

### **2. AGENTE DE CANDIDATOS NO SELECCIONADOS (Nuevo)**

**Archivo a crear:** `agente_feedback_no_seleccionados.php`  
**Archivo XML:** `Agente de Candidatos No Seleccionados.xml`

#### **¿Qué hace el agente?**
- Detecta candidatos rechazados automáticamente
- Genera mensajes personalizados de feedback
- Envía feedback por email
- Guarda feedback en base de datos

#### **PASO 2.1:** Crear tabla de feedback (si no existe)

```sql
-- Ejecutar en MySQL
CREATE TABLE IF NOT EXISTS feedback_rechazo (
    id_feedback INT AUTO_INCREMENT PRIMARY KEY,
    id_aplicacion INT NOT NULL,
    id_candidato INT NOT NULL,
    mensaje_generado TEXT,
    razones_rechazo JSON,
    sugerencias_mejora TEXT,
    estado_envio ENUM('pendiente', 'enviado', 'fallido') DEFAULT 'pendiente',
    fecha_envio DATETIME NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_aplicacion) REFERENCES aplicaciones(id_aplicacion),
    FOREIGN KEY (id_candidato) REFERENCES candidatos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### **PASO 2.2:** Crear archivo del agente

```php
<?php
// agente_feedback_no_seleccionados.php
require_once 'config.php';

class AgenteFeedbackNoSeleccionados {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    /**
     * Procesa candidatos rechazados y genera feedback
     */
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
                WHERE a.status_aplicacion = 'Rechazado'
                  AND f.id_feedback IS NULL
                LIMIT 10"; // Procesar en lotes
        
        $result = $this->mysqli->query($sql);
        
        $procesados = 0;
        while ($row = $result->fetch_assoc()) {
            $this->generarYEnviarFeedback($row);
            $procesados++;
        }
        
        return ['success' => true, 'procesados' => $procesados];
    }
    
    /**
     * Genera y envía feedback personalizado
     */
    private function generarYEnviarFeedback($datos) {
        $mensaje = $this->generarMensaje($datos);
        $sugerencias = $this->generarSugerencias($datos);
        $razones = $this->identificarRazones($datos);
        
        // Guardar en base de datos
        $sql = "INSERT INTO feedback_rechazo 
                (id_aplicacion, id_candidato, mensaje_generado, razones_rechazo, sugerencias_mejora, estado_envio)
                VALUES (?, ?, ?, ?, ?, 'pendiente')";
        
        $stmt = $this->mysqli->prepare($sql);
        $razones_json = json_encode($razones);
        $stmt->bind_param("iisss", 
            $datos['id_aplicacion'],
            $datos['id_candidato'],
            $mensaje,
            $razones_json,
            $sugerencias
        );
        $stmt->execute();
        $id_feedback = $stmt->insert_id;
        
        // Enviar email
        $enviado = $this->enviarEmail($datos['email'], $mensaje);
        
        // Actualizar estado
        if ($enviado) {
            $sql_update = "UPDATE feedback_rechazo 
                          SET estado_envio = 'enviado', fecha_envio = NOW() 
                          WHERE id_feedback = ?";
            $stmt_update = $this->mysqli->prepare($sql_update);
            $stmt_update->bind_param("i", $id_feedback);
            $stmt_update->execute();
        }
    }
    
    /**
     * Genera mensaje personalizado
     */
    private function generarMensaje($datos) {
        $mensaje = "Hola " . $datos['nombre'] . ",\n\n";
        $mensaje .= "Gracias por tu interés en la posición de " . $datos['vacante'] . ".\n\n";
        $mensaje .= "Después de revisar cuidadosamente tu perfil y aplicación, lamentamos informarte que en esta ocasión no podremos avanzar con tu candidatura.\n\n";
        
        if ($datos['score_global']) {
            $mensaje .= "Tu evaluación general fue de " . $datos['score_global'] . "%.\n";
        }
        
        $mensaje .= "\nAgradecemos el tiempo que invertiste en aplicar y te deseamos éxito en tu búsqueda profesional.\n\n";
        $mensaje .= "Saludos cordiales,\nEquipo de RRHH";
        
        return $mensaje;
    }
    
    /**
     * Genera sugerencias de mejora
     */
    private function generarSugerencias($datos) {
        $sugerencias = [];
        
        if ($datos['score_tecnico'] && $datos['score_tecnico'] < 50) {
            $sugerencias[] = "Considera fortalecer tus habilidades técnicas relacionadas con el puesto";
        }
        
        if ($datos['score_blando'] && $datos['score_blando'] < 50) {
            $sugerencias[] = "Desarrolla más tus habilidades blandas y de comunicación";
        }
        
        if (empty($sugerencias)) {
            $sugerencias[] = "Continúa desarrollando tu experiencia y habilidades";
        }
        
        return implode(". ", $sugerencias);
    }
    
    /**
     * Identifica razones de rechazo
     */
    private function identificarRazones($datos) {
        $razones = [];
        
        if ($datos['score_global'] && $datos['score_global'] < 30) {
            $razones[] = "Score de evaluación muy bajo";
        }
        
        if ($datos['score_tecnico'] && $datos['score_tecnico'] < 40) {
            $razones[] = "Habilidades técnicas insuficientes";
        }
        
        if (empty($razones)) {
            $razones[] = "No cumple con los requisitos mínimos del puesto";
        }
        
        return $razones;
    }
    
    /**
     * Envía email (simulación - implementar con PHPMailer o similar)
     */
    private function enviarEmail($email, $mensaje) {
        // TODO: Implementar envío real de email
        // Por ahora solo simulación
        error_log("Email enviado a: $email");
        return true;
    }
}
?>
```

#### **PASO 2.3:** Integrar con el orquestador

**Modificar `agente_orquestador.php`:**

```php
// Agregar en el switch case (después de línea 53)
case 'feedback_no_seleccionados':
    require_once 'agente_feedback_no_seleccionados.php';
    $agente = new AgenteFeedbackNoSeleccionados($this->mysqli);
    return $agente->procesarRechazados();
```

#### **PASO 2.4:** Crear script de ejecución automática

**Crear `procesar_feedback_automatico.php`:**

```php
<?php
// Este archivo se puede ejecutar vía cron job diariamente
require_once 'config.php';
require_once 'agente_orquestador.php';

$orquestador = new AgenteOrquestador($mysqli);
$resultado = $orquestador->ejecutarAgente('feedback_no_seleccionados', []);

echo "Procesados: " . ($resultado['procesados'] ?? 0) . " candidatos rechazados\n";
?>
```

#### **Cómo verificar que funciona:**

1. **Crear candidato de prueba rechazado:**
   ```sql
   -- En MySQL
   UPDATE aplicaciones 
   SET status_aplicacion = 'Rechazado' 
   WHERE id_aplicacion = [ID_DE_APLICACION_EXISTENTE];
   ```

2. **Ejecutar agente:**
   - Acceder a: `http://localhost/dashboard/Maqueta/procesar_feedback_automatico.php`
   - O ejecutar desde línea de comandos: `php procesar_feedback_automatico.php`

3. **Verificar resultados:**
   ```sql
   SELECT * FROM feedback_rechazo ORDER BY fecha_creacion DESC LIMIT 5;
   ```

4. **Verificar en logs:**
   - Ir a `logs-comunicacion.php`
   - Buscar mensajes de feedback enviados

---

### **3. AGENTE DE SEGUIMIENTO POST-ENTREVISTA (Mejora del existente)**

**Archivo actual:** `post-entrevista.php`  
**Archivo XML:** `Agente De Seguimiento Post-Entrevista.xml`

#### **¿Qué hace el agente?**
- Envía comunicación automática después de entrevista
- Recolecta feedback de entrevistador
- Recolecta feedback del candidato
- Genera alertas de retrasos

#### **PASO 3.1:** Crear tabla para feedback de entrevistas (si no existe)

```sql
CREATE TABLE IF NOT EXISTS feedback_entrevista (
    id_feedback INT AUTO_INCREMENT PRIMARY KEY,
    id_entrevista INT NOT NULL,
    tipo ENUM('entrevistador', 'candidato') NOT NULL,
    feedback_texto TEXT,
    calificacion INT DEFAULT 0,
    fecha_feedback DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_entrevista) REFERENCES entrevistas(id_entrevista)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### **PASO 3.2:** Modificar `post-entrevista.php` para agregar funcionalidad

**Agregar después de línea 87:**

```php
// Obtener feedback existente
$feedback_entrevistador = null;
$feedback_candidato = null;

$sql_feedback = "SELECT tipo, feedback_texto, calificacion 
                 FROM feedback_entrevista 
                 WHERE id_entrevista = ?";
$stmt_feedback = $conn->prepare($sql_feedback);
$stmt_feedback->bind_param("i", $idEntrevista);
$stmt_feedback->execute();
$result_feedback = $stmt_feedback->get_result();

while ($fb = $result_feedback->fetch_assoc()) {
    if ($fb['tipo'] === 'entrevistador') {
        $feedback_entrevistador = $fb;
    } else {
        $feedback_candidato = $fb;
    }
}
```

**Agregar procesamiento de feedback (después de línea 48):**

```php
// Procesar feedback de entrevistador
if (isset($_POST['guardar_feedback_entrevistador'])) {
    $feedback_texto = $_POST['feedback_texto'] ?? '';
    $calificacion = intval($_POST['calificacion'] ?? 0);
    
    $sql_fb = "INSERT INTO feedback_entrevista 
               (id_entrevista, tipo, feedback_texto, calificacion)
               VALUES (?, 'entrevistador', ?, ?)
               ON DUPLICATE KEY UPDATE 
               feedback_texto = VALUES(feedback_texto),
               calificacion = VALUES(calificacion)";
    
    $stmt_fb = $conn->prepare($sql_fb);
    $stmt_fb->bind_param("isi", $idEntrevista, $feedback_texto, $calificacion);
    $stmt_fb->execute();
    
    $_SESSION['mensaje_exito'] = "✅ Feedback de entrevistador guardado.";
    header("Location: post-entrevista.php?idEntrevista=$idEntrevista");
    exit;
}
```

#### **PASO 3.3:** Crear archivo para envío automático post-entrevista

**Crear `agente_seguimiento_post_entrevista.php`:**

```php
<?php
require_once 'config.php';

class AgenteSeguimientoPostEntrevista {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    /**
     * Envía comunicación automática después de entrevista
     */
    public function enviarComunicacionPostEntrevista($id_entrevista) {
        // Obtener datos de la entrevista
        $sql = "SELECT 
                    e.id_entrevista,
                    e.fecha,
                    e.hora_inicio,
                    c.nombre AS candidato,
                    c.email,
                    v.titulo AS vacante
                FROM entrevistas e
                JOIN candidatos c ON e.idClientes = c.id
                JOIN vacantes v ON e.idVacante = v.id_vacante
                WHERE e.id_entrevista = ?";
        
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("i", $id_entrevista);
        $stmt->execute();
        $datos = $stmt->get_result()->fetch_assoc();
        
        if (!$datos) {
            return ['success' => false, 'mensaje' => 'Entrevista no encontrada'];
        }
        
        // Generar mensaje
        $mensaje = "Hola " . $datos['candidato'] . ",\n\n";
        $mensaje .= "Gracias por participar en la entrevista para la vacante: " . $datos['vacante'] . "\n\n";
        $mensaje .= "Tu entrevista fue programada para el " . date('d/m/Y', strtotime($datos['fecha'])) . 
                   " a las " . substr($datos['hora_inicio'], 0, 5) . ".\n\n";
        $mensaje .= "Nos pondremos en contacto contigo pronto con los resultados.\n\n";
        $mensaje .= "Saludos,\nEquipo de RRHH";
        
        // Guardar en canal_comunicacion
        $sql_log = "INSERT INTO canal_comunicacion 
                   (idClientes, tipo_origen, id_origen, tipo_destino, id_destino, canal, fecha, hora, mensaje, estado, automatica)
                   VALUES (?, 'Sistema', 1, 'Candidato', ?, 'Email', CURDATE(), CURTIME(), ?, 'Enviado', 1)";
        
        $stmt_log = $this->mysqli->prepare($sql_log);
        $idCliente = $this->obtenerIdCliente($id_entrevista);
        $stmt_log->bind_param("iis", $idCliente, $idCliente, $mensaje);
        $stmt_log->execute();
        
        // TODO: Enviar email real
        // mail($datos['email'], 'Gracias por tu entrevista', $mensaje);
        
        return ['success' => true, 'mensaje' => 'Comunicación enviada'];
    }
    
    private function obtenerIdCliente($id_entrevista) {
        $sql = "SELECT idClientes FROM entrevistas WHERE id_entrevista = ?";
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("i", $id_entrevista);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['idClientes'] ?? null;
    }
}
?>
```

#### **PASO 3.4:** Integrar con el flujo de entrevistas

**Modificar `disponibilidades.php` o `administrador.php` para llamar al agente después de crear entrevista:**

```php
// Después de crear entrevista exitosamente (en administrador.php, línea 82)
if ($ok1) {
    // Llamar al agente de seguimiento post-entrevista
    require_once 'agente_seguimiento_post_entrevista.php';
    $agente_seguimiento = new AgenteSeguimientoPostEntrevista($conn);
    $agente_seguimiento->enviarComunicacionPostEntrevista($conn->insert_id);
    
    // ... resto del código
}
```

#### **Cómo verificar que funciona:**

1. **Crear entrevista:**
   - Ir a `administrador.php` o `disponibilidades.php`
   - Programar una entrevista nueva
   - Verificar que se cree correctamente

2. **Verificar comunicación:**
   - Ir a `logs-comunicacion.php`
   - Buscar mensaje de "Gracias por participar en la entrevista"
   - Verificar que el estado sea "Enviado"

3. **Verificar feedback:**
   - Ir a `post-entrevista.php?idEntrevista=[ID]`
   - Llenar formulario de feedback
   - Verificar que se guarde en `feedback_entrevista`

---

### **4. AGENTE DE SEGMENTACIÓN (Mejora del existente)**

**Archivo actual:** `procesar_fit.php`  
**Archivo XML:** `AGENTE DE SEGMENTACIÓN.xml`

#### **¿Qué hace el agente?**
- Calcula Fit Score (coincidencia candidato-vacante)
- Clasifica en segmentos A, B, C
- Normaliza habilidades técnicas

#### **PASO 4.1:** Mejorar algoritmo de cálculo de Fit Score

**Modificar `procesar_fit.php` (línea 60-89):**

```php
// Reemplazar cálculo de score técnico (línea 60)
// Mejorar con ponderación de requisitos
$requisitos_array = explode(',', $data['requisitos_vacante']);
$requisitos_array = array_map('trim', array_map('strtolower', $requisitos_array));

$coincidencias_tec = 0;
$total_skills = count($skills);
$total_requisitos = count($requisitos_array);

if ($total_skills > 0 && $total_requisitos > 0) {
    foreach ($skills as $skill) {
        $skill = trim(strtolower($skill));
        foreach ($requisitos_array as $req) {
            // Coincidencia exacta o parcial
            if (strpos($req, $skill) !== false || strpos($skill, $req) !== false) {
                $coincidencias_tec++;
                break; // Contar cada skill solo una vez
            }
        }
    }
    
    // Score = (coincidencias / max(skills, requisitos)) * 100
    $score_tecnico = intval(($coincidencias_tec / max($total_skills, $total_requisitos)) * 100);
    
    // Bonus por tener muchas habilidades relevantes
    if ($coincidencias_tec >= $total_requisitos * 0.7) {
        $score_tecnico = min(100, $score_tecnico + 10);
    }
} else {
    $score_tecnico = 0;
}
```

#### **PASO 4.2:** Agregar clasificación en segmentos A, B, C

**Agregar después de línea 110:**

```php
// Clasificar en segmentos según XML
$segmento = 'C'; // Bajo por defecto
if ($score_global >= 85) {
    $segmento = 'A'; // Alto Potencial
} elseif ($score_global >= 65) {
    $segmento = 'B'; // Coincidencia Media
}

// Actualizar SQL para incluir segmento
$sql_insert = "INSERT INTO evaluaciones 
               (id_aplicacion, score_tecnico, score_blando, score_global, comentarios_tecnicos, clasificacion_fit, segmento) 
               VALUES (?, ?, ?, ?, ?, ?, ?)
               ON DUPLICATE KEY UPDATE 
               score_tecnico = VALUES(score_tecnico),
               score_blando = VALUES(score_blando),
               score_global = VALUES(score_global),
               comentarios_tecnicos = VALUES(comentarios_tecnicos),
               clasificacion_fit = VALUES(clasificacion_fit),
               segmento = VALUES(segmento)";

$stmt_ins = $mysqli->prepare($sql_insert);
$stmt_ins->bind_param("iiissss", $id_aplicacion, $score_tecnico, $score_blando, $score_global, $feedback, $clasificacion, $segmento);
```

#### **PASO 4.3:** Agregar columna segmento a tabla evaluaciones

```sql
ALTER TABLE evaluaciones 
ADD COLUMN segmento CHAR(1) DEFAULT 'C' 
AFTER clasificacion_fit;

-- Valores: 'A' (Alto), 'B' (Medio), 'C' (Bajo)
```

#### **Cómo verificar que funciona:**

1. **Crear nueva aplicación:**
   - Postular un candidato a una vacante
   - Verificar que se ejecute `procesar_fit.php` automáticamente

2. **Verificar segmentación:**
   ```sql
   SELECT id_aplicacion, score_global, segmento, clasificacion_fit 
   FROM evaluaciones 
   ORDER BY fecha_evaluacion DESC LIMIT 10;
   ```

3. **Verificar en admin:**
   - Ir a `admin_evaluacion.php`
   - Verificar que se muestren los segmentos A, B, C

---

### **5. AGENTE DE DETECCIÓN DE RIESGOS (Mejora de integración)**

**Archivo actual:** `agente_deteccion_riesgos.php`  
**Archivo XML:** `Agente de Detección de Riesgos en Procesos de Reclutamiento y Selección.xml`

#### **¿Qué hace el agente?**
- Detecta riesgos en aplicaciones
- Calcula score de riesgo
- Guarda alertas en base de datos

#### **PASO 5.1:** Verificar que se ejecute automáticamente

**Ya está integrado en `postular.php` (línea 87-89).** Solo verificar que funcione:

```php
// Ya existe en postular.php
$resultado_riesgos = $orquestador->ejecutarAgente('deteccion_riesgos', [
    'id_aplicacion' => $id_app_internal
]);
```

#### **PASO 5.2:** Mejorar visualización de riesgos en admin

**Modificar `admin_evaluacion.php` para mostrar más detalles:**

```php
// Después de línea 98, agregar:
<?php
// Obtener riesgos detallados
$sql_riesgos = "SELECT tipo_riesgo, severidad, descripcion, score_riesgo 
                FROM riesgos_detectados 
                WHERE id_aplicacion = ? AND revisado = FALSE 
                ORDER BY score_riesgo DESC";
$stmt_riesgos = $mysqli->prepare($sql_riesgos);
$stmt_riesgos->bind_param("i", $row['id_aplicacion']);
$stmt_riesgos->execute();
$riesgos = $stmt_riesgos->get_result();
?>

<?php if ($riesgos->num_rows > 0): ?>
    <div style="margin-top: 10px;">
        <strong>Riesgos detectados:</strong>
        <ul style="margin: 5px 0; padding-left: 20px;">
            <?php while ($riesgo = $riesgos->fetch_assoc()): ?>
                <li style="color: <?php echo $riesgo['severidad'] === 'alta' ? '#c62828' : '#f59e0b'; ?>;">
                    <?php echo htmlspecialchars($riesgo['descripcion']); ?>
                    (<?php echo $riesgo['severidad']; ?>)
                </li>
            <?php endwhile; ?>
        </ul>
    </div>
<?php endif; ?>
```

#### **Cómo verificar que funciona:**

1. **Crear aplicación con riesgo:**
   - Postular candidato sin CV o con score muy bajo
   - Verificar que se detecte riesgo automáticamente

2. **Verificar en admin:**
   - Ir a `admin_evaluacion.php`
   - Buscar candidatos con alertas de riesgo
   - Verificar que se muestren los detalles

3. **Verificar en base de datos:**
   ```sql
   SELECT * FROM riesgos_detectados 
   WHERE revisado = FALSE 
   ORDER BY score_riesgo DESC;
   ```

---

### **6. AGENTE DE REDACCIÓN IA Y SEGUIMIENTO DE OFERTAS (Mejora de crear-vacante.php)**

**Archivo actual:** `crear-vacante.php`  
**Archivos XML:** 
- `Agente_Redaccion_IA.xml`
- `Agente Seguimiento De Ofertas De Empleo Automatizadas.xml`

#### **¿Qué hace el agente?**
- Genera descripciones de vacantes atractivas usando IA
- Optimiza contenido para SEO y ATS
- Valida lenguaje inclusivo
- Hace seguimiento de ofertas publicadas

#### **PASO 6.1:** Agregar generación automática de descripción

**Crear `agente_redaccion_vacantes.php`:**

```php
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
```

#### **PASO 6.2:** Integrar en `crear-vacante.php`

**Agregar después de línea 26 (antes de guardar):**

```php
// Si no hay descripción, generar automáticamente
if (empty($descripcion) && !$editMode) {
    require_once 'agente_redaccion_vacantes.php';
    $agente_redaccion = new AgenteRedaccionVacantes($conn);
    $descripcion = $agente_redaccion->generarDescripcion([
        'titulo' => $titulo,
        'departamento' => $departamento,
        'tipo' => $tipo,
        'ubicacion' => $ubicacion,
        'requisitos' => $requisitos
    ]);
}
```

#### **PASO 6.3:** Agregar botón "Generar con IA" en formulario

**Modificar `crear-vacante.php` (después de línea 160):**

```html
<div class="mb-3">
    <label class="form-label">Descripción general</label>
    <div class="d-flex justify-content-between mb-2">
        <span></span>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="generarDescripcionIA()">
            🤖 Generar con IA
        </button>
    </div>
    <textarea name="descripcion" id="descripcion" class="form-control" rows="4"
        placeholder="Describe brevemente las responsabilidades del puesto..."><?= htmlspecialchars($vacante['descripcion'] ?? '', ENT_QUOTES) ?></textarea>
</div>

<script>
function generarDescripcionIA() {
    const titulo = document.querySelector('input[name="titulo"]').value;
    const departamento = document.querySelector('select[name="departamento"]').value;
    const tipo = document.querySelector('select[name="tipo"]').value;
    const ubicacion = document.querySelector('input[name="ubicacion"]').value;
    const requisitos = document.querySelector('textarea[name="requisitos"]').value;
    
    if (!titulo || !requisitos) {
        alert('Por favor completa título y requisitos primero');
        return;
    }
    
    // Llamar a endpoint AJAX
    fetch('generar_descripcion_ia.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({titulo, departamento, tipo, ubicacion, requisitos})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('descripcion').value = data.descripcion;
        }
    });
}
</script>
```

#### **PASO 6.4:** Crear endpoint AJAX

**Crear `generar_descripcion_ia.php`:**

```php
<?php
require_once 'config.php';
require_once 'agente_redaccion_vacantes.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$agente = new AgenteRedaccionVacantes($mysqli);
$descripcion = $agente->generarDescripcion($input);

echo json_encode(['success' => true, 'descripcion' => $descripcion]);
?>
```

#### **Cómo verificar que funciona:**

1. **Crear vacante nueva:**
   - Ir a `crear-vacante.php`
   - Llenar título y requisitos
   - Hacer clic en "🤖 Generar con IA"
   - Verificar que se llene la descripción automáticamente

2. **Verificar lenguaje inclusivo:**
   - Crear vacante con título "Desarrollador"
   - Verificar que en la descripción aparezca "desarrollador(a)"

---

### **7. AGENTE DE SEGUIMIENTO DE INGRESO (Verificar integración)**

**Archivo actual:** `agente_seguimiento_ingreso.php`  
**Archivo XML:** `Agente_Seguimiento_Ingreso.xml`

#### **¿Qué hace el agente?**
- Inicia proceso de onboarding
- Gestiona tareas pendientes
- Actualiza estados de tareas

#### **Verificación:**

**Ya está integrado en:**
- `admin_onboarding.php` (línea 9-13)
- `procesar_accion_candidato.php` (línea 42-46)

**Solo verificar que funcione:**

1. **Contratar candidato:**
   - Ir a `admin_evaluacion.php`
   - Hacer clic en "Contratar" de un candidato
   - Verificar que se actualice status a "Contratado"

2. **Iniciar onboarding:**
   - Ir a `admin_onboarding.php`
   - Verificar que aparezca el candidato contratado
   - Iniciar onboarding con fecha de ingreso
   - Verificar que se creen las tareas pendientes

3. **Actualizar tareas:**
   - Cambiar estado de una tarea (Pendiente → En proceso)
   - Verificar que se actualice en base de datos

---

### **8. AGENTE DE VALIDACIÓN DE PROCESO (Verificar integración)**

**Archivo actual:** `agente_validacion_proceso.php`  
**Archivo XML:** `Agente_Validacion_Proceso_RS.xml`

#### **¿Qué hace el agente?**
- Valida que el proceso esté completo
- Verifica entregables
- Genera comentarios de validación

#### **Verificación:**

**Ya está integrado en:**
- `admin_validacion.php` (línea 7-10)

**Solo verificar que funcione:**

1. **Validar proceso:**
   - Ir a `admin_validacion.php`
   - Hacer clic en "Validar Proceso" de una aplicación
   - Verificar que se ejecute la validación

2. **Verificar comentarios:**
   ```sql
   SELECT * FROM comentarios_validacion 
   WHERE autor = 'Sistema (Agente)' 
   ORDER BY fecha_creacion DESC LIMIT 5;
   ```

---

## 📋 CHECKLIST DE INTEGRACIÓN COMPLETA

### **Fase 1: Preparación (1-2 días)**

- [ ] Hacer backup de base de datos
- [ ] Crear tablas nuevas necesarias (feedback_rechazo, feedback_entrevista, etc.)
- [ ] Agregar columnas nuevas (segmento, canal_preferido, etc.)
- [ ] Verificar que todas las foreign keys estén correctas

### **Fase 2: Implementación de Agentes Nuevos (3-5 días)**

- [ ] Crear `agente_feedback_no_seleccionados.php`
- [ ] Crear `agente_seguimiento_post_entrevista.php`
- [ ] Crear `agente_redaccion_vacantes.php`
- [ ] Crear `generar_descripcion_ia.php`
- [ ] Integrar todos en `agente_orquestador.php`

### **Fase 3: Mejoras de Agentes Existentes (2-3 días)**

- [ ] Mejorar `api_agente.php` con multicanal
- [ ] Mejorar `procesar_fit.php` con segmentos A, B, C
- [ ] Mejorar `post-entrevista.php` con feedback estructurado
- [ ] Mejorar `crear-vacante.php` con generación IA

### **Fase 4: Testing (2-3 días)**

- [ ] Probar cada agente individualmente
- [ ] Probar flujo completo end-to-end
- [ ] Verificar logs y base de datos
- [ ] Probar casos edge (datos faltantes, errores, etc.)

### **Fase 5: Documentación y Deployment (1 día)**

- [ ] Documentar cambios realizados
- [ ] Crear guía de uso para administradores
- [ ] Deploy a producción
- [ ] Monitoreo inicial

---

## 🔍 CÓMO VERIFICAR QUE TODO FUNCIONA

### **Test 1: Flujo Completo de Postulación**

1. Candidato se registra → `login.html`
2. Candidato postula → `detalle_vacante.php` → `postular.php`
3. **Verificar automáticamente:**
   - ✅ Se ejecuta agente de segmentación (fit score)
   - ✅ Se ejecuta agente de detección de riesgos
   - ✅ Se crea registro en `evaluaciones`
   - ✅ Se crean registros en `riesgos_detectados` (si hay riesgos)

**Comandos SQL para verificar:**
```sql
-- Ver última aplicación
SELECT * FROM aplicaciones ORDER BY fecha_aplicacion DESC LIMIT 1;

-- Ver evaluación generada
SELECT * FROM evaluaciones WHERE id_aplicacion = [ID_APLICACION];

-- Ver riesgos detectados
SELECT * FROM riesgos_detectados WHERE id_aplicacion = [ID_APLICACION];
```

### **Test 2: Flujo de Entrevista**

1. RRHH programa entrevista → `administrador.php` o `disponibilidades.php`
2. **Verificar automáticamente:**
   - ✅ Se crea registro en `entrevistas`
   - ✅ Se envía comunicación post-entrevista (agente de seguimiento)
   - ✅ Se registra en `canal_comunicacion`

**Comandos SQL:**
```sql
-- Ver última entrevista
SELECT * FROM entrevistas ORDER BY fecha DESC LIMIT 1;

-- Ver comunicación enviada
SELECT * FROM canal_comunicacion 
WHERE mensaje LIKE '%entrevista%' 
ORDER BY fecha DESC, hora DESC LIMIT 5;
```

### **Test 3: Flujo de Rechazo y Feedback**

1. RRHH rechaza candidato → `admin_evaluacion.php` → `procesar_accion_candidato.php`
2. **Verificar automáticamente:**
   - ✅ Se actualiza status a "Rechazado"
   - ✅ Se genera feedback (agente de candidatos no seleccionados)
   - ✅ Se guarda en `feedback_rechazo`
   - ✅ Se envía email (simulado)

**Comandos SQL:**
```sql
-- Ver aplicaciones rechazadas
SELECT * FROM aplicaciones WHERE status_aplicacion = 'Rechazado' ORDER BY fecha_aplicacion DESC LIMIT 5;

-- Ver feedback generado
SELECT * FROM feedback_rechazo ORDER BY fecha_creacion DESC LIMIT 5;
```

### **Test 4: Flujo de Calendarización con IA**

1. RRHH hace clic en "🤖 IA" → `disponibilidades.php` → `api_agente.php`
2. **Verificar:**
   - ✅ Se genera propuesta de horario
   - ✅ Se muestra en interfaz
   - ✅ Candidato puede confirmar o rechazar
   - ✅ Si rechaza, se genera nueva propuesta

**Verificación manual:**
- Ir a `disponibilidades.php`
- Hacer clic en botón "🤖 IA"
- Verificar que aparezca propuesta
- Rechazar y verificar nueva propuesta

---

## 🚨 AGENTES QUE SOBRAN (NO SE PUEDEN USAR)

### **1. Agente de Diseño Visual para Vacantes**

**Razón:** El sistema no tiene funcionalidad de diseño gráfico. Este agente requiere:
- Integración con Canva/Figma/Adobe Creative Suite
- Generación de imágenes/banners
- Sistema de plantillas visuales

**Recomendación:** 
- ❌ **NO integrar** este agente
- ✅ Si en el futuro se necesita diseño visual, crear módulo separado

---

## 📊 RESUMEN DE MODIFICACIONES POR ARCHIVO

### **Archivos a Modificar:**

1. **`api_agente.php`**
   - Agregar función `enviarNotificacionMulticanal()`
   - Mejorar `generarHorariosExtendidos()`
   - Mejorar `programarEnGoogleCalendar()` (opcional)

2. **`agente_orquestador.php`**
   - Agregar case `'feedback_no_seleccionados'`
   - Verificar que todos los casos existan

3. **`procesar_fit.php`**
   - Mejorar algoritmo de cálculo de score
   - Agregar clasificación en segmentos A, B, C
   - Actualizar SQL para incluir columna `segmento`

4. **`post-entrevista.php`**
   - Agregar formulario de feedback de entrevistador
   - Agregar procesamiento de feedback
   - Mostrar feedback existente

5. **`crear-vacante.php`**
   - Agregar botón "Generar con IA"
   - Integrar generación automática de descripción
   - Agregar JavaScript para llamada AJAX

6. **`admin_evaluacion.php`**
   - Mejorar visualización de riesgos
   - Agregar detalles de segmentos A, B, C

7. **`disponibilidades.php`** o **`administrador.php`**
   - Agregar llamada a agente de seguimiento post-entrevista después de crear entrevista

### **Archivos a Crear:**

1. **`agente_feedback_no_seleccionados.php`** (NUEVO)
2. **`agente_seguimiento_post_entrevista.php`** (NUEVO)
3. **`agente_redaccion_vacantes.php`** (NUEVO)
4. **`generar_descripcion_ia.php`** (NUEVO)
5. **`procesar_feedback_automatico.php`** (NUEVO - para cron job)

### **Tablas a Crear/Modificar:**

1. **`feedback_rechazo`** (NUEVA)
2. **`feedback_entrevista`** (NUEVA)
3. **`evaluaciones`** - Agregar columna `segmento`
4. **`candidatos`** - Agregar columna `canal_preferido`

---

## 🎯 PRIORIZACIÓN DE IMPLEMENTACIÓN

### **ALTA PRIORIDAD (Implementar primero):**

1. ✅ **Agente de Candidatos No Seleccionados** - Mejora experiencia del candidato
2. ✅ **Agente de Seguimiento Post-Entrevista** - Comunicación proactiva
3. ✅ **Mejora de Agente de Calendarización** - Ya existe, solo mejorar

### **MEDIA PRIORIDAD:**

4. ✅ **Mejora de Agente de Segmentación** - Mejorar algoritmo
5. ✅ **Agente de Redacción IA** - Mejorar creación de vacantes
6. ✅ **Mejora de Detección de Riesgos** - Mejor visualización

### **BAJA PRIORIDAD (Ya funcionan, solo verificar):**

7. ✅ **Agente de Seguimiento de Ingreso** - Solo verificar
8. ✅ **Agente de Validación de Proceso** - Solo verificar

---

## 📝 NOTAS IMPORTANTES

1. **Siempre hacer backup** antes de modificar archivos
2. **Probar en entorno de desarrollo** antes de producción
3. **Verificar foreign keys** antes de crear nuevas tablas
4. **Usar prepared statements** en todo el código nuevo
5. **Mantener compatibilidad** con el sistema existente
6. **Documentar cambios** en código con comentarios

---

## 🔗 REFERENCIAS

- **Documentación del sistema:** `RH_MASTER.md`
- **Resumen de integración:** `RESUMEN_INTEGRACION_SISTEMA.md`
- **Base de datos:** `recursosh.sql`
- **Configuración:** `config.php`

---

**Guía generada el:** 2025-01-02  
**Versión:** 1.0  
**Autor:** Sistema de Documentación Automática

