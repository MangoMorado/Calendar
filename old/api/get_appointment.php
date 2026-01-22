<?php
/**
 * API para obtener detalles de una cita específica
 */

// Bootstrap común y funciones
require_once '../includes/bootstrap.php';
require_once '../includes/functions.php';
// Autenticación por JWT únicamente
require_once '../includes/api/jwt.php';

// Configurar headers CORS y JSON (solo API)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificar autenticación usando JWT
try {
    $payload = requireJWTAuth();
    $currentUserId = $payload['user_id'] ?? null;

    if (! $currentUserId) {
        apiResponse(false, 'Usuario no identificado', null, 401);
    }
} catch (Exception $e) {
    apiResponse(false, 'Error de autenticación: '.$e->getMessage(), null, 401);
}

// Verificar el método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    apiResponse(false, 'Método no permitido', null, 405);
}

// Obtener el ID de la cita
$appointmentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($appointmentId <= 0) {
    apiResponse(false, 'ID de cita no válido', null, 400);
}

// Obtener la cita por su ID
$appointment = getAppointmentById($appointmentId);

if (! $appointment) {
    apiResponse(false, 'Cita no encontrada', null, 404);
}

// Formatear las fechas para el campo datetime-local si es necesario
$startTime = (new DateTime($appointment['start_time']))->format('Y-m-d\TH:i');
$endTime = (new DateTime($appointment['end_time']))->format('Y-m-d\TH:i');

// Devolver los datos de la cita
apiResponse(true, 'Cita obtenida correctamente', [
    'id' => $appointment['id'],
    'title' => $appointment['title'],
    'description' => $appointment['description'],
    'start_time' => $startTime,
    'end_time' => $endTime,
    'calendar_type' => $appointment['calendar_type'],
    'all_day' => (bool) ($appointment['all_day'] ?? false),
    'user_id' => $appointment['user_id'],
    'user' => $appointment['user'] ?? 'Sin asignar',
    'user_color' => $appointment['user_color'] ?? null,
]);
?> 