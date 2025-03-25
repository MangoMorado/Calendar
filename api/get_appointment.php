<?php
// Incluir archivos de configuración, funciones y autenticación
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Verificar que el usuario esté autenticado
requireAuth();

// Establecer headers para JSON
header('Content-Type: application/json');

// Verificar el método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener el ID de la cita
$appointmentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($appointmentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de cita no válido']);
    exit;
}

// Obtener la cita por su ID
$appointment = getAppointmentById($appointmentId);

if (!$appointment) {
    echo json_encode(['success' => false, 'message' => 'Cita no encontrada']);
    exit;
}

// Formatear las fechas para el campo datetime-local
$startTime = (new DateTime($appointment['start_time']))->format('Y-m-d\TH:i');
$endTime = (new DateTime($appointment['end_time']))->format('Y-m-d\TH:i');

// Devolver los datos de la cita
echo json_encode([
    'success' => true,
    'id' => $appointment['id'],
    'title' => $appointment['title'],
    'description' => $appointment['description'],
    'start_time' => $startTime,
    'end_time' => $endTime,
    'calendar_type' => $appointment['calendar_type']
]);
?> 