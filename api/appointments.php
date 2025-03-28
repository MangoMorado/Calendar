<?php
// Incluir archivos de configuración, funciones y autenticación
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Configurar headers CORS y JSON
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificar que el usuario esté autenticado
try {
    requireAuth();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado: ' . $e->getMessage()
    ]);
    exit;
}

// Manejar solicitudes GET para obtener eventos
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action']) && $_GET['action'] === 'get_events') {
        try {
            // Obtener las citas
            $startDate = isset($_GET['start']) ? $_GET['start'] : null;
            $endDate = isset($_GET['end']) ? $_GET['end'] : null;
            $calendarType = isset($_GET['calendar_type']) ? $_GET['calendar_type'] : null;
            
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
            
            // Codificar y enviar el JSON
            echo json_encode([
                'success' => true,
                'data' => $events
            ]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener eventos: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
    exit;
}

// Verificar el método de solicitud para POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener la acción solicitada
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Manejar las diferentes acciones
switch ($action) {
    case 'create':
        // Validar campos requeridos
        if (empty($_POST['title']) || empty($_POST['start_time']) || empty($_POST['end_time'])) {
            echo json_encode(['success' => false, 'message' => 'Faltan campos requeridos']);
            exit;
        }
        
        // Obtener datos del formulario
        $title = $_POST['title'];
        $description = isset($_POST['description']) ? $_POST['description'] : '';
        $startTime = $_POST['start_time'];
        $endTime = $_POST['end_time'];
        $calendarType = isset($_POST['calendar_type']) ? $_POST['calendar_type'] : 'estetico';
        
        // Manejo específico del user_id para asegurar que se guarde correctamente
        $userId = null;
        if (isset($_POST['user_id']) && $_POST['user_id'] !== '' && $_POST['user_id'] !== '0') {
            $userId = intval($_POST['user_id']);
            // Validar que sea un ID válido
            if ($userId <= 0) {
                $userId = null;
            }
        }
        
        // Para debug
        error_log("USER ID en create: " . var_export($userId, true));
        
        // Ya no impedimos el uso de 'general' como tipo de calendario
        // Comentado: if ($calendarType === 'general') {
        //     $calendarType = 'estetico';
        // }
        
        // Para checkbox, verificar si existe, ya que solo se envía cuando está marcado
        $allDay = isset($_POST['all_day']) ? ($_POST['all_day'] === 'on' || $_POST['all_day'] === '1' || $_POST['all_day'] === 'true') : false;
        
        // Crear la cita
        $appointmentId = createAppointment($title, $description, $startTime, $endTime, $calendarType, $allDay, $userId);
        
        if ($appointmentId) {
            echo json_encode([
                'success' => true, 
                'message' => 'Cita creada correctamente',
                'id' => $appointmentId
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Error al crear la cita'
            ]);
        }
        break;
        
    case 'update':
        // Validar campos requeridos
        if (empty($_POST['id']) || empty($_POST['title']) || empty($_POST['start_time']) || empty($_POST['end_time'])) {
            echo json_encode(['success' => false, 'message' => 'Faltan campos requeridos']);
            exit;
        }
        
        // Obtener datos del formulario
        $id = $_POST['id'];
        $title = $_POST['title'];
        $description = isset($_POST['description']) ? $_POST['description'] : '';
        $startTime = $_POST['start_time'];
        $endTime = $_POST['end_time'];
        $calendarType = isset($_POST['calendar_type']) ? $_POST['calendar_type'] : null;
        
        // Manejo específico del user_id para asegurar que se guarde correctamente
        $userId = null;
        if (isset($_POST['user_id']) && $_POST['user_id'] !== '' && $_POST['user_id'] !== '0') {
            $userId = intval($_POST['user_id']);
            // Validar que sea un ID válido
            if ($userId <= 0) {
                $userId = null;
            }
        }
        
        // Para debug
        error_log("USER ID en update: " . var_export($userId, true) . " para la cita ID: " . $id);
        
        // Ya no impedimos el uso de 'general' como tipo de calendario
        // Comentado: if ($calendarType === 'general') {
        //     $calendarType = 'estetico';
        // }
        
        // Para checkbox, verificar si existe, ya que solo se envía cuando está marcado
        $allDay = isset($_POST['all_day']) ? ($_POST['all_day'] === 'on' || $_POST['all_day'] === '1' || $_POST['all_day'] === 'true') : false;
        
        // Actualizar la cita
        $success = updateAppointment($id, $title, $description, $startTime, $endTime, $calendarType, $allDay, $userId);
        
        if ($success) {
            echo json_encode([
                'success' => true, 
                'message' => 'Cita actualizada correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Error al actualizar la cita'
            ]);
        }
        break;
        
    case 'delete':
        // Validar campos requeridos
        if (empty($_POST['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID de cita no proporcionado']);
            exit;
        }
        
        // Obtener ID de la cita
        $id = $_POST['id'];
        
        // Eliminar la cita
        $success = deleteAppointment($id);
        
        if ($success) {
            echo json_encode([
                'success' => true, 
                'message' => 'Cita eliminada correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Error al eliminar la cita'
            ]);
        }
        break;
        
    case 'update_date':
        // Validar campos requeridos
        if (empty($_POST['appointmentId']) || empty($_POST['start']) || empty($_POST['end'])) {
            echo json_encode(['success' => false, 'message' => 'Faltan campos requeridos']);
            exit;
        }
        
        // Obtener datos
        $id = $_POST['appointmentId'];
        $startTime = $_POST['start'];
        $endTime = $_POST['end'];
        
        // Actualizar solo las fechas de la cita
        $success = updateAppointmentDates($id, $startTime, $endTime);
        
        if ($success) {
            echo json_encode([
                'success' => true, 
                'message' => 'Fechas de cita actualizadas correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Error al actualizar las fechas de la cita'
            ]);
        }
        break;
        
    default:
        echo json_encode([
            'success' => false, 
            'message' => 'Acción no reconocida'
        ]);
        break;
}
?> 