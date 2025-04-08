<?php
// Incluir archivos de configuración, funciones y autenticación
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/api/jwt.php';

// Configurar headers CORS y JSON
// En producción, reemplazar * por los dominios específicos permitidos
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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

// Método GET para obtener citas
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Obtener parámetros
        $startDate = $_GET['start'] ?? null;
        $endDate = $_GET['end'] ?? null;
        $calendarType = $_GET['calendar_type'] ?? null;
        
        // Obtener citas
        $appointments = getAppointments($startDate, $endDate, $calendarType);
        $events = [];
        
        foreach ($appointments as $appointment) {
            // Convertir el valor de all_day a booleano para JavaScript
            $isAllDay = isset($appointment['all_day']) && ($appointment['all_day'] == 1);
            
            // Determinar el color según el usuario (si existe) o usar el color por defecto
            $color = !empty($appointment['user_color']) ? $appointment['user_color'] : '#0d6efd';
            
            $events[] = [
                'id' => $appointment['id'],
                'title' => $appointment['title'],
                'start' => $appointment['start_time'],
                'end' => $appointment['end_time'],
                'description' => $appointment['description'],
                'backgroundColor' => $color,
                'borderColor' => $color,
                'allDay' => $isAllDay,
                'extendedProps' => [
                    'calendarType' => $appointment['calendar_type'],
                    'description' => $appointment['description'],
                    'user_id' => $appointment['user_id'],
                    'user' => $appointment['user'] ?? 'Sin asignar',
                    'user_color' => $color
                ]
            ];
        }
        
        // Limpiar cualquier buffer previo
        if (ob_get_length()) ob_clean();
        
        // Enviar respuesta
        apiResponse(true, 'Eventos obtenidos correctamente', $events);
    } catch (Exception $e) {
        apiResponse(false, 'Error al obtener eventos: ' . $e->getMessage(), null, 500);
    }
}

// Método POST para crear una cita
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verificar si hay cuerpo JSON
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);
        
        // Si no hay datos JSON, usar los datos de formulario tradicionales
        if (!$data) {
            $data = $_POST;
        }
        
        // Validar campos requeridos
        if (empty($data['title']) || empty($data['start_time']) || empty($data['end_time'])) {
            apiResponse(false, 'Faltan campos requeridos: título, hora de inicio y hora de fin', null, 400);
        }
        
        // Obtener datos
        $title = $data['title'];
        $description = $data['description'] ?? '';
        $startTime = $data['start_time'];
        $endTime = $data['end_time'];
        $calendarType = $data['calendar_type'] ?? 'general';
        
        // Manejo del user_id
        $userId = null;
        if (isset($data['user_id']) && $data['user_id'] !== '' && $data['user_id'] !== '0') {
            $userId = intval($data['user_id']);
            // Validar que sea un ID válido
            if ($userId <= 0) {
                $userId = null;
            }
        }
        
        // Para checkbox, verificar si existe
        $allDay = isset($data['all_day']) ? 
            ($data['all_day'] === 'on' || $data['all_day'] === '1' || 
             $data['all_day'] === 'true' || $data['all_day'] === true) : false;
        
        // Crear la cita
        $appointmentId = createAppointment($title, $description, $startTime, $endTime, $calendarType, $allDay, $userId);
        
        if ($appointmentId) {
            apiResponse(true, 'Cita creada correctamente', ['id' => $appointmentId]);
        } else {
            apiResponse(false, 'Error al crear la cita', null, 500);
        }
    } catch (Exception $e) {
        apiResponse(false, 'Error al procesar la solicitud: ' . $e->getMessage(), null, 500);
    }
}

// Método PUT para actualizar una cita
else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        // Obtener datos JSON
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);
        
        if (!$data) {
            apiResponse(false, 'Datos inválidos', null, 400);
        }
        
        // Verificar ID y campos requeridos
        if (!isset($data['id']) || empty($data['title']) || empty($data['start_time']) || empty($data['end_time'])) {
            apiResponse(false, 'Faltan campos requeridos: id, título, hora de inicio y hora de fin', null, 400);
        }
        
        // Obtener datos
        $id = intval($data['id']);
        $title = $data['title'];
        $description = $data['description'] ?? '';
        $startTime = $data['start_time'];
        $endTime = $data['end_time'];
        $calendarType = $data['calendar_type'] ?? null;
        
        // Manejo del user_id
        $userId = null;
        if (isset($data['user_id']) && $data['user_id'] !== '' && $data['user_id'] !== '0') {
            $userId = intval($data['user_id']);
            if ($userId <= 0) {
                $userId = null;
            }
        }
        
        // Para checkbox, verificar si existe
        $allDay = isset($data['all_day']) ? 
            ($data['all_day'] === 'on' || $data['all_day'] === '1' || 
             $data['all_day'] === 'true' || $data['all_day'] === true) : false;
        
        // Actualizar la cita
        $success = updateAppointment($id, $title, $description, $startTime, $endTime, $calendarType, $allDay, $userId);
        
        if ($success) {
            apiResponse(true, 'Cita actualizada correctamente');
        } else {
            apiResponse(false, 'Error al actualizar la cita', null, 500);
        }
    } catch (Exception $e) {
        apiResponse(false, 'Error al procesar la solicitud: ' . $e->getMessage(), null, 500);
    }
}

// Método DELETE para eliminar una cita
else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        // Verificar si hay ID en la URL
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;
        
        // Si no hay ID en la URL, intentar obtenerlo del cuerpo
        if (!$id) {
            $jsonData = file_get_contents('php://input');
            $data = json_decode($jsonData, true);
            $id = $data['id'] ?? null;
        }
        
        if (!$id) {
            apiResponse(false, 'ID de cita no proporcionado', null, 400);
        }
        
        // Obtener datos de la cita antes de eliminarla para el historial
        $appointmentToDelete = getAppointmentById($id);
        
        // Eliminar la cita
        $result = deleteAppointment($id);
        
        if ($result) {
            apiResponse(true, 'Cita eliminada correctamente');
        } else {
            apiResponse(false, 'Error al eliminar la cita', null, 500);
        }
    } catch (Exception $e) {
        apiResponse(false, 'Error al procesar la solicitud: ' . $e->getMessage(), null, 500);
    }
}

// Método no permitido
else {
    apiResponse(false, 'Método no permitido', null, 405);
}
?> 