## Resumen General de la Integración de Agentes

Este documento resume todo lo que se hizo para integrar agentes inteligentes al sistema de reclutamiento en PHP, qué agentes ya están funcionando, cuáles faltan, y qué requiere cada uno (a nivel de lógica, base de datos y vistas) para funcionar correctamente dentro del sistema.

---

## 1. Arquitectura General que Construimos

- **Monolito PHP + MySQL existente**
  - Formularios de registro, login, postulación, vistas de administración.
  - Tablas principales: `candidatos`, `vacantes`, `aplicaciones`, `evaluaciones`, `entrevistas`, `onboarding`, `comentarios_validacion`.

- **Nuevo patrón: Orquestador de agentes**
  - Archivo: `agente_orquestador.php`.
  - Clase central: `AgenteOrquestador`.
  - **Responsabilidades:**
    - Recibir una acción como `segmentacion`, `deteccion_riesgos`, `calendarizacion`, `feedback_rechazo`, `validacion_proceso`, `seguimiento_ingreso`.
    - Registrar en `log_agentes` el inicio y fin de cada ejecución (entrada y salida en JSON).
    - Delegar a la clase/agente correcto.
    - Manejar errores con `try/catch`.

- **Registro de actividad de agentes**
  - Tabla: `log_agentes`.
  - Guarda: `id_aplicacion`, `agente_nombre`, `estado`, `datos_entrada`, `datos_salida`, `error_mensaje`, `fecha_inicio`, `fecha_fin`.

Con esto pasamos de un sistema con `include` sueltos a un sistema con **un punto de entrada único** para todos los agentes.

---

## 2. Agentes Implementados (FUNCIONANDO)

### 2.1 Agente de Segmentación (Fit Score)

- **Archivo principal de lógica:** `procesar_fit.php`  
- **Invocado como agente desde:** `agente_orquestador.php` (`case 'segmentacion'`).

- **Qué hace:**
  - Calcula `score_tecnico` y `score_blando` para una aplicación.
  - Calcula un `score_global` y una `clasificacion_fit` (`Alto Fit`, `Medio Fit`, `Bajo Fit`).
  - Guarda los datos en `evaluaciones`.

- **Cambios importantes:**
  - En `procesar_fit.php` se añadió `clasificacion_fit`:
    - Inserta/actualiza en `evaluaciones`:
      - `score_tecnico`
      - `score_blando`
      - `comentarios_tecnicos`
      - `clasificacion_fit`
  - En `postular.php`:
    - Se reemplazó el `include 'procesar_fit.php'` por:
      - Llamada al `AgenteOrquestador` con `segmentacion`.
      - Llamada al agente de riesgos (`deteccion_riesgos`).

- **Requisitos para funcionar:**
  - **BD:** tabla `evaluaciones` con columnas `score_tecnico`, `score_blando`, `score_global` (si la usas), `comentarios_tecnicos`, `clasificacion_fit`.
  - **Sistema:** archivo `procesar_fit.php` correcto, `agente_orquestador.php` con el `case 'segmentacion'` y `postular.php` llamando al orquestador.

---

### 2.2 Agente de Detección de Riesgos

- **Archivo:** `agente_deteccion_riesgos.php`
- **Método principal:** `analizar($id_aplicacion)`
- **Invocado como:** `deteccion_riesgos` desde el orquestador.

- **Qué hace:**
  - Lee datos de la aplicación (evaluaciones, CV, datos de contacto).
  - Detecta riesgos típicos:
    - Score muy bajo.
    - Falta de CV.
    - Datos incompletos.
    - Inconsistencias.
  - Inserta registros en la tabla `riesgos_detectados`.

- **Requisitos para funcionar:**
  - **BD:** tabla `riesgos_detectados` (id, id_aplicacion, tipo_riesgo, severidad, descripcion, fecha_creacion).
  - **Sistema:**
    - `agente_deteccion_riesgos.php` creado.
    - `agente_orquestador.php` con `case 'deteccion_riesgos'`.
    - `postular.php` llamando al orquestador después de segmentación:
      - `ejecutarAgente('deteccion_riesgos', ['id_aplicacion' => $id_app_internal])`.

---

### 2.3 Agente de Calendarización de Entrevistas

- **Archivo:** `agente_calendarizacion.php`
- **Métodos principales:**
  - `procesar($datos)`
  - `crearEntrevista($datos)`
  - `notificarReprogramacion($id_entrevista)`

- **Qué hace:**
  - Propone fechas y horas de entrevista basadas en una `fecha_base`.
  - Inserta la entrevista en la tabla `entrevistas`.
  - Inserta notificaciones en `notificaciones_entrevista`.
  - Se integra con la reprogramación de entrevistas.

- **Integraciones:**
  - `procesar_accion_candidato.php`:
    - Caso `programar_entrevista` llama a `calendarizacion` → actualiza `status_aplicacion` a `Entrevista`.
  - `reprogramar.php`:
    - Después de actualizar la entrevista, llama al orquestador con `calendarizacion`/`notificar_reprogramacion`.

- **Requisitos para funcionar:**
  - **BD:** tablas `entrevistas` y `notificaciones_entrevista` según el script SQL.
  - **Sistema:** 
    - `agente_calendarizacion.php`.
    - `agente_orquestador.php` con `case 'calendarizacion'`.
    - Botones/formularios en `admin_evaluacion.php` y flujo en `reprogramar.php`.

---

### 2.4 Agente de Feedback de Rechazo

- **Archivo:** `agente_feedback_rechazo.php`
- **Métodos:**
  - `generarYEnviar($datos)`
  - `generarMensaje($info, $razones)`
  - `generarSugerencias($info)`

- **Qué hace:**
  - Genera un mensaje de rechazo personalizado usando:
    - Info del candidato.
    - Resultados de evaluación.
    - Razones de rechazo.
  - Inserta el mensaje en `feedback_rechazo`.
  - (En esta versión) **no envía correo real**, solo guarda el texto preparado.

- **Integraciones:**
  - `procesar_accion_candidato.php`, caso `rechazar`:
    - Llama a `feedback_rechazo`.
    - Actualiza `status_aplicacion` a `Rechazado`.

- **Requisitos para funcionar:**
  - **BD:** tabla `feedback_rechazo`.
  - **Sistema:** 
    - `agente_feedback_rechazo.php`.
    - `agente_orquestador.php` con `case 'feedback_rechazo'`.
    - Botón de rechazo en `admin_evaluacion.php` que postea a `procesar_accion_candidato.php`.

- **Pendiente futuro:**
  - Integrar una librería como **PHPMailer** para enviar el correo real.

---

### 2.5 Agente de Validación de Proceso (Cliente Interno)

- **Archivo:** `agente_validacion_proceso.php`
- **Método principal:** `validar($id_aplicacion)`
- **Invocado como:** `validacion_proceso` desde el orquestador.

- **Qué hace:**
  - Revisa:
    - Si existe CV.
    - Si existe evaluación y si el `score_global` es suficiente.
    - Si hay entrevistas asociadas.
  - Genera una lista de validaciones:
    - De tipo `proceso` o `entregable`.
    - Con estado `aprobado` o `rechazado`.
    - Con `fecha_limite` para correcciones.
  - Inserta los resultados en `comentarios_validacion` como `Sistema (Agente)`.

- **Integraciones:**
  - `admin_validacion.php`:
    - Al inicio, procesa el POST `validar`:
      - Crea `AgenteOrquestador` y llama a `validacion_proceso`.
      - Redirige con `?validado=...`.
    - En la tabla:
      - Muestra contadores de validaciones aprobadas y pendientes.
      - Tiene botón **"Validar Proceso"** que dispara el POST.

- **Requisitos para funcionar:**
  - **BD:**
    - Tabla `comentarios_validacion` con columnas:
      - `tipo_validacion` (`proceso`, `entregable`, `feedback_cliente`).
      - `estado_validacion` (`pendiente`, `aprobado`, `rechazado`).
      - `fecha_limite`.
  - **Sistema:**
    - `agente_validacion_proceso.php`.
    - `agente_orquestador.php` con `case 'validacion_proceso'`.
    - `admin_validacion.php` con:
      - Lógica de POST al inicio.
      - Consulta SQL ajustada (LEFT JOIN con `comentarios_validacion`).
      - Botón "Validar Proceso" en la columna Acciones.

---

### 2.6 Agente de Seguimiento de Ingreso / Onboarding

- **Archivo:** `agente_seguimiento_ingreso.php`
- **Métodos:**
  - `iniciarOnboarding($id_aplicacion, $fecha_ingreso = null)`
  - `actualizarTarea($id_aplicacion, $tarea_nombre, $nuevo_estado)`

- **Qué hace:**
  - **Al iniciar onboarding:**
    - Define tareas base (`doc_contratacion`, `config_equipos`, `induccion`, `entrenamiento`) con estado `Pendiente`.
    - Inserta o actualiza un registro en `onboarding`:
      - `fecha_ingreso`
      - columnas de tareas (`doc_contratacion`, `config_equipos`, etc.)
      - `tareas_pendientes` (JSON con nombre y estado de cada tarea).
      - `fecha_limite_ingreso`.
  - **Al actualizar tarea:**
    - Lee `tareas_pendientes`.
    - Actualiza el estado de la tarea específica.
    - Actualiza también la columna directa (`doc_contratacion`, etc.).

- **Integraciones:**
  - `admin_onboarding.php`:
    - Al inicio, si hay POST:
      - Llama a `seguimiento_ingreso` con `accion = 'iniciar'` o `accion = 'actualizar_tarea'`.
      - Redirige con `?iniciado=1` o `?actualizado=1`.
    - Consulta SQL:
      - Trae candidatos contratados + `onboarding`.
    - En cada tarjeta:
      - Si NO hay onboarding:
        - Formulario con fecha y botón **"Iniciar Onboarding"**.
      - Si SÍ hay onboarding:
        - Un formulario por tarea con combo de estado (`Pendiente`, `En proceso`, `Completado`).

  - `admin_evaluacion.php`:
    - Botón **"Contratar"**:
      - Envia `accion=contratar` a `procesar_accion_candidato.php`.
  - `procesar_accion_candidato.php`, caso `contratar`:
    - Llama al orquestador con `seguimiento_ingreso` (`accion = 'iniciar'`).
    - Actualiza `status_aplicacion` a `Contratado`.
    - Redirige a `admin_evaluacion.php`.

- **Requisitos para funcionar:**
  - **BD:**
    - Tabla `onboarding` con:
      - `id_onboarding`, `id_aplicacion`, `fecha_ingreso`,
      - columnas de tareas (`doc_contratacion`, `config_equipos`, `induccion`, `entrenamiento`),
      - `tareas_pendientes` (JSON),
      - `fecha_limite_ingreso`, `recordatorios_enviados`, `notificaciones_enviadas`.
  - **Sistema:**
    - `agente_seguimiento_ingreso.php`.
    - `agente_orquestador.php` con `case 'seguimiento_ingreso'`.
    - `admin_onboarding.php` modificado.
    - Botón "Contratar" en `admin_evaluacion.php` y caso `contratar` en `procesar_accion_candidato.php`.

---

## 3. Archivos Clave que Tocamos o Creamos

- **Orquestador:**
  - `agente_orquestador.php`  
    - Switch central con casos:
      - `segmentacion`
      - `deteccion_riesgos`
      - `calendarizacion`
      - `feedback_rechazo`
      - `validacion_proceso`
      - `seguimiento_ingreso`
    - Funciones privadas para registrar inicio y fin en `log_agentes`.

- **Vistas / flujos integrados:**
  - `postular.php`  
    - Después de crear la aplicación:
      - Llama a `segmentacion`.
      - Llama a `deteccion_riesgos`.

  - `admin_evaluacion.php`  
    - Muestra info de evaluaciones y riesgos.
    - Botones:
      - Programar entrevista → `procesar_accion_candidato.php` (`programar_entrevista`).
      - Rechazar → `procesar_accion_candidato.php` (`rechazar`).
      - Contratar → `procesar_accion_candidato.php` (`contratar`).

  - `procesar_accion_candidato.php`
    - Recibe `id_aplicacion` y `accion`.
    - Casos:
      - `programar_entrevista` → `calendarizacion`.
      - `contratar` → `seguimiento_ingreso` + update a `Contratado`.
      - `rechazar` → `feedback_rechazo` + update a `Rechazado`.

  - `admin_validacion.php`
    - Filtra por departamento.
    - Muestra validaciones aprobadas/pendientes.
    - Botón **"Validar Proceso"** que llama al `AgenteOrquestador` (`validacion_proceso`).

  - `admin_onboarding.php`
    - Lista solo aplicaciones con status `Contratado`.
    - Si no hay onboarding:
      - Form para iniciar onboarding con fecha de ingreso.
    - Si hay onboarding:
      - Form para actualizar cada tarea.

  - `reprogramar.php`
    - Actualiza fecha/hora de entrevista.
    - Llama a `AgenteOrquestador` para notificación de reprogramación.

---

## 4. Agentes Pendientes y lo que Necesitan

### 4.1 Agente de Seguimiento Post-Entrevista (PENDIENTE)

- **Objetivo:**
  - Obtener feedback del candidato después de la entrevista.
  - Guardar respuestas en una tabla tipo `feedback_post_entrevista`.
  - Permitir reportes posteriores (satisfacción, NPS, etc.).

- **Qué necesita a nivel sistema:**
  - **BD:**
    - Tabla `feedback_post_entrevista` (id, id_entrevista o id_aplicacion, calificación, comentarios, fecha).
  - **Frontend:**
    - Una vista/formulario (por ejemplo `encuesta_post_entrevista.php`) donde el candidato responda.
  - **Backend:**
    - Un archivo `agente_feedback_post_entrevista.php` (opcional, si se quiere lógica más avanzada).
    - Un caso adicional en `AgenteOrquestador` si se maneja como agente.

---

### 4.2 Agente de Redacción IA (PENDIENTE / REQUIERE MÁS)

- **Objetivo:**
  - Ayudar a redactar y optimizar descripciones de vacantes.
  - Generar mensajes de publicación para redes.

- **Qué necesita:**
  - **Vista:** `admin_vacantes.php` (no existe aún):
    - CRUD de vacantes.
    - Botón "Sugerir descripción" / "Optimizar texto".
  - **Integración IA (externa):**
    - Llamado a API (OpenAI u otra).
    - Manejo de claves API (config segura).
  - **BD:**
    - Posible tabla `recursos_visuales_vacantes` o campos extra en `vacantes` para guardar versiones de textos.

---

### 4.3 Agente de Diseño Visual (PENDIENTE / REQUIERE MÁS)

- **Objetivo:**
  - Proponer banners o imágenes para publicar vacantes.

- **Qué necesita:**
  - **Vista:** también `admin_vacantes.php`:
    - Sección para "Recursos visuales".
  - **BD:**
    - Tabla `recursos_visuales_vacantes`:
      - id, id_vacante, url_imagen, tipo, fecha_creacion, etc.
  - **Servicios externos:**
    - API de generación de imágenes (DALL·E, Midjourney API, etc.), o carga manual desde el admin.

---

### 4.4 Agente de Seguimiento de Ofertas / Publicaciones (PENDIENTE / REQUIERE MÁS)

- **Objetivo:**
  - Controlar en qué portales/redes está publicada cada vacante.
  - Registrar fechas de publicación y expiración.
  - Recordar renovar o cerrar vacantes.

- **Qué necesita:**
  - **BD:**
    - Tablas:
      - `publicaciones_vacantes` (id, id_vacante, canal, fecha_publicacion, fecha_expiracion, estado).
  - **Vista:**
    - Sección en `admin_vacantes.php` para ver y gestionar publicaciones.
  - **Cron jobs (tareas programadas):**
    - Script que corra cada día:
      - Detecte vacantes por expirar.
      - Marque vencidas.
      - Genere recordatorios (puede disparar notificaciones o registros en otra tabla).

---

## 5. Integraciones Técnicas Pendientes (NO críticas para el flujo principal)

- **Envío real de correos:**
  - Integrar PHPMailer o similar.
  - Usado por:
    - Agente de `feedback_rechazo`.
    - Agente de `calendarizacion` (notificaciones).
    - Futuros agentes (onboarding, recordatorios).

- **Cron jobs:**
  - Recordatorios de onboarding (cuando se acerca `fecha_limite_ingreso`).
  - Recordatorios de feedback post-entrevista.
  - Recordatorios de publicaciones de vacantes próximas a vencer.

- **Seguridad y roles:**
  - Definir bien qué vistas ve:
    - Reclutador.
    - Cliente interno.
    - Admin general.

---

## 6. Estado Actual del Sistema

- **Flujo completo de reclutamiento (FUNCIONANDO):**
  - Postulación (`postular.php`) → Segmentación → Detección de riesgos.
  - Evaluación y decisiones (`admin_evaluacion.php`):
    - Programar entrevista.
    - Rechazar con feedback.
    - Contratar e iniciar onboarding.
  - Validación con cliente interno (`admin_validacion.php`).
  - Onboarding (`admin_onboarding.php`).

- **Agentes funcionando (6):**
  - Segmentación (Fit).
  - Detección de Riesgos.
  - Calendarización.
  - Feedback Rechazo.
  - Validación Proceso.
  - Seguimiento Ingreso / Onboarding.

- **Agentes pendientes (4 principales):**
  - Seguimiento Post-Entrevista.
  - Redacción IA (vacantes).
  - Diseño Visual (vacantes).
  - Seguimiento Ofertas / Publicaciones.

Con lo que ya tienes, el sistema cubre el ciclo principal de reclutamiento con automatización básica; lo que falta son agentes más avanzados (IA de contenido, diseño, seguimiento post-entrevista y de ofertas) y la vista `admin_vacantes.php` para explotar al máximo esos agentes.