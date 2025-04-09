<?php
/**
 * API Endpoint para autenticación mediante tokens JWT
 */

// Incluir archivos necesarios
require_once '../config/database.php';
require_once '../includes/api/jwt.php';

// Configurar headers CORS y JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // En producción, especificar los dominios permitidos
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo permitir peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse(false, 'Método no permitido', null, 405);
}

// Obtener datos del cuerpo de la petición
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Verificar si hay sesión PHP activa primero
session_start();
if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
    // Usar datos de la sesión en lugar de los enviados en el cuerpo
    $user = $_SESSION['user'];
    
    // Generar payload para el token
    $payload = [
        'user_id' => $user['id'],
        'email' => $user['email'],
        'name' => $user['name'],
        'role' => $user['role']
    ];
    
    // Generar token con tiempo de expiración de 24 horas
    $token = generateJWT($payload, 86400); // 24 horas
    
    // Datos para la respuesta
    $responseData = [
        'token' => $token,
        'expires_in' => 86400,
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ];
    
    apiResponse(true, 'Autenticación exitosa mediante sesión', $responseData);
}

// Si no hay sesión, verificar si los datos son válidos para autenticación
if (!$data || !isset($data['email']) || !isset($data['password'])) {
    apiResponse(false, 'Datos incompletos o inválidos', null, 400);
}

$email = $data['email'];
$password = $data['password'];

// Validar credenciales
$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    // Generar payload para el token
    $payload = [
        'user_id' => $user['id'],
        'email' => $user['email'],
        'name' => $user['name'],
        'role' => $user['role']
    ];
    
    // Generar token con tiempo de expiración de 24 horas
    $token = generateJWT($payload, 86400); // 24 horas
    
    // Datos para la respuesta
    $responseData = [
        'token' => $token,
        'expires_in' => 86400,
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ];
    
    apiResponse(true, 'Autenticación exitosa', $responseData);
} else {
    apiResponse(false, 'Credenciales inválidas', null, 401);
}
?> 