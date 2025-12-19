# GUÍA: CONECTAR AGENTES CON EL SISTEMA

## 📋 OBJETIVO

Conectar los agentes de **Validación** y **Onboarding** con las vistas existentes `admin_validacion.php` y `admin_onboarding.php`.

---

## PASO 1: MODIFICAR BASE DE DATOS

### SQL a Ejecutar en phpMyAdmin:

```sql
-- Modificar tabla comentarios_validacion
ALTER TABLE comentarios_validacion
ADD COLUMN tipo_validacion ENUM('proceso', 'entregable', 'feedback_cliente') DEFAULT 'proceso' AFTER mensaje,
ADD COLUMN estado_validacion ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente' AFTER tipo_validacion,
ADD COLUMN fecha_limite DATE NULL AFTER estado_validacion;

-- Modificar tabla onboarding
ALTER TABLE onboarding
ADD COLUMN tareas_pendientes JSON NULL AFTER notificaciones_enviadas,
ADD COLUMN fecha_limite_ingreso DATE NULL AFTER tareas_pendientes,
ADD COLUMN recordatorios_enviados INT DEFAULT 0 AFTER fecha_limite_ingreso;
```

---

## PASO 2: CREAR AGENTE DE VALIDACIÓN

### Crear archivo: `agente_validacion_proceso.php`

```php
<?php
require_once 'config.php';

class AgenteValidacionProceso {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function validar($id_aplicacion) {
        // Obtener datos de la aplicación
        $sql = "SELECT 
                    a.id_aplicacion, a.status_aplicacion,
                    c.cv_path, c.habilidades_tecnicas,
                    e.score_global,
                    COUNT(DISTINCT ent.id_entrevista) AS tiene_entrevista
                FROM aplicaciones a
                JOIN candidatos c ON a.id_candidato = c.id
                LEFT JOIN evaluaciones e ON a.id_aplicacion = e.id_aplicacion
                LEFT JOIN entrevistas ent ON a.id_aplicacion = ent.id_aplicacion
                WHERE a.id_aplicacion = ?
                GROUP BY a.id_aplicacion";
        
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("i", $id_aplicacion);
        $stmt->execute();
        $result = $stmt->get_result();
        $datos = $result->fetch_assoc();
        
        if (!$datos) {
            return ['success' => false, 'mensaje' => 'Aplicación no encontrada'];
        }
        
        $validaciones = [];
        
        // Validar CV
        if (empty($datos['cv_path']) || !file_exists($datos['cv_path'])) {
            $validaciones[] = [
                'tipo' => 'entregable',
                'estado' => 'rechazado',
                'descripcion' => 'CV no encontrado',
                'fecha_limite' => date('Y-m-d', strtotime('+3 days'))
            ];
        }
        
        // Validar evaluación
        if (!$datos['score_global'] || $datos['score_global'] < 30) {
            $validaciones[] = [
                'tipo' => 'proceso',
                'estado' => 'rechazado',
                'descripcion' => 'Evaluación incompleta o score muy bajo',
                'fecha_limite' => date('Y-m-d', strtotime('+1 day'))
            ];
        }
        
        // Si todo está bien
        if (empty($validaciones)) {
            $validaciones[] = [
                'tipo' => 'proceso',
                'estado' => 'aprobado',
                'descripcion' => 'Proceso validado correctamente',
                'fecha_limite' => null
            ];
        }
        
        // Guardar en BD
        foreach ($validaciones as $validacion) {
            $sql_insert = "INSERT INTO comentarios_validacion 
                          (id_aplicacion, autor, mensaje, tipo_validacion, estado_validacion, fecha_limite) 
                          VALUES (?, 'Sistema (Agente)', ?, ?, ?, ?)";
            $stmt_insert = $this->mysqli->prepare($sql_insert);
            $fecha_limite = $validacion['fecha_limite'] ?? null;
            $stmt_insert->bind_param("issss", $id_aplicacion, $validacion['descripcion'], 
                $validacion['tipo'], $validacion['estado'], $fecha_limite);
            $stmt_insert->execute();
        }
        
        return ['success' => true, 'validaciones' => $validaciones];
    }
}
?>
```

---

## PASO 3: CREAR AGENTE DE ONBOARDING

### Crear archivo: `agente_seguimiento_ingreso.php`

```php
<?php
require_once 'config.php';

class AgenteSeguimientoIngreso {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function iniciarOnboarding($id_aplicacion, $fecha_ingreso = null) {
        if (!$fecha_ingreso) {
            $fecha_ingreso = date('Y-m-d', strtotime('+7 days'));
        }
        
        $tareas_pendientes = [
            'doc_contratacion' => ['nombre' => 'Documentación', 'estado' => 'Pendiente'],
            'config_equipos' => ['nombre' => 'Config. Equipos', 'estado' => 'Pendiente'],
            'induccion' => ['nombre' => 'Inducción', 'estado' => 'Pendiente'],
            'entrenamiento' => ['nombre' => 'Entrenamiento', 'estado' => 'Pendiente']
        ];
        
        $sql = "INSERT INTO onboarding 
                (id_aplicacion, fecha_ingreso, doc_contratacion, config_equipos, 
                 induccion, entrenamiento, tareas_pendientes, fecha_limite_ingreso) 
                VALUES (?, ?, 'Pendiente', 'Pendiente', 'Pendiente', 'Pendiente', ?, ?)
                ON DUPLICATE KEY UPDATE
                fecha_ingreso = VALUES(fecha_ingreso),
                tareas_pendientes = VALUES(tareas_pendientes),
                fecha_limite_ingreso = VALUES(fecha_limite_ingreso)";
        
        $stmt = $this->mysqli->prepare($sql);
        $tareas_json = json_encode($tareas_pendientes);
        $fecha_limite = date('Y-m-d', strtotime($fecha_ingreso . ' -1 day'));
        $stmt->bind_param("isss", $id_aplicacion, $fecha_ingreso, $tareas_json, $fecha_limite);
        
        if ($stmt->execute()) {
            return ['success' => true, 'fecha_ingreso' => $fecha_ingreso];
        }
        
        return ['success' => false, 'mensaje' => 'Error al iniciar onboarding'];
    }
    
    public function actualizarTarea($id_aplicacion, $tarea_nombre, $nuevo_estado) {
        $sql = "SELECT tareas_pendientes FROM onboarding WHERE id_aplicacion = ?";
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("i", $id_aplicacion);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if (!$row) return ['success' => false];
        
        $tareas = json_decode($row['tareas_pendientes'], true);
        if (isset($tareas[$tarea_nombre])) {
            $tareas[$tarea_nombre]['estado'] = $nuevo_estado;
            
            $sql_update = "UPDATE onboarding SET 
                         tareas_pendientes = ?, $tarea_nombre = ?
                         WHERE id_aplicacion = ?";
            $stmt_update = $this->mysqli->prepare($sql_update);
            $tareas_json = json_encode($tareas);
            $stmt_update->bind_param("ssi", $tareas_json, $nuevo_estado, $id_aplicacion);
            $stmt_update->execute();
            
            return ['success' => true];
        }
        
        return ['success' => false];
    }
}
?>
```

---

## PASO 4: AGREGAR AL ORQUESTADOR

### En `agente_orquestador.php`, agregar en el switch:

```php
case 'validacion_proceso':
    require_once 'agente_validacion_proceso.php';
    $agente = new AgenteValidacionProceso($this->mysqli);
    return $agente->validar($datos['id_aplicacion']);
    
case 'seguimiento_ingreso':
    require_once 'agente_seguimiento_ingreso.php';
    $agente = new AgenteSeguimientoIngreso($this->mysqli);
    $accion = $datos['accion'] ?? 'iniciar';
    if ($accion === 'iniciar') {
        return $agente->iniciarOnboarding($datos['id_aplicacion'], $datos['fecha_ingreso'] ?? null);
    }
    if ($accion === 'actualizar_tarea') {
        return $agente->actualizarTarea($datos['id_aplicacion'], $datos['tarea_nombre'], $datos['nuevo_estado']);
    }
    return ['success' => false, 'mensaje' => 'Acción no reconocida'];
```

---

## PASO 5: MODIFICAR admin_validacion.php

### Agregar al inicio (después de `require_once 'config.php';`):

```php
// Procesar validación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['validar'])) {
    require_once 'agente_orquestador.php';
    $orquestador = new AgenteOrquestador($mysqli);
    $resultado = $orquestador->ejecutarAgente('validacion_proceso', [
        'id_aplicacion' => $_POST['id_aplicacion']
    ]);
    header("Location: admin_validacion.php?validado=" . $_POST['id_aplicacion']);
    exit;
}
```

### Modificar la consulta SQL para incluir validaciones:

```php
$sql = "SELECT 
            a.id_aplicacion, a.fecha_aplicacion, a.status_aplicacion,
            c.nombre AS candidato,
            v.titulo AS vacante, v.empresa AS departamento,
            COUNT(CASE WHEN cv.estado_validacion = 'aprobado' THEN 1 END) AS validaciones_aprobadas,
            COUNT(CASE WHEN cv.estado_validacion = 'pendiente' THEN 1 END) AS validaciones_pendientes
        FROM aplicaciones a
        JOIN candidatos c ON a.id_candidato = c.id
        JOIN vacantes v ON a.id_vacante = v.id_vacante
        LEFT JOIN comentarios_validacion cv ON a.id_aplicacion = cv.id_aplicacion AND cv.autor = 'Sistema (Agente)'
        WHERE 1=1
        GROUP BY a.id_aplicacion";
```

### Modificar columna "Acciones" en la tabla:

```php
<td>
    <button class="btn">Ver detalle</button>
    <form method="POST" style="display: inline;">
        <input type="hidden" name="id_aplicacion" value="<?php echo $row['id_aplicacion']; ?>">
        <input type="hidden" name="validar" value="1">
        <button type="submit" class="btn" style="background-color: #22c55e; color: white;">
            Validar Proceso
        </button>
    </form>
</td>
```

---

## PASO 6: MODIFICAR admin_onboarding.php

### Agregar al inicio (después de `require_once 'config.php';`):

```php
// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'agente_orquestador.php';
    $orquestador = new AgenteOrquestador($mysqli);
    
    if (isset($_POST['iniciar_onboarding'])) {
        $resultado = $orquestador->ejecutarAgente('seguimiento_ingreso', [
            'accion' => 'iniciar',
            'id_aplicacion' => $_POST['id_aplicacion'],
            'fecha_ingreso' => $_POST['fecha_ingreso'] ?? null
        ]);
        header("Location: admin_onboarding.php?iniciado=1");
        exit;
    }
    
    if (isset($_POST['actualizar_tarea'])) {
        $resultado = $orquestador->ejecutarAgente('seguimiento_ingreso', [
            'accion' => 'actualizar_tarea',
            'id_aplicacion' => $_POST['id_aplicacion'],
            'tarea_nombre' => $_POST['tarea_nombre'],
            'nuevo_estado' => $_POST['nuevo_estado']
        ]);
        header("Location: admin_onboarding.php?actualizado=1");
        exit;
    }
}
```

### Modificar la consulta SQL:

```php
$sql = "SELECT 
            a.id_aplicacion,
            c.nombre, v.titulo AS puesto,
            o.id_onboarding, o.fecha_ingreso, 
            o.doc_contratacion, o.config_equipos, o.induccion, o.entrenamiento,
            o.tareas_pendientes, o.fecha_limite_ingreso, o.recordatorios_enviados
        FROM aplicaciones a
        JOIN candidatos c ON a.id_candidato = c.id
        JOIN vacantes v ON a.id_vacante = v.id_vacante
        LEFT JOIN onboarding o ON a.id_aplicacion = o.id_aplicacion
        WHERE a.status_aplicacion = 'Contratado' OR a.status_aplicacion = 'contratado'";
```

### Modificar el botón "Enviar recordatorio":

```php
<?php 
$onboarding_iniciado = !empty($row['id_onboarding']);
$tareas = json_decode($row['tareas_pendientes'] ?? '{}', true) ?? [];
?>

<?php if (!$onboarding_iniciado): ?>
    <form method="POST">
        <input type="hidden" name="id_aplicacion" value="<?php echo $row['id_aplicacion']; ?>">
        <input type="hidden" name="iniciar_onboarding" value="1">
        <label>Fecha ingreso:</label>
        <input type="date" name="fecha_ingreso" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>
        <button type="submit" class="btn btn-green">Iniciar Onboarding</button>
    </form>
<?php else: ?>
    <?php foreach ($tareas as $tarea_key => $tarea): ?>
        <form method="POST" style="display: flex; gap: 5px; margin: 5px 0;">
            <input type="hidden" name="id_aplicacion" value="<?php echo $row['id_aplicacion']; ?>">
            <input type="hidden" name="actualizar_tarea" value="1">
            <input type="hidden" name="tarea_nombre" value="<?php echo $tarea_key; ?>">
            <span style="flex: 1;"><?php echo $tarea['nombre']; ?>:</span>
            <select name="nuevo_estado">
                <option value="Pendiente" <?php echo ($tarea['estado'] === 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                <option value="En proceso" <?php echo ($tarea['estado'] === 'En proceso') ? 'selected' : ''; ?>>En proceso</option>
                <option value="Completado" <?php echo ($tarea['estado'] === 'Completado') ? 'selected' : ''; ?>>Completado</option>
            </select>
            <button type="submit" style="padding: 3px 8px;">Actualizar</button>
        </form>
    <?php endforeach; ?>
<?php endif; ?>
```

---

## PASO 7: AGREGAR BOTÓN "CONTRATAR" EN admin_evaluacion.php

### Agregar en la sección de botones:

```php
<form method="POST" action="procesar_accion_candidato.php" style="display: inline;">
    <input type="hidden" name="id_aplicacion" value="<?php echo $row['id_aplicacion']; ?>">
    <input type="hidden" name="accion" value="contratar">
    <input type="date" name="fecha_ingreso" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" style="padding: 5px; margin-right: 5px;">
    <button type="submit" class="btn" style="background-color: #10b981; color: white;" 
            onclick="return confirm('¿Contratar? Se iniciará onboarding.');">
        Contratar
    </button>
</form>
```

### En `procesar_accion_candidato.php`, agregar caso:

```php
case 'contratar':
    require_once 'agente_orquestador.php';
    $orquestador = new AgenteOrquestador($mysqli);
    $resultado = $orquestador->ejecutarAgente('seguimiento_ingreso', [
        'accion' => 'iniciar',
        'id_aplicacion' => $id_aplicacion,
        'fecha_ingreso' => $_POST['fecha_ingreso'] ?? null
    ]);
    
    if ($resultado['success']) {
        $sql = "UPDATE aplicaciones SET status_aplicacion = 'Contratado' WHERE id_aplicacion = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $id_aplicacion);
        $stmt->execute();
        header("Location: admin_evaluacion.php?success=contratado");
    } else {
        header("Location: admin_evaluacion.php?error=no_contratado");
    }
    break;
```

---

## ✅ RESUMEN

**Archivos a crear:**
- `agente_validacion_proceso.php`
- `agente_seguimiento_ingreso.php`

**Archivos a modificar:**
- `agente_orquestador.php` (agregar casos)
- `admin_validacion.php` (agregar botón y procesamiento)
- `admin_onboarding.php` (agregar formularios)
- `admin_evaluacion.php` (agregar botón contratar)
- `procesar_accion_candidato.php` (agregar caso contratar)

**Base de datos:**
- Ejecutar SQL del Paso 1

---

**¡Listo! Con estos pasos tendrás los agentes conectados con el sistema.**



