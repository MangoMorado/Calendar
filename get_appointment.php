<?php
// Incluir archivos de configuración y funciones
require_once 'includes/functions.php';

// Verificar que se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de cita no proporcionado']);
    exit;
}

$id = intval($_GET['id']);

// Obtener los detalles de la cita
$appointment = getAppointmentById($id);

if ($appointment) {
    // Formatear fechas para que sean compatibles con el formato datetime-local del formulario HTML5
    $appointment['start_time'] = str_replace(' ', 'T', $appointment['start_time']);
    $appointment['end_time'] = str_replace(' ', 'T', $appointment['end_time']);
    
    echo json_encode($appointment);
} else {
    echo json_encode(['success' => false, 'message' => 'Cita no encontrada']);
}
?> 