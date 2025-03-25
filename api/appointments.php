<?php
// Incluir archivos de configuración, funciones y autenticación
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Verificar que el usuario esté autenticado
requireAuth();

// Establecer headers para JSON
header('Content-Type: application/json');

// Manejar solicitudes GET para obtener eventos
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action']) && $_GET['action'] === 'get_events') {
        // Obtener las citas
        $startDate = isset($_GET['start']) ? $_GET['start'] : null;
        $endDate = isset($_GET['end']) ? $_GET['end'] : null;
        $calendarType = isset($_GET['calendar_type']) ? $_GET['calendar_type'] : null;
        
        $appointments = getAppointments($startDate, $endDate, $calendarType);
        $events = [];
        
        foreach ($appointments as $appointment) {
            // Convertir el valor de all_day a booleano para JavaScript
            $isAllDay = isset($appointment['all_day']) && ($appointment['all_day'] == 1);
            
            $events[] = [
                'id' => $appointment['id'],
                'title' => $appointment['title'],
                'start' => $appointment['start_time'],
                'end' => $appointment['end_time'],
                'description' => $appointment['description'],
                'calendar_type' => $appointment['calendar_type'],
                'allDay' => $isAllDay,
                'extendedProps' => [
                    'calendar_type' => $appointment['calendar_type'],
                    'description' => $appointment['description']
                ]
            ];
        }
        
        // Establecer headers para JSON y asegurar que no hay output previo
        header('Content-Type: application/json');
        
        // Limpiar cualquier buffer previo
        ob_clean();
        
        // Codificar y enviar el JSON
        echo json_encode($events);
        exit;
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
        
        // Asegurarse de que no se use 'general' como tipo de calendario
        if ($calendarType === 'general') {
            $calendarType = 'estetico';
        }
        
        // Para checkbox, verificar si existe, ya que solo se envía cuando está marcado
        $allDay = isset($_POST['all_day']) ? ($_POST['all_day'] === 'on' || $_POST['all_day'] === '1' || $_POST['all_day'] === 'true') : false;
        
        // Crear la cita
        $appointmentId = createAppointment($title, $description, $startTime, $endTime, $calendarType, $allDay);
        
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
        
        // Asegurarse de que no se use 'general' como tipo de calendario
        if ($calendarType === 'general') {
            $calendarType = 'estetico';
        }
        
        // Para checkbox, verificar si existe, ya que solo se envía cuando está marcado
        $allDay = isset($_POST['all_day']) ? ($_POST['all_day'] === 'on' || $_POST['all_day'] === '1' || $_POST['all_day'] === 'true') : false;
        
        // Actualizar la cita
        $success = updateAppointment($id, $title, $description, $startTime, $endTime, $calendarType, $allDay);
        
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