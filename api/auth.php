<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Verificar si se recibieron las credenciales por Basic Auth
if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
    header('WWW-Authenticate: Basic realm="Mundo Animal API"');
    header('HTTP/1.0 401 Unauthorized');
    echo json_encode([
        'success' => false,
        'message' => 'Se requiere autenticación'
    ]);
    exit;
}

$email = $_SERVER['PHP_AUTH_USER'];
$password = $_SERVER['PHP_AUTH_PW'];

// Validar credenciales
$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    // Iniciar sesión
    session_start();
    authenticateUser($user);
    
    echo json_encode([
        'success' => true,
        'message' => 'Autenticación exitosa',
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ],
        'session_id' => session_id()
    ]);
} else {
    header('HTTP/1.0 401 Unauthorized');
    echo json_encode([
        'success' => false,
        'message' => 'Credenciales inválidas'
    ]);
} 