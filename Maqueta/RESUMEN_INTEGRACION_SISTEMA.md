# RESUMEN DETALLADO DE INTEGRACIÓN DEL SISTEMA

## 📋 ÍNDICE
1. [Visión General](#visión-general)
2. [Arquitectura de Conexión](#arquitectura-de-conexión)
3. [Puntos Clave de la Integración](#puntos-clave-de-la-integración)
4. [Líneas de Código Importantes](#líneas-de-código-importantes)
5. [Funcionalidades del Nuevo Sistema](#funcionalidades-del-nuevo-sistema)
6. [Mapeo de Tablas y Columnas](#mapeo-de-tablas-y-columnas)
7. [Flujo de Datos](#flujo-de-datos)

---

## 🎯 VISIÓN GENERAL

### ¿Qué se hizo?
Se integró completamente el sistema de "Nueva carpeta" (sistema de administración de reclutamiento) con el sistema existente "Maqueta", manteniendo la compatibilidad con ambos sistemas y utilizando una única base de datos (`recursosh`).

### Objetivo Principal
- **Unificar** ambos sistemas en una sola aplicación
- **Preservar** toda la funcionalidad existente del sistema "Maqueta"
- **Agregar** las nuevas funcionalidades del sistema "Nueva carpeta"
- **Centralizar** la conexión a la base de datos en un solo archivo (`config.php`)

---

## 🔌 ARQUITECTURA DE CONEXIÓN

### 1. Punto Central de Conexión: `config.php`

El archivo `config.php` es el **corazón de la integración**. Contiene:

```php
<?php
// Credenciales de la base de datos
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'recursosh');

// Conexión global para sistema "Maqueta" (compatibilidad hacia atrás)
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verificar conexión
if ($mysqli === false) {
    die("ERROR: No se pudo conectar a la base de datos. " . $mysqli->connect_error);
}

// Establecer charset
$mysqli->set_charset("utf8mb4");

// Iniciar sesión (ÚNICO punto de inicio de sesión en todo el sistema)
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

**Puntos Clave:**
- **Línea 9**: Variable global `$mysqli` para compatibilidad con código existente del sistema "Maqueta"
- **Línea 20**: `session_start()` único en todo el sistema (evita warnings de sesión duplicada)
- **Línea 25-33**: Función `connection()` que permite al nuevo sistema obtener la conexión de forma compatible

### 2. Sistema de Helpers: `helpers_db.php`

Este archivo centraliza las consultas adaptadas para mapear las diferencias entre esquemas:

```php
<?php
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

**Puntos Clave:**
- **Aliases SQL**: Usa `AS` para crear nombres de columnas compatibles (`id AS idClientes`)
- **COALESCE**: Maneja diferencias de nombres de columnas (`departamento` vs `empresa`)
- **Transformaciones**: Limpia y convierte datos (ej: salario de string a número)

---

## 🔑 PUNTOS CLAVE DE LA INTEGRACIÓN

### 1. **Compatibilidad Bidireccional**
- El sistema "Maqueta" sigue usando `$mysqli` directamente
- El sistema "Nueva carpeta" usa `connection()` que retorna `$mysqli`
- Ambos sistemas comparten la misma conexión y base de datos

### 2. **Mapeo de Esquemas**
El sistema "Nueva carpeta" esperaba ciertos nombres de columnas que no existían en `recursosh.sql`. Se resolvió mediante:

- **Aliases en SQL**: `SELECT id AS idClientes FROM candidatos`
- **Funciones helper**: Centralizan la lógica de mapeo
- **COALESCE**: Maneja valores NULL o columnas con nombres diferentes

### 3. **Gestión de Sesiones Unificada**
- **ANTES**: Cada archivo llamaba `session_start()` → múltiples warnings
- **AHORA**: Solo `config.php` llama `session_start()` → sin warnings

### 4. **Foreign Keys y Relaciones**
Se adaptaron las inserciones para cumplir con las restricciones de foreign keys:

```php
// Ejemplo: Antes de insertar en entrevistas, verificar/crear aplicación
$sql_app = "SELECT id_aplicacion FROM aplicaciones WHERE id_candidato = ? AND id_vacante = ? LIMIT 1";
// Si no existe, crear una nueva aplicación
// Luego usar ese id_aplicacion en la inserción de entrevistas
```

---

## 💻 LÍNEAS DE CÓDIGO IMPORTANTES

### 1. Conexión en Archivos Nuevos

**Patrón estándar en todos los archivos nuevos:**
```php
<?php
require_once("config.php");
$conn = connection();
$conn->set_charset("utf8mb4");
```

**¿Por qué es importante?**
- `require_once("config.php")`: Carga la configuración y la función `connection()`
- `connection()`: Obtiene la conexión compartida
- `set_charset("utf8mb4")`: Asegura codificación correcta para caracteres especiales

### 2. Consultas con Aliases

**Ejemplo de mapeo de columnas:**
```php
$sql = "SELECT 
    id AS idClientes,           // candidatos.id → idClientes
    nombre AS NombreCompleto,   // candidatos.nombre → NombreCompleto
    email,
    telefono
FROM candidatos";
```

**¿Por qué es importante?**
- El código del nuevo sistema espera `idClientes` y `NombreCompleto`
- La base de datos tiene `id` y `nombre`
- Los aliases hacen la traducción transparente

### 3. Manejo de Foreign Keys

**Ejemplo de creación de aplicación antes de entrevista:**
```php
// Buscar si existe aplicación
$sql_app = "SELECT id_aplicacion FROM aplicaciones 
            WHERE id_candidato = ? AND id_vacante = ? LIMIT 1";
$stmt_app = $conn->prepare($sql_app);
$stmt_app->bind_param("ii", $idCliente, $idVacante);
$stmt_app->execute();
$result_app = $stmt_app->get_result();

if ($result_app && $result_app->num_rows > 0) {
    // Usar aplicación existente
    $row_app = $result_app->fetch_assoc();
    $id_aplicacion = $row_app['id_aplicacion'];
} else {
    // Crear nueva aplicación
    $sql_create_app = "INSERT INTO aplicaciones 
                       (id_candidato, id_vacante, fecha_aplicacion, status_aplicacion) 
                       VALUES (?, ?, NOW(), 'En proceso')";
    // ... ejecutar y obtener id_aplicacion
}

// Ahora sí, insertar entrevista con id_aplicacion válido
$stmt = $conn->prepare("INSERT INTO entrevistas 
                        (id_aplicacion, idClientes, idVacante, idReclutador, ...) 
                        VALUES (?,?,?,?,...)");
```

**¿Por qué es importante?**
- La tabla `entrevistas` tiene una foreign key a `aplicaciones`
- Debe existir una aplicación antes de crear una entrevista
- Este código asegura que siempre exista la relación

### 4. Transformación de Datos

**Ejemplo: Limpieza de salario:**
```php
CAST(REPLACE(REPLACE(REPLACE(salario, '$', ''), ',', ''), ' ', '') AS UNSIGNED) AS salario
```

**¿Por qué es importante?**
- El salario puede venir como "$40,000 - $60,000" (string)
- Se necesita convertir a número para cálculos
- `REPLACE` elimina caracteres no numéricos, `CAST` convierte a entero

---

## 🎨 FUNCIONALIDADES DEL NUEVO SISTEMA

### 1. **Panel de Administración** (`administrador.php`)

**Funcionalidades:**
- ✅ Gestión de vacantes (crear, editar, eliminar, ver)
- ✅ Gestión de usuarios (candidatos y reclutadores)
- ✅ Programación de entrevistas
- ✅ Visualización de estadísticas (contadores de usuarios y vacantes)
- ✅ Registro de comunicaciones

**Características técnicas:**
- Usa `helpers_db.php` para obtener datos compatibles
- Maneja foreign keys correctamente (crea aplicaciones antes de entrevistas)
- Integra con `canal_comunicacion` para logs

### 2. **Gestión de Vacantes** (`crear-vacante.php`, `ver-vacante.php`)

**Funcionalidades:**
- ✅ Crear nuevas vacantes
- ✅ Editar vacantes existentes
- ✅ Ver detalles completos de vacantes
- ✅ Mapeo automático de campos (departamento → empresa, tipo → tipo_trabajo)

**Mapeo de campos:**
```php
// En INSERT/UPDATE:
departamento → empresa
tipo → tipo_trabajo
fechaApertura → fecha_publicacion
fechaCierre → fecha_publicacion (mismo campo)
```

### 3. **Disponibilidades** (`disponibilidades.php`)

**Funcionalidades:**
- ✅ Gestionar disponibilidad de candidatos
- ✅ Gestionar disponibilidad de reclutadores
- ✅ Ver entrevistas programadas
- ✅ Eliminar disponibilidades y entrevistas

**Características:**
- Usa `idClientes` (aliased de `candidatos.id`)
- Usa `idreclutadores` para reclutadores
- Integra con tabla `disponibilidaddelequipo` y `disponibilidadrrhh`

### 4. **API del Agente** (`api_agente.php`)

**Funcionalidades:**
- ✅ Encuentra horarios disponibles para entrevistas
- ✅ Guarda propuestas de horarios
- ✅ Registra logs de comunicación
- ✅ Valida conflictos de horarios

**Características:**
- Lógica de IA para optimización de horarios
- Integración con disponibilidades de candidatos y reclutadores
- Registro automático en `canal_comunicacion`

### 5. **Logs de Comunicación** (`logs-comunicacion.php`)

**Funcionalidades:**
- ✅ Ver todos los logs de comunicación
- ✅ Filtrar por candidato, fecha, canal
- ✅ Visualizar historial completo

**Características:**
- Join con tabla `candidatos` usando `idClientes`
- Muestra nombre del candidato (aliased como `usuario`)

### 6. **Post-Entrevista** (`post-entrevista.php`)

**Funcionalidades:**
- ✅ Evaluar entrevistas realizadas
- ✅ Guardar resultados en `resultados_entrevista`
- ✅ Mostrar información del candidato y vacante

**Características:**
- Join con `candidatos` y `vacantes` usando foreign keys
- Inserta en `resultados_entrevista` con `id_entrevista`

### 7. **Autenticación** (`Admlogin.php`, `Admregistro.php`)

**Funcionalidades:**
- ✅ Login de administradores/reclutadores
- ✅ Registro de nuevos administradores
- ✅ Validación de credenciales

**Características:**
- Usa tabla `reclutadores` para autenticación
- Redirige a `administrador.php` después del login
- Usa `styles.css` para diseño consistente

---

## 📊 MAPEO DE TABLAS Y COLUMNAS

### Tabla: `candidatos`
| Columna Original | Alias Usado | Uso |
|------------------|-------------|-----|
| `id` | `idClientes` | Foreign key en entrevistas, aplicaciones |
| `nombre` | `NombreCompleto` | Display en interfaces |
| `email` | `email` | Directo |
| `telefono` | `telefono` | Directo |

### Tabla: `vacantes`
| Columna Original | Mapeo | Uso |
|------------------|-------|-----|
| `id_vacante` | `idVacante` | Identificador principal |
| `empresa` | `departamento` | Display como "Departamento" |
| `tipo_trabajo` | `tipo` | Display como "Tipo" |
| `fecha_publicacion` | `fechaApertura` / `fechaCierre` | Fechas de apertura/cierre |

### Tabla: `reclutadores`
| Columna Original | Alias Usado | Uso |
|------------------|-------------|-----|
| `idreclutadores` | `id` / `idReclutador` | Foreign key en entrevistas |
| `NombreCompleto` | `nombre` | Display |
| `roles` | `roles` | Directo |
| `estados` | `estados` | Filtro de activos |

### Tabla: `entrevistas`
| Columna | Tipo | Relación |
|---------|------|----------|
| `id_entrevista` | PK | - |
| `id_aplicacion` | FK | → `aplicaciones.id_aplicacion` |
| `idClientes` | FK | → `candidatos.id` |
| `idVacante` | FK | → `vacantes.id_vacante` |
| `idReclutador` | FK | → `reclutadores.idreclutadores` |
| `fecha` | DATE | - |
| `hora_inicio` | TIME | - |
| `hora_fin` | TIME | - |
| `estado` | VARCHAR | - |
| `notas` | TEXT | - |

---

## 🔄 FLUJO DE DATOS

### Flujo 1: Crear Vacante
```
1. Usuario llena formulario en crear-vacante.php
2. POST → procesa datos
3. Mapea campos: departamento → empresa, tipo → tipo_trabajo
4. INSERT INTO vacantes (empresa, tipo_trabajo, ...)
5. Redirect → ver-vacante.php?view={id}
```

### Flujo 2: Programar Entrevista
```
1. Usuario selecciona candidato, vacante, reclutador en administrador.php
2. POST → agendar_entrevista
3. Verifica/crea aplicación en tabla aplicaciones
4. INSERT INTO entrevistas (con id_aplicacion, idClientes, idVacante, idReclutador)
5. INSERT INTO canal_comunicacion (log de comunicación)
6. Respuesta "ok" → JavaScript actualiza UI
```

### Flujo 3: Ver Logs de Comunicación
```
1. Usuario accede a logs-comunicacion.php
2. SELECT con JOIN:
   - canal_comunicacion
   - candidatos (ON idClientes = candidatos.id)
3. Alias: candidatos.nombre AS usuario
4. Display en tabla con filtros
```

### Flujo 4: Evaluar Post-Entrevista
```
1. Usuario accede a post-entrevista.php?idEntrevista={id}
2. SELECT entrevistas JOIN candidatos JOIN vacantes
3. Muestra información del candidato y vacante
4. Usuario llena formulario de evaluación
5. INSERT INTO resultados_entrevista
```

---

## 🛠️ CORRECCIONES APLICADAS

### Error 1: ArgumentCountError en bind_param
**Problema**: Número de parámetros no coincidía con placeholders
**Solución**: Ajustar `bind_param()` para incluir exactamente los parámetros del SQL

### Error 2: TypeError en number_format
**Problema**: `salario` venía como string "$40,000 - $60,000"
**Solución**: Parsear string y extraer número antes de `number_format()`

### Error 3: Foreign Key Constraint
**Problema**: `entrevistas` requiere `id_aplicacion` pero no existía
**Solución**: Buscar o crear aplicación antes de insertar entrevista

### Error 4: Unknown Column
**Problema**: Consulta usaba `C.NombreCompleto` pero columna es `C.nombre`
**Solución**: Cambiar a `C.nombre AS usuario`

### Error 5: Header Warning
**Problema**: Output antes de `header()` redirect
**Solución**: Usar `ob_end_clean()` antes de `header()`

### Error 6: Parse Error
**Problema**: Falta `?>` antes de HTML
**Solución**: Agregar `?>` para separar PHP de HTML

---

## 📝 NOTAS IMPORTANTES

1. **Siempre usar `require_once("config.php")`** en archivos nuevos
2. **Nunca llamar `session_start()`** en archivos nuevos (ya está en config.php)
3. **Usar `connection()`** para obtener la conexión compartida
4. **Usar helpers de `helpers_db.php`** cuando sea posible para consistencia
5. **Verificar foreign keys** antes de insertar datos relacionados
6. **Usar aliases SQL** para compatibilidad de nombres de columnas
7. **Mapear campos** según la tabla de mapeo cuando insertar/actualizar

---

## 🎯 CONCLUSIÓN

La integración fue exitosa mediante:
- ✅ Un punto central de conexión (`config.php`)
- ✅ Sistema de helpers para mapeo de datos (`helpers_db.php`)
- ✅ Aliases SQL para compatibilidad de nombres
- ✅ Manejo correcto de foreign keys
- ✅ Gestión unificada de sesiones
- ✅ Transformación de datos cuando es necesario

El sistema ahora funciona como una aplicación unificada que combina las funcionalidades de ambos sistemas originales, manteniendo compatibilidad y agregando nuevas capacidades de administración de reclutamiento.

