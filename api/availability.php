<?php
// Incluir archivos de configuración, funciones y autenticación
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/api/jwt.php';

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
    $start1 = strtotime($start1);
    $end1 = strtotime($end1);
    $start2 = strtotime($start2);
    $end2 = strtotime($end2);

    return $start1 < $end2 && $start2 < $end1;
}

// Función para contar cuántas citas hay en un horario específico
function countAppointmentsInSlot($startSlot, $endSlot, $appointments) {
    $count = 0;
    foreach ($appointments as $appointment) {
        if (isOverlapping($startSlot, $endSlot, $appointment['start_time'], $appointment['end_time'])) {
            $count++;
        }
    }
    return $count;
}

// Función para obtener los horarios disponibles
function getAvailableSlots($startDate, $endDate, $calendarType = null, $slotDuration = 3600) {
    // Obtener citas existentes en el rango de fechas
    $appointments = getAppointments($startDate, $endDate, $calendarType);
    
    // Definir horario de atención (8:00 AM - 6:00 PM)
    $openingTime = 8; // 8 AM
    $closingTime = 18; // 6 PM
    
    // Horarios disponibles
    $availableSlots = [];
    
    // Convertir a objetos DateTime
    $currentDate = new DateTime($startDate);
    $endDateTime = new DateTime($endDate);
    
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
            
            // Dividir el día en slots según la duración especificada
            $slotStart = clone $dayStart;
            $slotDurationSecs = $slotDuration; // Por defecto 1 hora (3600 segundos)
            
            while ($slotStart < $dayEnd) {
                $slotEnd = clone $slotStart;
                $slotEnd->modify('+' . $slotDurationSecs . ' seconds');
                
                if ($slotEnd > $dayEnd) {
                    break;
                }
                
                // Verificar cuántas citas ya existen en este slot
                $existingAppointments = countAppointmentsInSlot(
                    $slotStart->format('Y-m-d H:i:s'),
                    $slotEnd->format('Y-m-d H:i:s'),
                    $appointments
                );
                
                // Si hay menos de 2 citas simultáneas, el slot está disponible
                if ($existingAppointments < 2) {
                    $availableSlots[] = [
                        'start' => $slotStart->format('Y-m-d H:i:s'),
                        'end' => $slotEnd->format('Y-m-d H:i:s'),
                        'available_spots' => 2 - $existingAppointments // Cuántas citas más se pueden agendar
                    ];
                }
                
                // Avanzar al siguiente slot
                $slotStart = clone $slotEnd;
            }
        }
        
        // Avanzar al día siguiente
        $currentDate->modify('+1 day');
    }
    
    return $availableSlots;
}

// Procesar la solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Obtener parámetros
    $jsonData = file_get_contents('php://input');
    $requestData = json_decode($jsonData, true) ?: [];
    
    // Si no hay datos JSON, usar los parámetros GET/POST
    $startDate = $requestData['start'] ?? $_REQUEST['start'] ?? null;
    $endDate = $requestData['end'] ?? $_REQUEST['end'] ?? null;
    $calendarType = $requestData['calendar_type'] ?? $_REQUEST['calendar_type'] ?? null;
    $slotDuration = isset($requestData['slot_duration']) ? intval($requestData['slot_duration']) : 
                   (isset($_REQUEST['slot_duration']) ? intval($_REQUEST['slot_duration']) : 3600); // 1 hora por defecto
    
    // Validar parámetros
    if (!$startDate || !$endDate) {
        apiResponse(false, 'Los parámetros start y end son obligatorios', null, 400);
    }
    
    try {
        // Obtener slots disponibles
        $availableSlots = getAvailableSlots($startDate, $endDate, $calendarType, $slotDuration);
        
        // Responder con los slots disponibles
        apiResponse(true, 'Horarios disponibles obtenidos correctamente', $availableSlots);
    } catch (Exception $e) {
        apiResponse(false, 'Error al obtener horarios disponibles: ' . $e->getMessage(), null, 500);
    }
} else {
    apiResponse(false, 'Método no permitido', null, 405);
}

// Función para responder en formato JSON
function apiResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}
?> 