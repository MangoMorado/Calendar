<?php
// Incluir archivos de configuración y funciones
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Verificar que el usuario esté autenticado
requireAuth();

// Verificar si se proporcionó un ID de cita
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de cita no proporcionado']);
    exit;
}

$id = intval($_GET['id']);

// Obtener los detalles de la cita
$appointment = getAppointmentById($id);

if (!$appointment) {
    echo json_encode(['success' => false, 'message' => 'Cita no encontrada']);
    exit;
}

// Dar formato a las fechas para el formulario
$appointment['start_time'] = str_replace(' ', 'T', $appointment['start_time']);
$appointment['end_time'] = str_replace(' ', 'T', $appointment['end_time']);

// Asegurar que el tipo de calendario está incluido, con un valor predeterminado si no lo tiene
if (!isset($appointment['calendar_type'])) {
    $appointment['calendar_type'] = 'general';
}

// Devolver los datos como JSON
echo json_encode($appointment);
?> 