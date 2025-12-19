<?php
// =======================================================
// AGENTE DE CALENDARIZACIÓN DE ENTREVISTAS (MODIFICADO)
// Lógica de Flexibilidad: Prioridad estricta -> Candidato Flexible -> Candidato Extendido.
// =======================================================

require_once("config.php");
$conn = connection();


/* =======================================================
// 1. DISPONIBILIDAD (SIN CAMBIOS)
// ======================================================= */

function obtenerDisponibilidadCandidato($conn, $idCliente) {
    $data = [];
    $q = $conn->prepare("
        SELECT fecha_referencia AS fecha, hora_inicio, hora_fin
        FROM disponibilidaddelequipo
        WHERE idClientes = ?
          AND estado = 'Disponible'
        ORDER BY fecha_referencia, hora_inicio
    ");
    $q->bind_param("i", $idCliente);
    $q->execute();
    $r = $q->get_result();
    while ($row = $r->fetch_assoc()) $data[] = $row;
    $q->close();
    return $data;
}

function obtenerDisponibilidadReclutador($conn, $idReclutador) {
    $data = [];
    $q = $conn->prepare("
        SELECT fecha_referencia AS fecha, hora_inicio, hora_fin
        FROM disponibilidades_rrhh
        WHERE idreclutadores = ?
          AND estado = 'Disponible'
        ORDER BY fecha_referencia, hora_inicio
    ");
    $q->bind_param("i", $idReclutador);
    $q->execute();
    $r = $q->get_result();
    while ($row = $r->fetch_assoc()) $data[] = $row;
    $q->close();
    return $data;
}

/* =======================================================
// 2. COINCIDENCIAS (SIN CAMBIOS)
// ======================================================= */

function calcularCoincidencias($dispA, $dispB) {
    $slots = [];

    foreach ($dispA as $a) {
        foreach ($dispB as $b) {

            if ($a['fecha'] !== $b['fecha']) continue;

            $inicio = max(
                strtotime($a['fecha'].' '.$a['hora_inicio']),
                strtotime($b['fecha'].' '.$b['hora_inicio'])
            );

            $fin = min(
                strtotime($a['fecha'].' '.$a['hora_fin']),
                strtotime($b['fecha'].' '.$b['hora_fin'])
            );

            if ($fin > $inicio) {
                $slots[] = [
                    'fecha' => $a['fecha'],
                    'hora_inicio' => date('H:i:s', $inicio),
                    'hora_fin' => date('H:i:s', $fin)
                ];
            }
        }
    }

    usort($slots, fn($x,$y) =>
        strtotime($x['fecha'].' '.$x['hora_inicio'])
        <=>
        strtotime($y['fecha'].' '.$y['hora_inicio'])
    );

    return $slots;
}

/* =======================================================
// 2.1 SUB-HORARIOS IA (Duración y Salto de 60 min)
// ======================================================= */

function generarSubHorarios($fecha, $horaInicio, $horaFin, $duracionMin = 60, $saltoMin = 60) {
    $horarios = [];

    $inicio = strtotime("$fecha $horaInicio");
    $fin = strtotime("$fecha $horaFin");

    while (($inicio + ($duracionMin * 60)) <= $fin) {
        $horarios[] = [
            'fecha' => $fecha,
            'hora_inicio' => date('H:i:s', $inicio),
            'hora_fin' => date('H:i:s', $inicio + ($duracionMin * 60))
        ];
        $inicio += ($saltoMin * 60);
    }

    return $horarios;
}

/**
 * NUEVA FUNCIÓN: Genera horarios potenciales para la fase de 'Extensión/Flexibilidad' 
 * Asumimos un rango máximo de 5 días y 12 horas por día (8:00 a 20:00).
 *
 * @return array Lista de slots potenciales
 */
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

/* =======================================================
// 3. EVITAR REPETIR SLOT EN BASE DE DATOS (SIN CAMBIOS)
// ======================================================= */

function slotYaUsado($conn, $idCliente, $idReclutador, $slot) {
    $q = $conn->prepare("
        SELECT 1
        FROM entrevistas
        WHERE idClientes = ?
          AND idReclutador = ?
          AND fecha = ?
          AND hora_inicio = ?
          AND hora_fin = ?
    ");
    $q->bind_param(
        "iisss",
        $idCliente,
        $idReclutador,
        $slot['fecha'],
        $slot['hora_inicio'],
        $slot['hora_fin']
    );
    $q->execute();
    return $q->get_result()->num_rows > 0;
}

/* =======================================================
// 4. GUARDAR PROPUESTA IA (SIN CAMBIOS)
// ======================================================= */

function guardarPropuestaIA($conn, $idCliente, $idReclutador, $idVacante, $slot, $estado) {
    // Obtener o crear id_aplicacion
    $sql_app = "SELECT id_aplicacion FROM aplicaciones WHERE id_candidato = ? AND id_vacante = ? LIMIT 1";
    $stmt_app = $conn->prepare($sql_app);
    $id_aplicacion = null;
    
    if ($stmt_app) {
        $stmt_app->bind_param("ii", $idCliente, $idVacante);
        $stmt_app->execute();
        $result_app = $stmt_app->get_result();
        
        if ($result_app && $result_app->num_rows > 0) {
            $row_app = $result_app->fetch_assoc();
            $id_aplicacion = $row_app['id_aplicacion'];
        } else {
            // Si no existe aplicación, crear una nueva
            $sql_create_app = "INSERT INTO aplicaciones (id_candidato, id_vacante, fecha_aplicacion, status_aplicacion) VALUES (?, ?, NOW(), 'En proceso')";
            $stmt_create = $conn->prepare($sql_create_app);
            if ($stmt_create) {
                $stmt_create->bind_param("ii", $idCliente, $idVacante);
                if ($stmt_create->execute()) {
                    $id_aplicacion = $conn->insert_id;
                }
                $stmt_create->close();
            }
        }
        $stmt_app->close();
    }
    
    if (!$id_aplicacion) {
        throw new Exception("No se pudo obtener o crear la aplicación");
    }
    
    // Verificar si ya existe una entrevista para esta aplicación
    $sql_check = "SELECT id_entrevista FROM entrevistas WHERE id_aplicacion = ? LIMIT 1";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $id_aplicacion);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $existe_entrevista = $result_check->num_rows > 0;
    $stmt_check->close();
    
    if ($existe_entrevista) {
        // Si ya existe, actualizar la entrevista existente
        $q = $conn->prepare("
            UPDATE entrevistas
            SET idClientes = ?, idVacante = ?, idReclutador = ?, fecha = ?, hora_inicio = ?, hora_fin = ?, estado = ?
            WHERE id_aplicacion = ?
        ");
        $q->bind_param(
            "iiiisssi",
            $idCliente,
            $idVacante,
            $idReclutador,
            $slot['fecha'],
            $slot['hora_inicio'],
            $slot['hora_fin'],
            $estado,
            $id_aplicacion
        );
    } else {
        // Si no existe, insertar nueva
        $q = $conn->prepare("
            INSERT INTO entrevistas
            (id_aplicacion, idClientes, idVacante, idReclutador, fecha, hora_inicio, hora_fin, estado)
            VALUES (?,?,?,?,?,?,?,?)
        ");
        $q->bind_param(
            "iiiissss",
            $id_aplicacion,
            $idCliente,
            $idVacante,
            $idReclutador,
            $slot['fecha'],
            $slot['hora_inicio'],
            $slot['hora_fin'],
            $estado
        );
    }
    $q->execute();
    $q->close();
}

/* =======================================================
// 4.2 LOGS Y CALENDAR (SIN CAMBIOS)
// ======================================================= */

function guardarLogComunicacion($conn, $idCliente, $mensaje, $canal, $estado = 'Enviado') {
    $q = $conn->prepare("
        INSERT INTO canal_comunicacion
        (idClientes, tipo_origen, id_origen, tipo_destino, id_destino, canal, fecha, hora, mensaje, estado, automatica)
        VALUES (?, 'Sistema', 1, 'Candidato', ?, ?, CURDATE(), CURTIME(), ?, ?, 1)
    ");
    
    $q->bind_param(
        "iisss",
        $idCliente,
        $idCliente,
        $canal,
        $mensaje,
        $estado
    );
    
    $q->execute();
    $q->close();
}
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


/* =======================================================
// 5. AGENTE/BUSCADOR DE SLOTS (MODIFICADO PARA TRES FASES PRECISO)
// ======================================================= */

/**
 * Helper: Transforma rangos de disponibilidad en slots de 60 minutos y los ordena.
 */
function expandirDisponibilidadEnSlots($disp) {
    $slots = [];
    foreach ($disp as $d) {
        $slots = array_merge($slots, generarSubHorarios($d['fecha'], $d['hora_inicio'], $d['hora_fin']));
    }
    // Filtrar duplicados y ordenar
    $uniqueSlots = array_map("unserialize", array_unique(array_map("serialize", $slots)));
    usort($uniqueSlots, fn($x,$y) => strtotime($x['fecha'].' '.$x['hora_inicio']) <=> strtotime($y['fecha'].' '.$y['hora_inicio']));
    return $uniqueSlots;
}

/**
 * Función principal: Encuentra el siguiente slot disponible con lógica de fases.
 * @return array|null [slot, source]
 */
function findNextAvailableSlot($conn, $idCliente, $idReclutador, $slotsRechazados = []) {
    $REJECTION_THRESHOLD = 3; // Límite de rechazos para pasar a la Fase 3
    
    $dispCand = obtenerDisponibilidadCandidato($conn, $idCliente);
    $dispRec = obtenerDisponibilidadReclutador($conn, $idReclutador);
    
    // Convertir slots rechazados a un formato simple para la verificación (sin 'source')
    $rechazadosData = array_map(function($s) {
        return ['fecha' => $s['fecha'], 'hora_inicio' => $s['hora_inicio'], 'hora_fin' => $s['hora_fin']];
    }, $slotsRechazados);
    
    
    // =====================================================
    // FASE 1: COINCIDENCIA (DENTRO DE LA DISPONIBILIDAD DE AMBOS)
    // =====================================================
    $coincidenciasRango = calcularCoincidencias($dispCand, $dispRec);
    $slotsPhase1 = expandirDisponibilidadEnSlots($coincidenciasRango);

    foreach ($slotsPhase1 as $slot) {
        // Utilizamos la función auxiliar para la verificación de rechazos
        if (!slotYaUsado($conn, $idCliente, $idReclutador, $slot) && 
            !in_array($slot, $rechazadosData, true)) {
            // Regresamos el slot y la fuente
            return ['slot' => $slot, 'source' => 'Coincidencia'];
        }
    }
    
    // =====================================================
    // FASE 2: SOLO CANDIDATO (Disponibilidad original del candidato, sin coincidencia con reclutador)
    // Se propone esperando que el reclutador se ajuste.
    // =====================================================
    
    $slotsCandidatoOriginal = expandirDisponibilidadEnSlots($dispCand);
    
    // Excluir slots que ya fueron propuestos y agotados en FASE 1
    $slotsPhase2 = array_filter($slotsCandidatoOriginal, function($s) use ($slotsPhase1) {
        // Esta verificación es un poco "cara", pero asegura que no se repitan slots de Fase 1.
        return !in_array($s, $slotsPhase1, true);
    });
    
    foreach ($slotsPhase2 as $slot) {
        if (!slotYaUsado($conn, $idCliente, $idReclutador, $slot) && 
            !in_array($slot, $rechazadosData, true)) {
            return ['slot' => $slot, 'source' => 'Candidato'];
        }
    }


    // =====================================================
    // FASE 3: CANDIDATO FLEXIBLE/EXTENDIDO (FUERA DE SU DISPONIBILIDAD MARCADA)
    // Se activa solo si las fases anteriores se agotaron Y el candidato sigue rechazando.
    // =====================================================
    if (count($slotsRechazados) >= $REJECTION_THRESHOLD) {
        
        $slotsExtendidos = generarHorariosExtendidos();
        
        // Slots que ya fueron propuestos o están en la disponibilidad marcada.
        // Usamos el set de slots de la Fase 1 y 2 para no duplicar.
        $slotsYaVistos = array_merge($slotsPhase1, $slotsPhase2);
        
        // 1. Filtrar los slots extendidos para que NO sean los ya vistos (Fase 1 y 2)
        $slotsPhase3 = array_filter($slotsExtendidos, function($s) use ($slotsYaVistos) {
            return !in_array($s, $slotsYaVistos, true);
        });
        
        // 2. Filtrar los slots extendidos para que el reclutador esté disponible (solo buscamos coincidencia de inicio y fin, ya que los slots de 60 min son discretos)
        $slotsReclutador = expandirDisponibilidadEnSlots($dispRec); // Slots de 60 min del reclutador
        
        $slotsPhase3_CoincidenciaRec = array_filter($slotsPhase3, function($s) use ($slotsReclutador) {
            // Verificar si el slot extendido cae DENTRO de un slot de 60 minutos del reclutador
            foreach ($slotsReclutador as $rec_slot) {
                if ($s['fecha'] === $rec_slot['fecha'] && 
                    $s['hora_inicio'] === $rec_slot['hora_inicio']) {
                    return true; // Coincidencia exacta de slot de 60 min
                }
            }
            return false;
        });

        // Re-ordenar por si el filtrado alteró el orden cronológico
        usort($slotsPhase3_CoincidenciaRec, fn($x,$y) => strtotime($x['fecha'].' '.$x['hora_inicio']) <=> strtotime($y['fecha'].' '.$y['hora_inicio']));


        foreach ($slotsPhase3_CoincidenciaRec as $slot) {
            // Verificar contra rechazados y ya usados en DB
            if (!slotYaUsado($conn, $idCliente, $idReclutador, $slot) && 
                !in_array($slot, $rechazadosData, true)) {
                
                // Slot extendido encontrado (el "hasta el fin" del candidato)
                return ['slot' => $slot, 'source' => 'Candidato_Extendido'];
            }
        }
    }
    // =====================================================
    // FASE 4: FORZADO DEL SISTEMA (ÚLTIMO RECURSO)
    // NO elimina el return null, solo se ejecuta antes
    // =====================================================

    $fecha = new DateTime('tomorrow');
    while (in_array($fecha->format('w'), [0, 6])) { // Saltar sábado y domingo
        $fecha->modify('+1 day');
    }

    return [
        'slot' => [
            'fecha' => $fecha->format('Y-m-d'),
            'hora_inicio' => '09:00:00',
            'hora_fin' => '10:00:00'
        ],
        'source' => 'Sistema_Forzado'
    ];




    return null; // No disponible
}


/* =======================================================
// 6. UTILIDADES (SIN CAMBIOS)
// ======================================================= */

function debeMostrarBotonRegresar($mensaje) {
    if (!$mensaje) return false;
    return str_contains($mensaje, 'No hay más horarios');
}


/* =======================================================
// 7. MANEJO DE ESTADO Y ACCIONES (AJUSTADO PARA FUENTE DEL SLOT)
// ======================================================= */

$mensaje = null;
$slot = null;
$slotSource = null; // Fuente del slot ('Coincidencia', 'Candidato', 'Candidato_Extendido')
$slotsRechazados = []; // Almacena slots {fecha, hora_inicio, hora_fin} que no se deben volver a proponer.
$slotHistory = []; // Historial de slots {fecha, hora_inicio, hora_fin, source}
$currentSlotIndex = -1;

// 1. Cargar slots rechazados previos (si existen)
if (isset($_POST['slotsRechazadosJson']) && $_POST['slotsRechazadosJson']) {
    $slotsRechazados = json_decode($_POST['slotsRechazadosJson'], true) ?? [];
}

// 2. Cargar historial y estado para navegación (incluye el source)
if (isset($_POST['slotHistoryJson']) && $_POST['slotHistoryJson']) {
    $slotHistory = json_decode($_POST['slotHistoryJson'], true) ?? [];
}
if (isset($_POST['currentSlotIndex']) && $_POST['currentSlotIndex'] !== '') {
    $currentSlotIndex = intval($_POST['currentSlotIndex']);
}


// 3. Procesar acciones (CONFIRMAR/RECHAZAR/VOLVER)
if (isset($_POST['accion'])) {

    $accion = $_POST['accion'];

    $idCliente      = intval($_POST['idCliente'] ?? 0);
    $idReclutador = intval($_POST['idReclutador'] ?? 0);
    $idVacante      = intval($_POST['idVacante'] ?? 0);
    $fecha          = $_POST['fecha'] ?? '';
    $hora_inicio    = $_POST['hora_inicio'] ?? '';
    $hora_fin       = $_POST['hora_fin'] ?? '';
    $slotSource     = $_POST['slotSource'] ?? null; // Recuperar la fuente

    $slot_propuesto_anterior = [
        'fecha' => $fecha,
        'hora_inicio' => $hora_inicio,
        'hora_fin' => $hora_fin,
        'source' => $slotSource 
    ];

    if ($accion === 'CONFIRMAR') {
        if ($idCliente && $idReclutador && $idVacante && $fecha) {
              
            // 1. GUARDAR EN DB LOCAL
            // Solo pasamos los datos del slot, no el 'source' a la DB.
             guardarPropuestaIA($conn, $idCliente, $idReclutador, $idVacante, $slot_propuesto_anterior, 'Programado por IA');
             
            // 2. PREPARAR DETALLES PARA GÉMINIS/CALENDAR
            $detallesEvento = [
                'summary' => 'Entrevista Técnica para Vacante ' . $idVacante, 
                'attendees' => [],
                'description' => 'Enlace de la videollamada y detalles de la entrevista.',
            ];
            
            // 3. INTEGRAR CON GOOGLE CALENDAR
            $programado = programarEnGoogleCalendar($slot_propuesto_anterior, $detallesEvento);

            if ($programado) {
                
                // 4. NUEVA INSERCIÓN EN LOG DE COMUNICACIÓN
                $log_mensaje = "Entrevista confirmada y evento de calendario enviado al candidato para la vacante " . $idVacante . " en la fecha " . $fecha . " de " . $hora_inicio . " a " . $hora_fin . ". Fuente: " . $slotSource;
                guardarLogComunicacion($conn, $idCliente, $log_mensaje, 'Calendar', 'Enviado');
                
                // Redirección inmediata tras confirmación exitosa
                header("Location: disponibilidades.php");
                exit;
            } else {
                $mensaje = "❌ Error: La programación en Google Calendar falló.";
            }
        } else {
             $mensaje = "❌ Error: Faltan datos para confirmar el horario.";
        }
        
    } elseif ($accion === 'RECHAZAR') {

        if ($currentSlotIndex === count($slotHistory) - 1) {
            // Caso 1: Estamos al final del historial (es una nueva propuesta).
            
            // A. Agregar el slot actual a la lista de rechazados (solo datos del slot, sin 'source')
            if ($fecha) {
                $slotRechazadoData = ['fecha' => $fecha, 'hora_inicio' => $hora_inicio, 'hora_fin' => $hora_fin];
                $slotsRechazados[] = $slotRechazadoData;
                // Eliminar duplicados en rechazados
                $slotsRechazados = array_map("unserialize", array_unique(array_map("serialize", $slotsRechazados)));
            }

            // B. Buscar el siguiente slot realmente disponible
            $result = findNextAvailableSlot($conn, $idCliente, $idReclutador, $slotsRechazados);
            
            // C. Actualizar historial y estado
            if ($result) {
                $slot = $result['slot'];
                $slotSource = $result['source'];
                // Agregar el source al slot antes de guardarlo en el historial
                $slot['source'] = $slotSource; 
                
                $slotHistory[] = $slot;
                $currentSlotIndex = count($slotHistory) - 1; // Asegurar que esté en el último
                $mensaje = "🔁 Horario rechazado. Nuevo horario generado por IA.";
            } else {
                // Si no hay más slots, mantener el último del historial si existe
                if (!empty($slotHistory) && $currentSlotIndex >= 0 && $currentSlotIndex < count($slotHistory)) {
                    $slot = $slotHistory[$currentSlotIndex];
                    $slotSource = $slot['source'] ?? null;
                } else {
                    $slot = null;
                    $slotSource = null;
                }
                $mensaje = "⚠️ No hay más horarios disponibles para el candidato/reclutador.";
            }
                
        } elseif ($currentSlotIndex < count($slotHistory) - 1) {
            // Caso 2: Estamos navegando en el historial. Solo avanzamos.
            $currentSlotIndex++;
            $slot = $slotHistory[$currentSlotIndex];
            $slotSource = $slot['source']; // Cargar el source del historial
            $mensaje = "⏭️ Avance a la siguiente propuesta.";
        }

    } elseif ($accion === 'VOLVER') {
        if ($currentSlotIndex > 0) {
            // Moverse hacia atrás en el historial.
            $currentSlotIndex--;
            $slot = $slotHistory[$currentSlotIndex];
            $slotSource = $slot['source']; // Cargar el source del historial
            $mensaje = "⏪ Volviendo a la propuesta anterior.";
        } else {
            // Ya está en el primer slot.
            if (!empty($slotHistory)) {
                $slot = $slotHistory[0]; // Asegurar que el slot actual esté cargado
                $slotSource = $slot['source'];
            }
            $mensaje = "⚠️ Estás en la primera propuesta. Rechaza para avanzar.";
        }
    }
}

/* =======================================================
// 8. CREAR AGENTE / INICIAR (AJUSTADO PARA FUENTE DEL SLOT)
// ======================================================= */

if (isset($_POST['action']) && $_POST['action'] === 'iniciar') {
    // Primera búsqueda de slot al iniciar el agente
    $result = findNextAvailableSlot(
        $conn,
        $_POST['idCliente'],
        $_POST['idReclutador'],
        [] // Lista de rechazos vacía
    );
    
    if ($result) {
        $slot = $result['slot'];
        $slotSource = $result['source'];
        
        // Agregar el source al slot antes de guardarlo en el historial
        $slot['source'] = $slotSource; 

        $slotHistory = [$slot];
        $currentSlotIndex = 0;
        $mensaje = "✅ Se encontró un posible horario. Por favor, confirma o rechaza para ver el siguiente.";
    } else {
        $slotHistory = [];
        $currentSlotIndex = -1;
        $mensaje = "⚠️ No se encontró ningún horario disponible.";
    }
}

// 9. Lógica de re-carga de slot (Si la acción no lo proveyó, pero el historial lo tiene)
if (!$slot && $currentSlotIndex >= 0 && isset($slotHistory[$currentSlotIndex])) {
    $slot = $slotHistory[$currentSlotIndex];
    $slotSource = $slot['source']; // Asegurar que el source se cargue para el mensaje
}

// Re-set POST variables for display y actualizar fecha/hora del slot actual
if ($slot) {
    $idCliente = $_POST['idCliente'] ?? $idCliente;
    $idReclutador = $_POST['idReclutador'] ?? $idReclutador;
    $idVacante = $_POST['idVacante'] ?? $idVacante;
    // Actualizar fecha y hora del slot actual para el formulario
    $fecha = $slot['fecha'];
    $hora_inicio = $slot['hora_inicio'];
    $hora_fin = $slot['hora_fin'];
    if (isset($slot['source'])) {
        $slotSource = $slot['source'];
    }
}


// Codificar el estado para el siguiente post
$slotsRechazadosJson = htmlspecialchars(json_encode($slotsRechazados), ENT_QUOTES, 'UTF-8');
$slotHistoryJson = htmlspecialchars(json_encode($slotHistory), ENT_QUOTES, 'UTF-8');


?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Agente IA</title>
<style>
body {
    font-family: Arial;
    background:#f4f6f8;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}
.card {
    background:#fff;
    padding:30px;
    border-radius:10px;
    box-shadow:0 10px 25px rgba(0,0,0,.1);
    text-align:center;
    max-width:420px;
}
button {
    margin:10px;
    padding:10px 20px;
    border:none;
    border-radius:6px;
    cursor:pointer;
    white-space: nowrap;
}
.confirmar { background:#2e7d32; color:#fff; }
.rechazar { background:#c62828; color:#fff; }
.volver { background:#ff9800; color:#fff; }
.alerta { background:#ff9800; color:#fff; padding: 10px; border-radius: 5px; margin: 15px 0; font-weight: bold; }
</style>
</head>

<body>
<div class="card">
<h2>🤖 Agente de Entrevistas</h2>

<?php if ($mensaje) echo "<p><strong>$mensaje</strong></p>"; ?>

<?php
// Muestra el slot propuesto si existe
if ($slot) {
    
    // ===================================================
    // NUEVO REQUERIMIENTO: Mostrar mensaje si el slot es de la Fase 3 (Extendido)
    // ===================================================
    if ($slotSource === 'Candidato_Extendido') {
        echo "<div class='alerta'>⚠️ ¡ATENCIÓN! Su disponibilidad registrada ya se agotó. Este horario (disponible para el Reclutador) está **fuera de tu rango inicial**. ¿Deseas aceptarlo?</div>";
    }
    
?>
    <p><strong>Horario propuesto:</strong></p>
    <p><?= htmlspecialchars($slot['fecha']) ?><br><?= date('H:i', strtotime($slot['hora_inicio'])) ?> - <?= date('H:i', strtotime($slot['hora_fin'])) ?></p>

    <form method="POST">
        <input type="hidden" name="idCliente" value="<?= htmlspecialchars($idCliente) ?>">
        <input type="hidden" name="idReclutador" value="<?= htmlspecialchars($idReclutador) ?>">
        <input type="hidden" name="idVacante" value="<?= htmlspecialchars($idVacante) ?>">
        
        <input type="hidden" name="fecha" value="<?= htmlspecialchars($slot['fecha']) ?>">
        <input type="hidden" name="hora_inicio" value="<?= htmlspecialchars($slot['hora_inicio']) ?>">
        <input type="hidden" name="hora_fin" value="<?= htmlspecialchars($slot['hora_fin']) ?>">
        <input type="hidden" name="slotSource" value="<?= htmlspecialchars($slotSource) ?>"> 
        <input type="hidden" name="slotsRechazadosJson" value="<?= $slotsRechazadosJson ?>">
        <input type="hidden" name="slotHistoryJson" value="<?= $slotHistoryJson ?>">
        <input type="hidden" name="currentSlotIndex" value="<?= htmlspecialchars($currentSlotIndex) ?>">
        
        <div style="display:flex; justify-content:center; flex-wrap:wrap;">
            <button class="confirmar" name="accion" value="CONFIRMAR">✅ Confirmar</button>
            <button class="rechazar" name="accion" value="RECHAZAR">❌ Rechazar</button>
            
            <?php 
            // Mostrar botón VOLVER si hay un historial anterior
            if ($currentSlotIndex > 0) { ?>
                <button class="volver" name="accion" value="VOLVER">⏪ Anterior</button>
            <?php } ?>
        </div>
    </form>
<?php } ?>

<?php
// Muestra el botón de regreso SOLO si no hay más slots disponibles
if (debeMostrarBotonRegresar($mensaje)) {
?>
    <button onclick="window.location.href='disponibilidades.php'">⬅ Regresar</button>
<?php } ?>

</div>
</body>
</html>