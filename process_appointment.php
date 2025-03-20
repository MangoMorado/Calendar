<?php
// Incluir archivos de configuración y funciones
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Verificar si el usuario está autenticado
requireAuth();

// Obtener información del usuario actual
$currentUser = getCurrentUser();
$userId = $currentUser['id'];

// Verificar si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar la acción solicitada
if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Acción no especificada']);
    exit;
}

$action = $_POST['action'];

// Realizar la acción correspondiente
switch ($action) {
    case 'create':
        // Verificar campos requeridos
        if (empty($_POST['title']) || empty($_POST['start_time']) || empty($_POST['end_time'])) {
            echo json_encode(['success' => false, 'message' => 'Faltan campos requeridos']);
            exit;
        }
        
        // Obtener datos del formulario
        $title = $_POST['title'];
        $description = isset($_POST['description']) ? $_POST['description'] : '';
        $startTime = str_replace('T', ' ', $_POST['start_time']);
        $endTime = str_replace('T', ' ', $_POST['end_time']);
        $calendarType = isset($_POST['calendar_type']) ? $_POST['calendar_type'] : 'general';
        
        // Validar el tipo de calendario
        if (!in_array($calendarType, ['estetico', 'veterinario', 'general'])) {
            $calendarType = 'general';
        }
        
        // Validar fechas
        if (strtotime($endTime) <= strtotime($startTime)) {
            echo json_encode(['success' => false, 'message' => 'La hora de fin debe ser posterior a la hora de inicio']);
            exit;
        }
        
        // Crear la cita
        $result = createAppointment($title, $description, $startTime, $endTime, $calendarType);
        
        if ($result) {
            // Determinar el nombre del calendario para el historial
            $calendarName = '';
            switch ($calendarType) {
                case 'estetico':
                    $calendarName = 'Estético';
                    break;
                case 'veterinario':
                    $calendarName = 'Veterinario';
                    break;
                default:
                    $calendarName = 'General';
            }
            
            // Registrar la acción en el historial del usuario con detalles adicionales
            updateUserHistory($userId, "Creó una cita: '$title' en el calendario $calendarName", [
                'id' => $result,
                'date' => $startTime,
                'calendar' => $calendarType,
                'extra' => "Duración: " . round((strtotime($endTime) - strtotime($startTime)) / 60) . " minutos"
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Cita creada con éxito', 'id' => $result]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear la cita']);
        }
        break;
        
    case 'update':
        // Verificar ID y campos requeridos
        if (!isset($_POST['id']) || empty($_POST['title']) || empty($_POST['start_time']) || empty($_POST['end_time'])) {
            echo json_encode(['success' => false, 'message' => 'Faltan campos requeridos']);
            exit;
        }
        
        // Obtener datos del formulario
        $id = intval($_POST['id']);
        $title = $_POST['title'];
        $description = isset($_POST['description']) ? $_POST['description'] : '';
        $startTime = str_replace('T', ' ', $_POST['start_time']);
        $endTime = str_replace('T', ' ', $_POST['end_time']);
        $calendarType = isset($_POST['calendar_type']) ? $_POST['calendar_type'] : null;
        
        // Validar el tipo de calendario si está establecido
        if ($calendarType !== null && !in_array($calendarType, ['estetico', 'veterinario', 'general'])) {
            $calendarType = 'general';
        }
        
        // Obtener datos de la cita original para el historial
        $originalAppointment = getAppointmentById($id);
        
        // Validar fechas
        if (strtotime($endTime) <= strtotime($startTime)) {
            echo json_encode(['success' => false, 'message' => 'La hora de fin debe ser posterior a la hora de inicio']);
            exit;
        }
        
        // Actualizar la cita
        $result = updateAppointment($id, $title, $description, $startTime, $endTime, $calendarType);
        
        if ($result) {
            // Determinar si el tipo de calendario cambió para el historial
            $calendarChanged = $calendarType !== null && isset($originalAppointment['calendar_type']) && $calendarType !== $originalAppointment['calendar_type'];
            $calendarInfo = '';
            
            if ($calendarChanged) {
                // Obtener el nombre de los calendarios para el historial
                $oldCalendarName = '';
                switch ($originalAppointment['calendar_type']) {
                    case 'estetico':
                        $oldCalendarName = 'Estético';
                        break;
                    case 'veterinario':
                        $oldCalendarName = 'Veterinario';
                        break;
                    default:
                        $oldCalendarName = 'General';
                }
                
                $newCalendarName = '';
                switch ($calendarType) {
                    case 'estetico':
                        $newCalendarName = 'Estético';
                        break;
                    case 'veterinario':
                        $newCalendarName = 'Veterinario';
                        break;
                    default:
                        $newCalendarName = 'General';
                }
                
                $calendarInfo = " (Cambió de calendario $oldCalendarName a $newCalendarName)";
            }
            
            // Registrar la acción en el historial del usuario con detalles adicionales
            updateUserHistory($userId, "Actualizó una cita: '$title'$calendarInfo", [
                'id' => $id,
                'date' => $startTime,
                'calendar' => $calendarType,
                'extra' => isset($originalAppointment) ? "Original: '{$originalAppointment['title']}'" : ""
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Cita actualizada con éxito']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar la cita']);
        }
        break;
        
    case 'delete':
        // Verificar ID
        if (!isset($_POST['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID de cita no proporcionado']);
            exit;
        }
        
        $id = intval($_POST['id']);
        
        // Obtener datos de la cita antes de eliminarla para el historial
        $appointmentToDelete = getAppointmentById($id);
        
        // Eliminar la cita
        $result = deleteAppointment($id);
        
        if ($result) {
            // Registrar la acción en el historial del usuario con detalles adicionales
            if ($appointmentToDelete) {
                // Determinar el nombre del calendario para el historial
                $calendarName = '';
                switch ($appointmentToDelete['calendar_type']) {
                    case 'estetico':
                        $calendarName = 'Estético';
                        break;
                    case 'veterinario':
                        $calendarName = 'Veterinario';
                        break;
                    default:
                        $calendarName = 'General';
                }
                
                updateUserHistory($userId, "Eliminó una cita: '{$appointmentToDelete['title']}' del calendario $calendarName", [
                    'id' => $id,
                    'date' => $appointmentToDelete['start_time'],
                    'calendar' => $appointmentToDelete['calendar_type']
                ]);
            } else {
                updateUserHistory($userId, "Eliminó una cita (ID: $id)");
            }
            
            echo json_encode(['success' => true, 'message' => 'Cita eliminada con éxito']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar la cita']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
?> 