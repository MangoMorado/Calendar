<?php
/**
 * Endpoint de verificación de estado de la API
 * Utilizado para monitoreo de disponibilidad y verificación básica del sistema
 */

require_once '../config/database.php';
require_once '../includes/api/jwt.php';

// Configurar headers CORS y JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // En producción, especificar los dominios permitidos
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificar conexión a la base de datos
$dbStatus = [
    'connected' => false,
    'error' => null,
];

try {
    if ($conn && mysqli_ping($conn)) {
        $dbStatus['connected'] = true;
    } else {
        $dbStatus['error'] = mysqli_connect_error() ?: 'Error de conexión desconocido';
    }
} catch (Exception $e) {
    $dbStatus['error'] = $e->getMessage();
}

// Respuesta del sistema
$response = [
    'success' => true,
    'service' => 'Mundo Animal API',
    'status' => 'operational',
    'response' => 'pong',
    'timestamp' => date('Y-m-d H:i:s'),
    'version' => '2.0.0',
    'database' => $dbStatus,
    'environment' => [
        'php_version' => PHP_VERSION,
        'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    ],
];

// Si la base de datos no está conectada, cambiar el estado del servicio
if (! $dbStatus['connected']) {
    $response['status'] = 'degraded';
}

// Enviar respuesta
echo json_encode($response);
?> 