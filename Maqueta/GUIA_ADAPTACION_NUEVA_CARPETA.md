# Guía de Adaptación: Integración del Sistema "Nueva carpeta" con "Maqueta"

## 📋 Resumen Ejecutivo

Esta guía te ayudará a adaptar el sistema de "Nueva carpeta" para que funcione con tu base de datos `recursosh` y tu archivo `config.php` existente, manteniendo toda la funcionalidad ya creada en "Maqueta".

---

## 🔍 1. Análisis de Diferencias Clave

### 1.1 Base de Datos

| Aspecto | Nueva carpeta | Maqueta (Tu sistema) |
|---------|---------------|----------------------|
| **Nombre BD** | `reclutamiento` | `recursosh` |
| **Conexión** | `conexion.php` → función `connection()` → `$conn` | `config.php` → variable global `$mysqli` |
| **Candidatos** | Tabla `clientes` | Tabla `candidatos` |
| **Reclutadores** | Tabla `reclutadores` | No existe (solo candidatos) |
| **Vacantes** | Estructura diferente | Estructura diferente |

### 1.2 Mapeo de Tablas

#### Tablas que EXISTEN en ambos sistemas:
- ✅ `vacantes` (pero con columnas diferentes)
- ✅ `entrevistas` (pero con estructura diferente)

#### Tablas SOLO en "Nueva carpeta":
- `clientes` → **Mapear a** `candidatos`
- `reclutadores` → **Crear nueva tabla o usar tabla de usuarios**
- `disponibilidaddelequipo` → **Crear nueva tabla**
- `disponibilidades_rrhh` → **Crear nueva tabla**
- `canal_comunicacion` → **Crear nueva tabla**
- `resultados_entrevista` → **Crear nueva tabla**

#### Tablas SOLO en "Maqueta":
- `aplicaciones` → **Mantener (es clave en tu sistema)**
- `evaluaciones` → **Mantener**
- `onboarding` → **Mantener**
- `comentarios_validacion` → **Mantener**

---

## 🛠️ 2. Pasos de Adaptación

### PASO 1: Crear Tablas Faltantes en `recursosh`

Ejecuta este SQL en tu base de datos `recursosh`:

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
-- ADAPTAR TABLA: entrevistas (agregar columnas necesarias)
-- ============================================
-- Tu tabla entrevistas actual tiene estructura diferente.
-- Opción A: Agregar columnas nuevas (recomendado)
ALTER TABLE `entrevistas` 
  ADD COLUMN IF NOT EXISTS `idClientes` int(11) DEFAULT NULL COMMENT 'FK a candidatos.id',
  ADD COLUMN IF NOT EXISTS `idVacante` int(11) DEFAULT NULL COMMENT 'FK a vacantes.id_vacante',
  ADD COLUMN IF NOT EXISTS `idReclutador` int(15) UNSIGNED DEFAULT NULL COMMENT 'FK a reclutadores.idreclutadores',
  ADD COLUMN IF NOT EXISTS `fecha` date DEFAULT NULL COMMENT 'Fecha final confirmada',
  ADD COLUMN IF NOT EXISTS `hora_inicio` time DEFAULT NULL COMMENT 'Hora inicio',
  ADD COLUMN IF NOT EXISTS `hora_fin` time DEFAULT NULL COMMENT 'Hora fin',
  ADD COLUMN IF NOT EXISTS `estado` varchar(30) DEFAULT 'Programada',
  ADD COLUMN IF NOT EXISTS `notas` text DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `fecha_creacion` datetime DEFAULT current_timestamp();

-- Agregar índices
ALTER TABLE `entrevistas`
  ADD KEY IF NOT EXISTS `fk_e_cli` (`idClientes`),
  ADD KEY IF NOT EXISTS `fk_e_vac` (`idVacante`),
  ADD KEY IF NOT EXISTS `fk_e_rec` (`idReclutador`);

-- Agregar foreign keys (si no existen)
ALTER TABLE `entrevistas`
  ADD CONSTRAINT `fk_e_cli` FOREIGN KEY (`idClientes`) 
    REFERENCES `candidatos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_e_vac` FOREIGN KEY (`idVacante`) 
    REFERENCES `vacantes` (`id_vacante`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_e_rec` FOREIGN KEY (`idReclutador`) 
    REFERENCES `reclutadores` (`idreclutadores`) ON DELETE CASCADE ON UPDATE CASCADE;
```

---

### PASO 2: Adaptar `config.php` para Compatibilidad

Modifica tu `config.php` para que también exponga una función `connection()` compatible:

```php
<?php
// Credenciales de la base de datos para XAMPP (configuración por defecto)
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'recursosh');

// Conexión a la base de datos (variable global para compatibilidad con Maqueta)
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
// FUNCIÓN DE COMPATIBILIDAD para "Nueva carpeta"
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

### PASO 3: Crear Archivo de Mapeo de Columnas

Crea un archivo `mapeo_columnas.php` que centralice las diferencias:

```php
<?php
/**
 * Mapeo de columnas entre "Nueva carpeta" y "Maqueta"
 * Usa este archivo para mantener consistencia
 */

// Mapeo de tablas
define('TABLA_CANDIDATOS', 'candidatos');  // En Nueva carpeta era 'clientes'
define('TABLA_RECLUTADORES', 'reclutadores');
define('TABLA_VACANTES', 'vacantes');
define('TABLA_ENTREVISTAS', 'entrevistas');
define('TABLA_APLICACIONES', 'aplicaciones'); // Solo existe en Maqueta

// Mapeo de columnas de candidatos/clientes
define('COL_CANDIDATO_ID', 'id');  // En Nueva carpeta era 'idClientes'
define('COL_CANDIDATO_NOMBRE', 'nombre');  // En Nueva carpeta era 'NombreCompleto'
define('COL_CANDIDATO_EMAIL', 'email');
define('COL_CANDIDATO_TELEFONO', 'telefono');

// Mapeo de columnas de vacantes
// Nueva carpeta: idVacante, titulo, departamento, tipo, ubicacion, descripcion, requisitos, salario, fechaApertura, fechaCierre, responsable, estado, fecha_creacion
// Maqueta: id_vacante, titulo, empresa, ubicacion, descripcion, requisitos, salario, tipo_trabajo, fecha_publicacion, estado
define('COL_VACANTE_ID', 'id_vacante');
define('COL_VACANTE_TITULO', 'titulo');
define('COL_VACANTE_UBICACION', 'ubicacion');
define('COL_VACANTE_DESCRIPCION', 'descripcion');
define('COL_VACANTE_REQUISITOS', 'requisitos');
define('COL_VACANTE_SALARIO', 'salario');
define('COL_VACANTE_ESTADO', 'estado');

// Mapeo de columnas de entrevistas
// Nueva carpeta: idEntrevista, idClientes, idVacante, idReclutador, fecha, hora_inicio, hora_fin, estado, notas, fecha_creacion
// Maqueta: id_entrevista, id_aplicacion, fecha_propuesta_1, hora_propuesta_1, fecha_propuesta_2, hora_propuesta_2, fecha_propuesta_3, hora_propuesta_3, fecha_final, hora_final, status_confirmacion
define('COL_ENTREVISTA_ID', 'id_entrevista');
define('COL_ENTREVISTA_APLICACION', 'id_aplicacion'); // Solo en Maqueta
define('COL_ENTREVISTA_CANDIDATO', 'idClientes'); // Nueva columna agregada
define('COL_ENTREVISTA_VACANTE', 'idVacante'); // Nueva columna agregada
define('COL_ENTREVISTA_RECLUTADOR', 'idReclutador'); // Nueva columna agregada
define('COL_ENTREVISTA_FECHA', 'fecha'); // Nueva columna agregada
define('COL_ENTREVISTA_HORA_INICIO', 'hora_inicio'); // Nueva columna agregada
define('COL_ENTREVISTA_HORA_FIN', 'hora_fin'); // Nueva columna agregada
define('COL_ENTREVISTA_ESTADO', 'estado'); // Nueva columna agregada
?>
```

---

### PASO 4: Adaptar Archivos de "Nueva carpeta"

#### 4.1 Reemplazar `conexion.php` con `config.php`

En todos los archivos de "Nueva carpeta", cambia:

```php
// ANTES:
include("conexion.php");
$conn = connection();

// DESPUÉS:
require_once("../Maqueta/config.php");
$conn = connection(); // Ahora usa la función de config.php
```

**Archivos a modificar:**
- `administrador.php`
- `api_agente.php`
- `crear-vacante.php`
- `disponibilidades.php`
- `logs-comunicacion.php`
- `post-entrevista.php`
- `ver-vacante.php`

#### 4.2 Adaptar Consultas SQL - Mapeo de Tablas

**A) Cambiar `clientes` → `candidatos`:**

```php
// ANTES:
SELECT idClientes, NombreCompleto FROM clientes

// DESPUÉS:
SELECT id AS idClientes, nombre AS NombreCompleto FROM candidatos
```

**B) Adaptar columnas de `vacantes`:**

```php
// Nueva carpeta usa: idVacante, departamento, tipo, fechaApertura, fechaCierre, responsable
// Maqueta usa: id_vacante, empresa, tipo_trabajo, fecha_publicacion

// En consultas, usar alias:
SELECT 
    id_vacante AS idVacante,
    titulo,
    empresa AS departamento,  // Mapeo aproximado
    tipo_trabajo AS tipo,
    fecha_publicacion AS fechaApertura,
    fecha_publicacion AS fechaCierre,  // Ajustar según lógica
    'RRHH' AS responsable  // Valor por defecto o crear columna
FROM vacantes
```

**C) Adaptar `entrevistas`:**

```php
// Tu tabla entrevistas tiene estructura diferente.
// Opción 1: Usar las nuevas columnas agregadas (recomendado)
// Opción 2: Mantener compatibilidad con ambas estructuras

// Ejemplo de INSERT adaptado:
INSERT INTO entrevistas 
    (id_aplicacion, idClientes, idVacante, idReclutador, 
     fecha, hora_inicio, hora_fin, estado, notas)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
```

#### 4.3 Adaptar Autenticación

**En `Admlogin.php` y `Admregistro.php`:**

```php
// Cambiar de:
include('conexion.php');
$conexion = connection();

// A:
require_once('../Maqueta/config.php');
$conexion = connection();

// Las consultas a 'reclutadores' se mantienen igual
// (ya creaste la tabla en el PASO 1)
```

---

### PASO 5: Crear Función Helper para Consultas Compatibles

Crea `helpers_db.php`:

```php
<?php
require_once('config.php');

/**
 * Obtiene candidatos con formato compatible con "Nueva carpeta"
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
            WHERE 1=1  -- Agregar filtros según necesites
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
                empresa AS departamento,
                tipo_trabajo AS tipo,
                ubicacion,
                descripcion,
                requisitos,
                CAST(REPLACE(REPLACE(salario, '$', ''), ',', '') AS UNSIGNED) AS salario,
                fecha_publicacion AS fechaApertura,
                fecha_publicacion AS fechaCierre,
                'RRHH' AS responsable,
                estado
            FROM vacantes
            ORDER BY id_vacante DESC";
    return $conn->query($sql);
}

/**
 * Inserta entrevista compatible con ambas estructuras
 */
function insertarEntrevistaCompatible($conn, $datos) {
    // $datos debe contener: idClientes, idVacante, idReclutador, fecha, hora_inicio, hora_fin, estado, notas
    // Opcional: id_aplicacion (si viene de Maqueta)
    
    $sql = "INSERT INTO entrevistas 
            (id_aplicacion, idClientes, idVacante, idReclutador, 
             fecha, hora_inicio, hora_fin, estado, notas,
             fecha_propuesta_1, hora_propuesta_1, status_confirmacion)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $id_aplicacion = $datos['id_aplicacion'] ?? null;
    $fecha_prop = $datos['fecha'] ?? null;
    $hora_prop = $datos['hora_inicio'] ?? null;
    $status = $datos['estado'] ?? 'Programada';
    
    $stmt->bind_param(
        "iiiissssssss",
        $id_aplicacion,
        $datos['idClientes'],
        $datos['idVacante'],
        $datos['idReclutador'],
        $datos['fecha'],
        $datos['hora_inicio'],
        $datos['hora_fin'],
        $datos['estado'],
        $datos['notas'],
        $fecha_prop,
        $hora_prop,
        $status
    );
    
    return $stmt->execute();
}
?>
```

---

### PASO 6: Estructura de Carpetas Recomendada

```
Maqueta/
├── config.php                    (✅ Ya existe, adaptado)
├── mapeo_columnas.php            (🆕 Nuevo)
├── helpers_db.php                (🆕 Nuevo)
├── login_proceso.php             (✅ Existente)
├── inicio.php                    (✅ Existente)
├── ... (tus archivos existentes)
│
└── admin/                        (🆕 Nueva carpeta para panel admin)
    ├── administrador.php         (Adaptado)
    ├── Admlogin.php              (Adaptado)
    ├── Admregistro.php           (Adaptado)
    ├── index.php                 (Adaptado)
    ├── crear-vacante.php         (Adaptado)
    ├── ver-vacante.php           (Adaptado)
    ├── disponibilidades.php      (Adaptado)
    ├── api_agente.php            (Adaptado)
    ├── post-entrevista.php       (Adaptado)
    ├── logs-comunicacion.php     (Adaptado)
    └── logout.php                (Adaptado)
```

---

### PASO 7: Checklist de Adaptación por Archivo

#### ✅ `administrador.php`
- [ ] Cambiar `include("conexion.php")` → `require_once("../config.php")`
- [ ] Cambiar consultas `clientes` → `candidatos` con alias
- [ ] Adaptar consultas `vacantes` con alias de columnas
- [ ] Verificar que `reclutadores` funcione (tabla creada)

#### ✅ `api_agente.php`
- [ ] Cambiar conexión
- [ ] Adaptar `obtenerDisponibilidadCandidato()` para usar `candidatos.id`
- [ ] Verificar que `disponibilidaddelequipo.idClientes` apunte a `candidatos.id`

#### ✅ `disponibilidades.php`
- [ ] Cambiar conexión
- [ ] Adaptar todas las consultas de `clientes` → `candidatos`
- [ ] Verificar foreign keys

#### ✅ `crear-vacante.php` y `ver-vacante.php`
- [ ] Cambiar conexión
- [ ] Adaptar INSERT/UPDATE de `vacantes` para usar columnas de Maqueta
- [ ] Mapear `departamento` → `empresa` (o crear columna nueva)
- [ ] Mapear `tipo` → `tipo_trabajo`
- [ ] Mapear `fechaApertura/fechaCierre` → `fecha_publicacion`

#### ✅ `post-entrevista.php`
- [ ] Cambiar conexión
- [ ] Verificar que `resultados_entrevista.idEntrevista` apunte a `entrevistas.id_entrevista`

#### ✅ `logs-comunicacion.php`
- [ ] Cambiar conexión
- [ ] Adaptar JOIN con `candidatos` en lugar de `clientes`

---

### PASO 8: Pruebas de Integración

1. **Probar conexión:**
   ```php
   require_once('config.php');
   $conn = connection();
   var_dump($conn);
   ```

2. **Probar consulta de candidatos:**
   ```php
   $sql = "SELECT id AS idClientes, nombre AS NombreCompleto FROM candidatos LIMIT 5";
   $result = $conn->query($sql);
   while($row = $result->fetch_assoc()) {
       print_r($row);
   }
   ```

3. **Probar inserción en nuevas tablas:**
   ```php
   // Insertar disponibilidad de candidato
   $stmt = $conn->prepare("INSERT INTO disponibilidaddelequipo 
                          (idClientes, dia_semana, hora_inicio, hora_fin, fecha_referencia, puesto, estado) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)");
   // ... probar con datos de prueba
   ```

---

## ⚠️ Consideraciones Importantes

1. **Backup:** Haz backup de tu BD `recursosh` antes de ejecutar los ALTER TABLE.

2. **Datos existentes:** Las nuevas columnas en `entrevistas` pueden quedar NULL para registros antiguos. Considera migración de datos si es necesario.

3. **Compatibilidad:** Mantén ambas estructuras funcionando durante la transición.

4. **Foreign Keys:** Verifica que todas las foreign keys apunten correctamente.

5. **Sesiones:** El sistema de "Nueva carpeta" usa `admin_id` y `admin_email`, mientras que "Maqueta" usa `loggedin`, `id`, `email`. Considera unificar o mantener separados según necesidad.

---

## 📝 Siguiente Paso: Integración de Agentes

Una vez completada esta adaptación, podrás integrar los agentes faltantes siguiendo la misma estructura.

---

**¿Necesitas ayuda con algún paso específico?** Indica qué archivo o funcionalidad quieres adaptar primero.

