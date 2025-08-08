<?php
// Activar mostrado de errores en pantalla (SOLO PARA DESARROLLO)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurar logging detallado
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Log de cada petición para debugging
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
$requestUri = $_SERVER['REQUEST_URI'] ?? 'UNKNOWN';
$requestBody = file_get_contents('php://input');
error_log("REQUEST [$requestMethod] $requestUri - BODY: $requestBody");

// Bootstrap común y dependencias
require_once '../includes/bootstrap.php';
require_once '../includes/functions.php';
require_once '../includes/api/jwt.php';

// Timezone ya establecido en bootstrap

// Verificar conexión a la base de datos
if (!isset($conn) || mysqli_connect_errno()) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión a la base de datos: ' . mysqli_connect_error(),
        'data' => null
    ]);
    exit;
}

// Configurar headers CORS y JSON
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificar autenticación usando JWT
try {
    $payload = requireJWTAuth();
    $currentUserId = $payload['user_id'] ?? null;
    $userRole = $payload['role'] ?? '';
    
    if (!$currentUserId) {
        apiResponse(false, 'Usuario no identificado', null, 401);
    }
} catch (Exception $e) {
    apiResponse(false, 'Error de autenticación: ' . $e->getMessage(), null, 401);
}

// Función para verificar si dos rangos de tiempo se solapan
function isOverlapping($start1, $end1, $start2, $end2) {
    // Validación de formatos
    $start1Timestamp = strtotime($start1);
    $end1Timestamp = strtotime($end1);
    $start2Timestamp = strtotime($start2);
    $end2Timestamp = strtotime($end2);
    
    if ($start1Timestamp === false || $end1Timestamp === false ||
        $start2Timestamp === false || $end2Timestamp === false) {
        error_log("ERROR en isOverlapping: formato de fecha inválido");
        error_log("  start1: $start1 (" . ($start1Timestamp === false ? 'inválido' : 'válido') . ")");
        error_log("  end1: $end1 (" . ($end1Timestamp === false ? 'inválido' : 'válido') . ")");
        error_log("  start2: $start2 (" . ($start2Timestamp === false ? 'inválido' : 'válido') . ")");
        error_log("  end2: $end2 (" . ($end2Timestamp === false ? 'inválido' : 'válido') . ")");
        return false;
    }
    
    // Un rango se solapa con otro si:
    // El inicio del primero es antes del fin del segundo Y
    // El inicio del segundo es antes del fin del primero
    $overlaps = ($start1Timestamp < $end2Timestamp && $start2Timestamp < $end1Timestamp);
    
    return $overlaps;
}

// Función para contar cuántas citas hay en un horario específico
function countAppointmentsInSlot($startSlot, $endSlot, $appointments) {
    error_log("countAppointmentsInSlot - Verificando slot: $startSlot - $endSlot");
    $count = 0;
    
    foreach ($appointments as $appointment) {
        $appointmentStart = $appointment['start_time'];
        $appointmentEnd = $appointment['end_time'];
        $appointmentId = $appointment['id'] ?? 'desconocido';
        
        $overlaps = isOverlapping($startSlot, $endSlot, $appointmentStart, $appointmentEnd);
        
        if ($overlaps) {
            $count++;
            error_log("Cita ID: $appointmentId ($appointmentStart - $appointmentEnd) se solapa con el slot");
        }
    }
    
    error_log("Slot $startSlot - $endSlot tiene $count citas existentes");
    return $count;
}

// Función para obtener los horarios disponibles
function getAvailableSlots($startDate, $endDate, $calendarType = null, $slotDuration = 3600) {
    // Log inicio de función
    error_log("getAvailableSlots - Iniciando con parámetros: startDate=$startDate, endDate=$endDate, calendarType=$calendarType, slotDuration=$slotDuration");
    
    try {
        // Validar parámetros
        if (empty($startDate) || empty($endDate)) {
            throw new InvalidArgumentException('Las fechas de inicio y fin son obligatorias');
        }
        
        // Validar que las fechas tengan formato válido
        $formatosValidos = [
            'Y-m-d H:i:s',
            'Y-m-d\TH:i:s',
            'Y-m-d H:i',
            'Y-m-d\TH:i',
            'Y-m-d'
        ];
        
        $startDateTime = null;
        $endDateTime = null;
        
        // Intentar diferentes formatos de fecha
        foreach ($formatosValidos as $formato) {
            $startTmp = DateTime::createFromFormat($formato, $startDate);
            $endTmp = DateTime::createFromFormat($formato, $endDate);
            
            if ($startTmp && $endTmp) {
                $startDateTime = $startTmp;
                $endDateTime = $endTmp;
                error_log("Formato de fecha válido encontrado: $formato");
                break;
            }
        }
        
        if (!$startDateTime || !$endDateTime) {
            error_log("Error: No se pudo parsear las fechas. startDate=$startDate, endDate=$endDate");
            throw new InvalidArgumentException('Formato de fecha inválido. Use uno de los formatos soportados.');
        }
        
        // Si solo se proporcionó la fecha sin hora, establecer horas por defecto
        if ($startDate === $startDateTime->format('Y-m-d')) {
            $startDateTime->setTime(0, 0, 0);
            error_log("Fecha sin hora detectada para startDate, estableciendo a medianoche");
        }
        
        if ($endDate === $endDateTime->format('Y-m-d')) {
            $endDateTime->setTime(23, 59, 59);
            error_log("Fecha sin hora detectada para endDate, estableciendo a fin del día");
        }
        
        // Convertir a formato de cadena consistente para la consulta SQL
        $startDateFormatted = $startDateTime->format('Y-m-d H:i:s');
        $endDateFormatted = $endDateTime->format('Y-m-d H:i:s');
        
        error_log("Fechas formateadas para SQL: start=$startDateFormatted, end=$endDateFormatted");
        
        // Validar duración del slot
        if (!is_numeric($slotDuration) || $slotDuration <= 0) {
            throw new InvalidArgumentException('La duración del slot debe ser un número positivo');
        }
        
        // Validar tipo de calendario
        if ($calendarType && !in_array($calendarType, ['estetico', 'veterinario', 'general'])) {
            throw new InvalidArgumentException('Tipo de calendario inválido');
        }
        
        // Obtener citas existentes en el rango de fechas
        error_log("Consultando citas existentes para el rango: $startDateFormatted - $endDateFormatted");
        $appointments = getAppointments($startDateFormatted, $endDateFormatted, $calendarType);
        
        // Validar que se obtuvieron las citas correctamente (debería ser un array, aunque esté vacío)
        if (!is_array($appointments)) {
            error_log("Error: getAppointments no devolvió un array: " . var_export($appointments, true));
            throw new RuntimeException('Error al obtener las citas existentes');
        }
        
        error_log("Se encontraron " . count($appointments) . " citas existentes en el rango");
        
        // Definir horario de atención (8:00 AM - 6:00 PM)
        $openingTime = 8; // 8 AM
        $closingTime = 18; // 6 PM
        
        // Horarios disponibles
        $availableSlots = [];
        
        // Usar los objetos DateTime ya creados
        $currentDate = clone $startDateTime;
        $currentDate->setTime(0, 0, 0); // Establecer a medianoche
        
        // Iterar por cada día en el rango
        while ($currentDate <= $endDateTime) {
            $dayOfWeek = $currentDate->format('N');
            
            // Solo considerar días laborables (1-6, lunes a sábado)
            if ($dayOfWeek >= 1 && $dayOfWeek <= 6) {
                $dayStart = clone $currentDate;
                $dayStart->setTime($openingTime, 0, 0);
                
                $dayEnd = clone $currentDate;
                $dayEnd->setTime($closingTime, 0, 0);
                
                // Verificar si estamos consultando para hoy
                $now = new DateTime();
                $now->modify('+3 hours'); // Agregar 3 horas (citas con al menos 3 horas de anticipación)
                
                if ($currentDate->format('Y-m-d') === $now->format('Y-m-d') && $dayStart < $now) {
                    $dayStart = $now;
                    // Redondear a la hora siguiente
                    $minutes = (int)$dayStart->format('i');
                    if ($minutes > 0) {
                        $dayStart->modify('+' . (60 - $minutes) . ' minutes');
                    }
                }
                
                error_log("Procesando día: " . $currentDate->format('Y-m-d') . " - horario: $openingTime:00 - $closingTime:00");
                
                // Dividir el día en slots según la duración especificada
                $slotStart = clone $dayStart;
                $slotDurationSecs = $slotDuration; // Por defecto 1 hora (3600 segundos)
                
                while ($slotStart < $dayEnd) {
                    $slotEnd = clone $slotStart;
                    $slotEnd->modify('+' . $slotDurationSecs . ' seconds');
                    
                    if ($slotEnd > $dayEnd) {
                        break;
                    }
                    
                    $slotStartStr = $slotStart->format('Y-m-d H:i:s');
                    $slotEndStr = $slotEnd->format('Y-m-d H:i:s');
                    
                    // Verificar cuántas citas ya existen en este slot
                    $existingAppointments = countAppointmentsInSlot(
                        $slotStartStr,
                        $slotEndStr,
                        $appointments
                    );
                    
                    // Si hay menos de 2 citas simultáneas, el slot está disponible
                    if ($existingAppointments < 2) {
                        $availableSlots[] = [
                            'start' => $slotStartStr,
                            'end' => $slotEndStr,
                            'available_spots' => 2 - $existingAppointments // Cuántas citas más se pueden agendar
                        ];
                    }
                    
                    // Avanzar al siguiente slot
                    $slotStart = clone $slotEnd;
                }
            } else {
                error_log("Saltando día no laborable: " . $currentDate->format('Y-m-d'));
            }
            
            // Avanzar al día siguiente
            $currentDate->modify('+1 day');
        }
        
        error_log("getAvailableSlots - Finalizado. Se encontraron " . count($availableSlots) . " slots disponibles");
        return $availableSlots;
        
    } catch (Exception $e) {
        error_log("Error en getAvailableSlots: " . $e->getMessage() . " en línea " . $e->getLine());
        throw $e; // Re-lanzar la excepción para que la maneje el código que llamó a esta función
    }
}

// Procesar la solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Obtener parámetros
    $jsonData = file_get_contents('php://input');
    $requestData = json_decode($jsonData, true) ?: [];
    
    error_log("PARÁMETROS RECIBIDOS:");
    error_log("POST/PUT Body: " . $jsonData);
    error_log("GET/POST Variables: " . print_r($_REQUEST, true));
    
    // Si no hay datos JSON, usar los parámetros GET/POST
    $startDate = $requestData['start'] ?? $_REQUEST['start'] ?? null;
    $endDate = $requestData['end'] ?? $_REQUEST['end'] ?? null;
    $calendarType = $requestData['calendar_type'] ?? $_REQUEST['calendar_type'] ?? null;
    $slotDuration = isset($requestData['slot_duration']) ? intval($requestData['slot_duration']) : 
                   (isset($_REQUEST['slot_duration']) ? intval($_REQUEST['slot_duration']) : 3600); // 1 hora por defecto
    
    error_log("PARÁMETROS PROCESADOS: startDate=$startDate, endDate=$endDate, calendarType=$calendarType, slotDuration=$slotDuration");
    
    // Validar parámetros
    if (!$startDate || !$endDate) {
        error_log("ERROR: Faltan parámetros obligatorios start y/o end");
        apiResponse(false, 'Los parámetros start y end son obligatorios', null, 400);
    }
    
    try {
        // Log de los parámetros para depuración
        error_log("Consultando disponibilidad - Start: $startDate, End: $endDate, Type: $calendarType, Duration: $slotDuration");
        
        // Obtener slots disponibles
        $availableSlots = getAvailableSlots($startDate, $endDate, $calendarType, $slotDuration);
        
        // Responder con los slots disponibles
        apiResponse(true, 'Horarios disponibles obtenidos correctamente', $availableSlots);
    } catch (Exception $e) {
        // Registrar detalles del error
        error_log("Error en availability.php: " . $e->getMessage() . " en " . $e->getFile() . " línea " . $e->getLine());
        error_log("Trace: " . $e->getTraceAsString());
        
        // Crear respuesta detallada para debugging
        $errorData = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTrace(),
            'params' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'calendarType' => $calendarType,
                'slotDuration' => $slotDuration
            ]
        ];
        
        apiResponse(false, 'Error al obtener horarios disponibles: ' . $e->getMessage(), $errorData, 500);
    } catch (Error $e) {
        // Capturar errores de PHP (como errores fatales)
        error_log("Error fatal en availability.php: " . $e->getMessage() . " en " . $e->getFile() . " línea " . $e->getLine());
        error_log("Trace: " . $e->getTraceAsString());
        
        // Crear respuesta detallada para debugging
        $errorData = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTrace(),
            'params' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'calendarType' => $calendarType,
                'slotDuration' => $slotDuration
            ]
        ];
        
        apiResponse(false, 'Error interno del servidor: ' . $e->getMessage(), $errorData, 500);
    }
} else {
    apiResponse(false, 'Método no permitido', null, 405);
}

// La función apiResponse ya está definida en includes/api/jwt.php, no es necesario redeclararla aquí
?> 