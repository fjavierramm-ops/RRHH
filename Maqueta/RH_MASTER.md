# 📚 DOCUMENTACIÓN MASTER - SISTEMA DE RECURSOS HUMANOS

## 🎯 VISIÓN GENERAL DEL SISTEMA

Este es un **Sistema de Gestión de Reclutamiento y Selección** desarrollado en PHP con MySQL, que automatiza el proceso completo desde la postulación de candidatos hasta su onboarding. El sistema utiliza una arquitectura basada en **agentes inteligentes** que procesan automáticamente las aplicaciones, evalúan candidatos, detectan riesgos y gestionan el flujo de trabajo.

---

## 🏗️ ARQUITECTURA DEL SISTEMA

### **Componentes Principales:**

1. **Frontend (Vistas)**: Interfaces para candidatos y administradores
2. **Backend (Controladores)**: Procesamiento de formularios y lógica de negocio
3. **Agentes Inteligentes**: Automatización de procesos (evaluación, detección de riesgos, calendarización)
4. **Base de Datos**: Almacenamiento estructurado de toda la información

### **Flujo General del Sistema:**

```
Candidato → Registro → Postulación → Evaluación Automática → 
Detección de Riesgos → Revisión RRHH → Entrevista → 
Validación → Contratación → Onboarding
```

---

## 📁 ESTRUCTURA DE ARCHIVOS Y DOCUMENTACIÓN

---

## 🔐 **1. CONFIGURACIÓN Y AUTENTICACIÓN**

### **`config.php`**
**Propósito**: Archivo central de configuración y conexión a la base de datos.

**Puntos Clave:**
- Establece conexión MySQL usando `mysqli`
- Configuración de base de datos: `recursosh`
- Inicia sesiones PHP (`session_start()`)
- Configura charset UTF-8 para soporte de caracteres especiales

**Conexiones:**
- **Base de Datos**: Se conecta a MySQL/MariaDB en `localhost`
- **Otros Archivos**: Es requerido por TODOS los archivos PHP del sistema mediante `require_once 'config.php'`

**Variables Globales:**
- `$mysqli`: Objeto de conexión a la base de datos (disponible globalmente)

---

### **`login.html`**
**Propósito**: Interfaz de inicio de sesión y registro de candidatos.

**Puntos Clave:**
- Formulario de login que envía a `login_proceso.php`
- Modal de registro con validación JavaScript
- Campos: email, contraseña, nombre, teléfono, habilidades, CV, portafolio
- Validación cliente-side de coincidencia de contraseñas

**Conexiones:**
- **POST → `login_proceso.php`**: Procesa inicio de sesión
- **POST → `registro_proceso.php`**: Procesa registro de nuevos candidatos
- **CSS**: `estilo.css` para estilos

**Funcionalidad:**
- Manejo de subida de archivos (CV y portafolio)
- Validación de formularios antes de envío
- Interfaz responsive con diseño moderno

---

### **`login_proceso.php`**
**Propósito**: Procesa el inicio de sesión de candidatos.

**Puntos Clave:**
- Valida credenciales contra tabla `candidatos`
- Usa `password_verify()` para verificar contraseñas hasheadas
- Establece variables de sesión: `$_SESSION['loggedin']`, `$_SESSION['id']`, `$_SESSION['email']`
- Redirige a `inicio.php` en éxito o a `login.html` con error

**Conexiones:**
- **Base de Datos**: Consulta tabla `candidatos` (SELECT por email)
- **Archivos**: Requiere `config.php`
- **Redirección**: `inicio.php` (éxito) o `login.html?error=...` (fallo)

**Seguridad:**
- Usa prepared statements para prevenir SQL injection
- Verifica hash de contraseña con `password_verify()`

---

### **`registro_proceso.php`**
**Propósito**: Procesa el registro de nuevos candidatos.

**Puntos Clave:**
- Valida datos del formulario de registro
- Sube archivos CV y portafolio a carpeta `uploads/`
- Hashea contraseña con `password_hash()`
- Inserta nuevo candidato en tabla `candidatos`
- Inicia sesión automáticamente después del registro

**Conexiones:**
- **Base de Datos**: INSERT en tabla `candidatos`
- **Archivos**: Requiere `config.php`
- **Sistema de Archivos**: Guarda CVs en `uploads/`
- **Redirección**: `inicio.php` después de registro exitoso

**Campos Procesados:**
- Información personal (nombre, email, teléfono)
- Habilidades técnicas y blandas (texto separado por comas)
- Archivos: CV (obligatorio), Portafolio (opcional)
- URL de portafolio online (opcional)

---

## 🏠 **2. INTERFACES DE CANDIDATOS**

### **`inicio.php`**
**Propósito**: Dashboard principal para candidatos - muestra vacantes disponibles.

**Puntos Clave:**
- Verifica sesión activa (redirige a login si no hay sesión)
- Muestra vacantes abiertas con búsqueda
- Indica qué vacantes ya tienen aplicación del candidato
- Muestra aplicaciones en proceso en sección inferior

**Conexiones:**
- **Base de Datos**: 
  - Consulta `vacantes` (WHERE estado = 'Abierta')
  - Consulta `aplicaciones` para verificar aplicaciones previas
- **Archivos**: Requiere `config.php`
- **Navegación**: Enlaces a `aplicaciones.php`, `entrevistas.php`, `detalle_vacante.php`
- **CSS**: `estilo.css`

**Funcionalidad:**
- Búsqueda de vacantes por título, empresa o ubicación
- Filtrado de vacantes ya aplicadas
- Visualización de estado de aplicaciones en proceso

---

### **`aplicaciones.php`**
**Propósito**: Muestra todas las aplicaciones del candidato con sus estados.

**Puntos Clave:**
- Lista todas las aplicaciones del candidato logueado
- Muestra estado de cada aplicación (En revisión, En proceso, Aceptado, Rechazado, Contratado)
- Permite búsqueda de aplicaciones
- Muestra barra de progreso visual según estado

**Conexiones:**
- **Base de Datos**: 
  - JOIN entre `aplicaciones`, `vacantes` y `candidatos`
  - Filtra por `id_candidato` de la sesión
- **Archivos**: Requiere `config.php`
- **Navegación**: Enlaces a `detalle_vacante.php`, `entrevistas.php`
- **CSS**: `estilo.css`

**Estados Visualizados:**
- En revisión (amarillo)
- En proceso (naranja)
- Aceptado (verde)
- Rechazado (rojo)
- Contratado (azul)

---

### **`detalle_vacante.php`**
**Propósito**: Muestra detalles completos de una vacante y permite postularse.

**Puntos Clave:**
- Muestra información completa de la vacante (descripción, requisitos, beneficios)
- Verifica si el candidato ya aplicó a esta vacante
- Botón de postulación que llama a `postular.php` vía AJAX
- Modal de confirmación después de postularse

**Conexiones:**
- **Base de Datos**: 
  - SELECT de `vacantes` por `id_vacante`
  - Verifica en `aplicaciones` si ya existe aplicación
- **Archivos**: Requiere `config.php`
- **AJAX**: Llama a `postular.php` para procesar postulación
- **CSS**: `estilo.css`

**Funcionalidad:**
- Si no está logueado: muestra botón "Iniciar sesión"
- Si ya aplicó: muestra mensaje de confirmación
- Si no ha aplicado: muestra botón "Postularme"

---

### **`entrevistas.php`**
**Propósito**: Muestra entrevistas programadas y permite solicitar cambios de fecha.

**Puntos Clave:**
- Lista entrevistas del candidato con fechas propuestas
- Permite solicitar cambio de fecha/hora
- Muestra estado de confirmación (Pendiente, Confirmada, Reprogramación solicitada)
- Modal para seleccionar nueva fecha entre opciones propuestas

**Conexiones:**
- **Base de Datos**: 
  - JOIN entre `entrevistas`, `aplicaciones`, `vacantes`
  - Filtra por `id_candidato` de la sesión
  - UPDATE en `entrevistas` para cambios de fecha
- **Archivos**: Requiere `config.php`
- **CSS**: `estilo.css`

**Funcionalidad:**
- Muestra 3 opciones de fecha/hora propuestas por el sistema
- Permite seleccionar una opción o proponer nueva fecha
- Actualiza `fecha_final` y `hora_final` en tabla `entrevistas`

---

## 📝 **3. PROCESAMIENTO DE POSTULACIONES**

### **`postular.php`**
**Propósito**: Procesa la postulación de un candidato a una vacante (endpoint AJAX).

**Puntos Clave:**
- Recibe `id_vacante` por POST
- Verifica que no exista aplicación duplicada
- Inserta nueva aplicación en tabla `aplicaciones` con estado "En revisión"
- **Ejecuta agentes automáticamente** después de crear la aplicación:
  - Agente de Segmentación (Fit Score)
  - Agente de Detección de Riesgos

**Conexiones:**
- **Base de Datos**: 
  - INSERT en `aplicaciones`
  - Verifica duplicados antes de insertar
- **Archivos**: 
  - Requiere `config.php`
  - Requiere `agente_orquestador.php` para ejecutar agentes
- **Respuesta**: JSON con `success` y `message`

**Flujo Automático:**
1. Crea aplicación en BD
2. Ejecuta `agente_orquestador->ejecutarAgente('segmentacion')`
3. Ejecuta `agente_orquestador->ejecutarAgente('deteccion_riesgos')`
4. Si score de riesgo > 70, registra alerta en logs

---

## 🤖 **4. AGENTES INTELIGENTES**

### **`agente_orquestador.php`**
**Propósito**: Orquestador central que ejecuta y coordina todos los agentes del sistema.

**Puntos Clave:**
- Patrón Singleton/Facade para centralizar ejecución de agentes
- Registra inicio y fin de cada ejecución en tabla `log_agentes`
- Maneja errores y excepciones de agentes
- Switch case para diferentes tipos de agentes

**Agentes Soportados:**
1. `segmentacion`: Calcula fit score del candidato
2. `deteccion_riesgos`: Analiza riesgos en la aplicación
3. `calendarizacion`: Programa entrevistas
4. `feedback_rechazo`: Genera feedback de rechazo
5. `validacion_proceso`: Valida proceso completo
6. `seguimiento_ingreso`: Gestiona onboarding

**Conexiones:**
- **Base de Datos**: 
  - INSERT/UPDATE en `log_agentes` (registro de ejecuciones)
- **Archivos**: 
  - Requiere `config.php`
  - Requiere archivos de agentes específicos según el caso
- **Otros Archivos**: Llamado por `postular.php`, `procesar_accion_candidato.php`, `admin_validacion.php`, `admin_onboarding.php`

**Métodos Principales:**
- `ejecutarAgente($nombre_agente, $datos)`: Ejecuta un agente específico
- `registrarInicio()`: Registra inicio de ejecución
- `registrarFin()`: Registra fin y resultado

---

### **`procesar_fit.php`**
**Propósito**: Agente de Segmentación - Calcula el Fit Score (coincidencia candidato-vacante).

**Puntos Clave:**
- Compara habilidades técnicas del candidato vs requisitos de la vacante
- Calcula `score_tecnico` (0-100%)
- Calcula `score_blando` (base 50% + bonificaciones)
- Calcula `score_global` = promedio de ambos
- Clasifica fit: Alto (≥75%), Medio (50-74%), Bajo (<50%)

**Conexiones:**
- **Base de Datos**: 
  - SELECT de `aplicaciones`, `candidatos`, `vacantes` (JOIN)
  - INSERT/UPDATE en `evaluaciones` con scores calculados
- **Archivos**: 
  - Requiere `config.php`
  - Llamado internamente por `agente_orquestador.php`
- **Lógica**: Comparación de texto (habilidades vs requisitos)

**Algoritmo:**
1. Extrae habilidades técnicas y blandas del candidato
2. Extrae requisitos y descripción de la vacante
3. Busca coincidencias de palabras clave
4. Calcula porcentajes de coincidencia
5. Guarda resultados en tabla `evaluaciones`

---

### **`agente_deteccion_riesgos.php`**
**Propósito**: Detecta riesgos potenciales en las aplicaciones de candidatos.

**Puntos Clave:**
- Analiza múltiples factores de riesgo:
  - Score global muy bajo (<30%)
  - Habilidades técnicas vacías o insuficientes
  - Información de contacto incompleta
  - CV no encontrado o no subido
- Calcula `score_riesgo` acumulativo (0-100)
- Guarda riesgos detectados en tabla `riesgos_detectados`

**Conexiones:**
- **Base de Datos**: 
  - SELECT de `aplicaciones`, `candidatos`, `evaluaciones`
  - INSERT en `riesgos_detectados` por cada riesgo encontrado
- **Archivos**: 
  - Requiere `config.php`
  - Llamado por `agente_orquestador.php`
- **Sistema de Archivos**: Verifica existencia de archivo CV

**Tipos de Riesgo:**
- `informacion_sospechosa`: Score extremadamente bajo
- `inconsistencia`: Datos incompletos o faltantes
- Severidades: `alta`, `media`, `baja`

---

### **`agente_calendarizacion.php`**
**Propósito**: Gestiona la programación de entrevistas con múltiples opciones de fecha/hora.

**Puntos Clave:**
- Genera 3 opciones de fecha/hora para entrevistas
- Inserta/actualiza en tabla `entrevistas`
- Envía notificaciones al candidato (registra en `notificaciones_entrevista`)
- Maneja reprogramaciones de entrevistas

**Conexiones:**
- **Base de Datos**: 
  - INSERT/UPDATE en `entrevistas`
  - INSERT en `notificaciones_entrevista`
  - SELECT de datos del candidato para notificaciones
- **Archivos**: 
  - Requiere `config.php`
  - Llamado por `agente_orquestador.php` y `procesar_accion_candidato.php`

**Funcionalidad:**
- `crear`: Crea entrevista con 3 opciones de fecha
- `notificar_reprogramacion`: Envía notificación de cambio de fecha

---

### **`agente_feedback_rechazo.php`**
**Propósito**: Genera y envía feedback personalizado a candidatos rechazados.

**Puntos Clave:**
- Genera mensaje personalizado basado en evaluación del candidato
- Incluye razones de rechazo
- Genera sugerencias de mejora basadas en scores
- Guarda feedback en tabla `feedback_rechazo`

**Conexiones:**
- **Base de Datos**: 
  - SELECT de `aplicaciones`, `candidatos`, `vacantes`, `evaluaciones`
  - INSERT en `feedback_rechazo`
  - UPDATE de estado de envío
- **Archivos**: 
  - Requiere `config.php`
  - Llamado por `agente_orquestador.php`

**Funcionalidad:**
- Personaliza mensaje con nombre del candidato y vacante
- Incluye scores de evaluación
- Sugiere mejoras según áreas débiles detectadas

---

### **`agente_validacion_proceso.php`**
**Propósito**: Valida que el proceso de selección esté completo antes de avanzar.

**Puntos Clave:**
- Valida existencia de CV
- Valida que exista evaluación con score adecuado (≥30%)
- Genera validaciones en tabla `comentarios_validacion`
- Estados: `aprobado`, `rechazado`, `pendiente`

**Conexiones:**
- **Base de Datos**: 
  - SELECT de `aplicaciones`, `candidatos`, `evaluaciones`, `entrevistas`
  - INSERT en `comentarios_validacion` con tipo y estado
- **Archivos**: 
  - Requiere `config.php`
  - Llamado por `agente_orquestador.php` desde `admin_validacion.php`

**Validaciones:**
- CV debe existir y estar accesible
- Score global debe ser ≥30%
- Si todo está bien: estado `aprobado`
- Si hay problemas: estado `rechazado` con fecha límite

---

### **`agente_seguimiento_ingreso.php`**
**Propósito**: Gestiona el proceso de onboarding de candidatos contratados.

**Puntos Clave:**
- Inicia proceso de onboarding con fecha de ingreso
- Crea tareas pendientes en formato JSON
- Actualiza estado de tareas (Documentación, Config. Equipos, Inducción, Entrenamiento)
- Gestiona fecha límite de ingreso

**Conexiones:**
- **Base de Datos**: 
  - INSERT/UPDATE en `onboarding`
  - Almacena `tareas_pendientes` como JSON
- **Archivos**: 
  - Requiere `config.php`
  - Llamado por `agente_orquestador.php` desde `admin_onboarding.php` y `procesar_accion_candidato.php`

**Tareas Gestionadas:**
- `doc_contratacion`: Documentación de contratación
- `config_equipos`: Configuración de equipos
- `induccion`: Proceso de inducción
- `entrenamiento`: Entrenamiento inicial

**Estados de Tareas:**
- Pendiente
- En proceso
- Completado

---

## 👨‍💼 **5. INTERFACES DE ADMINISTRACIÓN**

### **`admin_evaluacion.php`**
**Propósito**: Dashboard para RRHH - Evalúa candidatos y toma decisiones.

**Puntos Clave:**
- Muestra candidatos con sus scores de evaluación
- Filtros por puesto y score (alto ≥80%)
- Muestra alertas de riesgos detectados
- Acciones disponibles:
  - Ver detalles
  - Programar entrevista
  - Rechazar candidato
  - Contratar candidato

**Conexiones:**
- **Base de Datos**: 
  - SELECT con JOIN de `aplicaciones`, `candidatos`, `vacantes`, `evaluaciones`, `riesgos_detectados`
  - Filtra por status: 'En proceso', 'Entrevista', 'En revisión'
- **Archivos**: 
  - Requiere `config.php`
  - POST a `procesar_accion_candidato.php` para acciones
- **CSS**: `estilo_admin.css`

**Visualización:**
- Cards con nombre, puesto, score global
- Barra de progreso visual del score
- Alertas de riesgos (alta/media severidad)
- Tags de habilidades principales

---

### **`admin_validacion.php`**
**Propósito**: Interfaz para validar procesos con cliente interno.

**Puntos Clave:**
- Muestra aplicaciones pendientes de validación
- Filtro por departamento (empresa)
- Botón "Validar Proceso" que ejecuta agente de validación
- Muestra estado de validaciones (aprobado/pendiente/rechazado)

**Conexiones:**
- **Base de Datos**: 
  - SELECT con JOIN de `aplicaciones`, `candidatos`, `vacantes`, `comentarios_validacion`
  - Cuenta validaciones aprobadas y pendientes
- **Archivos**: 
  - Requiere `config.php`
  - POST ejecuta `agente_orquestador->ejecutarAgente('validacion_proceso')`
- **CSS**: `estilo_admin.css`

**Funcionalidad:**
- Tabla con entregables y candidatos
- Muestra fecha de envío
- Estado visual con badges de color
- Ejecuta validación automática al hacer clic en "Validar Proceso"

---

### **`admin_onboarding.php`**
**Propósito**: Dashboard para seguimiento de onboarding de candidatos contratados.

**Puntos Clave:**
- Muestra solo candidatos con status "Contratado"
- Permite iniciar proceso de onboarding con fecha de ingreso
- Muestra progreso de tareas (Documentación, Equipos, Inducción, Entrenamiento)
- Permite actualizar estado de cada tarea

**Conexiones:**
- **Base de Datos**: 
  - SELECT con JOIN de `aplicaciones`, `candidatos`, `vacantes`, `onboarding`
  - Filtra por status 'Contratado'
  - UPDATE en `onboarding` para tareas
- **Archivos**: 
  - Requiere `config.php`
  - POST ejecuta `agente_orquestador->ejecutarAgente('seguimiento_ingreso')`
- **CSS**: `estilo_admin.css`

**Funcionalidad:**
- Si onboarding no iniciado: muestra formulario para iniciar
- Si ya iniciado: muestra checklist de tareas con dropdowns para actualizar estado
- Indicadores visuales (puntos de color) para estado de cada tarea

---

### **`procesar_accion_candidato.php`**
**Propósito**: Procesa acciones de RRHH sobre candidatos (entrevista, rechazo, contratación).

**Puntos Clave:**
- Recibe acción y `id_aplicacion` por POST
- Ejecuta agente correspondiente según acción
- Actualiza `status_aplicacion` en tabla `aplicaciones`
- Redirige a `admin_evaluacion.php` con mensaje de éxito/error

**Acciones Soportadas:**
1. `programar_entrevista`: Ejecuta agente de calendarización
2. `rechazar`: Ejecuta agente de feedback de rechazo
3. `contratar`: Ejecuta agente de seguimiento de ingreso (onboarding)

**Conexiones:**
- **Base de Datos**: 
  - UPDATE en `aplicaciones` (status_aplicacion)
- **Archivos**: 
  - Requiere `config.php`
  - Requiere `agente_orquestador.php`
- **Redirección**: `admin_evaluacion.php` con parámetros de éxito/error

**Flujo por Acción:**
- **Entrevista**: Crea entrevista → Actualiza status a "Entrevista"
- **Rechazar**: Genera feedback → Actualiza status a "Rechazado"
- **Contratar**: Inicia onboarding → Actualiza status a "Contratado"

---

### **`reprogramar.php`**
**Propósito**: Procesa solicitudes de cambio de fecha de entrevista por parte del candidato.

**Puntos Clave:**
- Recibe nueva fecha/hora seleccionada por el candidato
- Actualiza `fecha_final` y `hora_final` en tabla `entrevistas`
- Cambia `status_confirmacion` a "Reprogramación solicitada"
- Ejecuta agente de calendarización para notificar reprogramación
- Actualiza `status_aplicacion` a "Entrevista"

**Conexiones:**
- **Base de Datos**: 
  - UPDATE en `entrevistas` (fecha_final, hora_final, status_confirmacion)
  - UPDATE en `aplicaciones` (status_aplicacion)
- **Archivos**: 
  - Requiere `config.php`
  - Requiere `agente_orquestador.php` para notificación
- **Redirección**: `entrevistas.php` con status de éxito/error

**Funcionalidad:**
- Verifica sesión del candidato
- Valida datos recibidos
- Actualiza entrevista con nueva fecha
- Notifica a RRHH sobre reprogramación

---

## 🎨 **6. ESTILOS Y PRESENTACIÓN**

### **`estilo.css`**
**Propósito**: Estilos CSS para interfaces de candidatos.

**Puntos Clave:**
- Diseño responsive y moderno
- Estilos para login, dashboard, vacantes, aplicaciones, entrevistas
- Paleta de colores azul (#2b5c8f, #88b8df)
- Componentes: cards, badges, barras de progreso, modales

**Elementos Estilizados:**
- Login page con gradiente
- Dashboard header con búsqueda
- Cards de vacantes y aplicaciones
- Badges de estado (colores según estado)
- Barras de progreso animadas
- Modales para registro y confirmaciones

---

### **`estilo_admin.css`**
**Propósito**: Estilos CSS para interfaces de administración (RRHH).

**Puntos Clave:**
- Diseño profesional para dashboards administrativos
- Paleta de colores corporativa (#2f3e6f, #7b9dcf)
- Componentes: filtros, tablas, cards, badges

**Elementos Estilizados:**
- Filtros de búsqueda y selección
- Tablas con estados visuales
- Cards de evaluación con scores
- Badges de validación (verde/amarillo/rojo)
- Checklist de onboarding con indicadores

---

## 🗄️ **7. BASE DE DATOS**

### **Estructura de Tablas (recursosh.sql)**

#### **`candidatos`**
- Almacena información de candidatos registrados
- Campos: id, nombre, email, password (hasheado), teléfono, habilidades, CV path
- Relación: 1:N con `aplicaciones`

#### **`vacantes`**
- Almacena ofertas de trabajo
- Campos: id_vacante, titulo, empresa, ubicacion, descripcion, requisitos, salario, estado
- Relación: 1:N con `aplicaciones`

#### **`aplicaciones`**
- Tabla central que conecta candidatos con vacantes
- Campos: id_aplicacion, id_candidato, id_vacante, fecha_aplicacion, status_aplicacion
- Estados: "En revisión", "En proceso", "Entrevista", "Aceptado", "Rechazado", "Contratado"
- Relaciones: FK a `candidatos` y `vacantes`

#### **`evaluaciones`**
- Almacena scores de evaluación automática
- Campos: id_evaluacion, id_aplicacion, score_tecnico, score_blando, score_global (calculado), comentarios_tecnicos
- Relación: 1:1 con `aplicaciones`

#### **`riesgos_detectados`**
- Almacena riesgos detectados por el agente
- Campos: id_riesgo, id_aplicacion, tipo_riesgo, severidad, descripcion, evidencia, score_riesgo, revisado
- Relación: N:1 con `aplicaciones`

#### **`entrevistas`**
- Almacena información de entrevistas programadas
- Campos: id_entrevista, id_aplicacion, 3 fechas/horas propuestas, fecha_final, hora_final, status_confirmacion
- Relación: 1:1 con `aplicaciones`

#### **`comentarios_validacion`**
- Almacena comentarios y validaciones del proceso
- Campos: id_comentario, id_aplicacion, autor, mensaje, tipo_validacion, estado_validacion, fecha_limite
- Relación: N:1 con `aplicaciones`

#### **`onboarding`**
- Almacena información del proceso de onboarding
- Campos: id_onboarding, id_aplicacion, fecha_ingreso, estados de tareas, tareas_pendientes (JSON), fecha_limite_ingreso
- Relación: 1:1 con `aplicaciones`

#### **`log_agentes`**
- Registra ejecuciones de agentes (usado por orquestador)
- Campos: id_log, id_aplicacion, agente_nombre, estado, datos_entrada, datos_salida, fecha_inicio, fecha_fin, error_mensaje

---

## 🔄 **8. FLUJO COMPLETO DEL SISTEMA**

### **Flujo de Postulación y Evaluación:**

1. **Registro de Candidato**
   - `login.html` → `registro_proceso.php`
   - Inserta en `candidatos`
   - Sube CV a `uploads/`

2. **Búsqueda y Postulación**
   - `inicio.php` muestra vacantes
   - `detalle_vacante.php` muestra detalles
   - `postular.php` crea aplicación

3. **Evaluación Automática** (después de postular)
   - `postular.php` ejecuta `agente_orquestador`
   - `procesar_fit.php` calcula fit score → guarda en `evaluaciones`
   - `agente_deteccion_riesgos.php` detecta riesgos → guarda en `riesgos_detectados`

4. **Revisión por RRHH**
   - `admin_evaluacion.php` muestra candidatos con scores
   - RRHH puede: programar entrevista, rechazar, o contratar
   - `procesar_accion_candidato.php` ejecuta acción correspondiente

5. **Programación de Entrevista**
   - `agente_calendarizacion.php` crea 3 opciones de fecha
   - Guarda en `entrevistas`
   - Candidato ve opciones en `entrevistas.php`
   - Puede confirmar o solicitar cambio (`reprogramar.php`)

6. **Validación del Proceso**
   - `admin_validacion.php` muestra aplicaciones pendientes
   - Ejecuta `agente_validacion_proceso.php`
   - Valida CV, evaluación, y genera comentarios en `comentarios_validacion`

7. **Contratación y Onboarding**
   - RRHH contrata desde `admin_evaluacion.php`
   - `agente_seguimiento_ingreso.php` inicia onboarding
   - Guarda en `onboarding` con tareas pendientes
   - `admin_onboarding.php` permite seguimiento y actualización de tareas

---

## 🔗 **9. CONEXIONES ENTRE ARCHIVOS**

### **Mapa de Dependencias:**

```
config.php (base)
    ├── login_proceso.php
    ├── registro_proceso.php
    ├── inicio.php
    ├── aplicaciones.php
    ├── detalle_vacante.php
    ├── entrevistas.php
    ├── postular.php
    │   └── agente_orquestador.php
    │       ├── procesar_fit.php
    │       ├── agente_deteccion_riesgos.php
    │       ├── agente_calendarizacion.php
    │       ├── agente_feedback_rechazo.php
    │       ├── agente_validacion_proceso.php
    │       └── agente_seguimiento_ingreso.php
    ├── procesar_accion_candidato.php
    │   └── agente_orquestador.php
    ├── reprogramar.php
    │   └── agente_orquestador.php
    ├── admin_evaluacion.php
    ├── admin_validacion.php
    │   └── agente_orquestador.php
    └── admin_onboarding.php
        └── agente_orquestador.php
```

### **Flujo de Datos:**

1. **Frontend → Backend**: Formularios HTML envían POST a archivos PHP
2. **Backend → Agentes**: Archivos PHP llaman a `agente_orquestador.php`
3. **Agentes → Base de Datos**: Agentes consultan/actualizan tablas
4. **Base de Datos → Frontend**: PHP consulta BD y renderiza HTML

---

## 🛡️ **10. SEGURIDAD Y MEJORES PRÁCTICAS**

### **Seguridad Implementada:**
- ✅ Prepared statements para prevenir SQL injection
- ✅ Hash de contraseñas con `password_hash()` y `password_verify()`
- ✅ Validación de sesiones antes de acceder a páginas protegidas
- ✅ Sanitización de inputs con `real_escape_string()` y `htmlspecialchars()`
- ✅ Verificación de existencia de archivos antes de acceder

### **Áreas de Mejora:**
- ⚠️ Implementar autenticación de administradores (actualmente no hay)
- ⚠️ Validar permisos de acceso a archivos
- ⚠️ Implementar CSRF tokens en formularios
- ⚠️ Rate limiting para prevenir spam de postulaciones
- ⚠️ Validación más estricta de tipos de archivo subidos

---

## 📊 **11. PUNTOS CLAVE DEL SISTEMA**

### **Arquitectura:**
- **MVC Simplificado**: Separación entre vistas (HTML), controladores (PHP), y modelo (BD)
- **Patrón Orquestador**: Centralización de lógica de agentes
- **Agentes Autónomos**: Cada agente tiene responsabilidad única

### **Automatización:**
- Evaluación automática de candidatos
- Detección automática de riesgos
- Generación automática de entrevistas
- Validación automática de procesos
- Gestión automática de onboarding

### **Escalabilidad:**
- Sistema modular permite agregar nuevos agentes fácilmente
- Base de datos normalizada con relaciones claras
- Separación de estilos permite personalización

---

## 🎯 **12. RESUMEN EJECUTIVO**

Este sistema de RRHH automatiza el proceso completo de reclutamiento desde la postulación hasta el onboarding. Utiliza **agentes inteligentes** que procesan automáticamente las aplicaciones, calculan fit scores, detectan riesgos, programan entrevistas y gestionan el onboarding.

**Componentes principales:**
- **Frontend**: Interfaces intuitivas para candidatos y administradores
- **Backend**: Procesamiento robusto con validaciones y seguridad
- **Agentes**: Automatización inteligente de procesos
- **Base de Datos**: Estructura normalizada y relacional

**Flujo principal:**
Candidato se registra → Postula a vacante → Sistema evalúa automáticamente → RRHH revisa y decide → Entrevista programada → Validación → Contratación → Onboarding gestionado

El sistema está diseñado para ser **escalable**, **modular** y **fácil de mantener**, permitiendo agregar nuevas funcionalidades sin afectar el código existente.

---

**Documentación generada el:** 2025-01-02  
**Versión del Sistema:** 1.0  
**Autor:** Sistema de Documentación Automática

