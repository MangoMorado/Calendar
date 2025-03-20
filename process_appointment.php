<?php
// Incluir archivos de configuración y funciones
require_once 'includes/functions.php';

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
        
        // Validar fechas
        if (strtotime($endTime) <= strtotime($startTime)) {
            echo json_encode(['success' => false, 'message' => 'La hora de fin debe ser posterior a la hora de inicio']);
            exit;
        }
        
        // Crear la cita
        $result = createAppointment($title, $description, $startTime, $endTime);
        
        if ($result) {
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
        
        // Validar fechas
        if (strtotime($endTime) <= strtotime($startTime)) {
            echo json_encode(['success' => false, 'message' => 'La hora de fin debe ser posterior a la hora de inicio']);
            exit;
        }
        
        // Actualizar la cita
        $result = updateAppointment($id, $title, $description, $startTime, $endTime);
        
        if ($result) {
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
        
        // Eliminar la cita
        $result = deleteAppointment($id);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Cita eliminada con éxito']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar la cita']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
?> 