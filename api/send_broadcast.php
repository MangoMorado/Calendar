<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAuth();
header('Content-Type: application/json; charset=utf-8');

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos de la petición
$number = $_POST['number'] ?? '';
$mensaje = $_POST['mensaje'] ?? '';
$imagen = $_FILES['imagen'] ?? null;

// Validar datos requeridos
if (empty($number) || empty($mensaje)) {
    echo json_encode(['success' => false, 'message' => 'Número y mensaje son requeridos']);
    exit;
}

// Obtener configuración de Evolution API
$config = [];
$sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('evolution_api_url', 'evolution_api_key', 'evolution_instance_name')";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $config[$row['setting_key']] = $row['setting_value'];
}

$evolutionApiUrl = $config['evolution_api_url'] ?? '';
$evolutionApiKey = $config['evolution_api_key'] ?? '';
$evolutionInstanceName = $config['evolution_instance_name'] ?? '';

if (empty($evolutionApiUrl) || empty($evolutionApiKey) || empty($evolutionInstanceName)) {
    echo json_encode(['success' => false, 'message' => 'Configuración de Evolution API incompleta']);
    exit;
}

// Si hay imagen, procesar el envío de imagen
if ($imagen && $imagen['tmp_name']) {
    // Crear carpeta uploads si no existe
    $uploadsDir = __DIR__ . '/../uploads';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0777, true);
    }
    // Guardar la imagen temporalmente
    $ext = pathinfo($imagen['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_', true) . '.' . $ext;
    $filepath = $uploadsDir . '/' . $filename;
    if (!move_uploaded_file($imagen['tmp_name'], $filepath)) {
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar la imagen en el servidor']);
        exit;
    }
    // Enviar la imagen a Evolution API
    $apiUrl = rtrim($evolutionApiUrl, '/') . '/message/sendMedia/' . $evolutionInstanceName;
    $headers = [
        'apikey: ' . $evolutionApiKey
    ];
    $caption = $mensaje;
    $postfields = [
        'number' => $number,
        'file' => new CURLFile($filepath),
        'caption' => $caption,
        'mediatype' => 'image'
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    // Eliminar la imagen temporal
    if (file_exists($filepath)) {
        unlink($filepath);
    }
    // Log para debugging
    error_log("[EVOLUTION SEND MEDIA] Number: $number");
    error_log("[EVOLUTION SEND MEDIA] URL: $apiUrl");
    error_log("[EVOLUTION SEND MEDIA] HTTP Code: $httpCode");
    error_log("[EVOLUTION SEND MEDIA] Response: $response");
    if ($curlError) {
        error_log("[EVOLUTION SEND MEDIA] cURL Error: $curlError");
    }
    if ($curlError) {
        echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $curlError]);
        exit;
    }
    if ($httpCode === 200 || $httpCode === 201) {
        $responseData = json_decode($response, true);
        echo json_encode([
            'success' => true,
            'message' => 'Imagen enviada correctamente',
            'data' => $responseData ? $responseData : $response
        ]);
    } else {
        $errorMessage = 'Error HTTP ' . $httpCode;
        if ($response) {
            $errorData = json_decode($response, true);
            if (is_array($errorData) && isset($errorData['message'])) {
                $errorMessage = $errorData['message'];
            }
        }
        echo json_encode(['success' => false, 'message' => $errorMessage]);
    }
    exit;
}

// Si no hay imagen, enviar mensaje de texto
$apiUrl = rtrim($evolutionApiUrl, '/') . '/message/sendText/' . $evolutionInstanceName;
$headers = [
    'Content-Type: application/json',
    'apikey: ' . $evolutionApiKey
];

// Estructura del payload: enviar 'text' directamente
$payload = [
    'number' => $number,
    'text' => $mensaje
];

// Configurar cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

// Ejecutar la petición
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Log para debugging
error_log("[EVOLUTION SEND TEXT] Number: $number");
error_log("[EVOLUTION SEND TEXT] URL: $apiUrl");
error_log("[EVOLUTION SEND TEXT] HTTP Code: $httpCode");
error_log("[EVOLUTION SEND TEXT] Response: $response");
if ($curlError) {
    error_log("[EVOLUTION SEND TEXT] cURL Error: $curlError");
}

// Verificar si hubo error en cURL
if ($curlError) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $curlError]);
    exit;
}

// Verificar el código de respuesta HTTP
if ($httpCode === 200 || $httpCode === 201) {
    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'message' => 'Mensaje enviado correctamente',
        'data' => $responseData ? $responseData : $response
    ]);
} else {
    // Intentar obtener más información del error
    $errorMessage = 'Error HTTP ' . $httpCode;
    if ($response) {
        $errorData = json_decode($response, true);
        if (is_array($errorData) && isset($errorData['message'])) {
            $errorMessage = $errorData['message'];
        }
    }
    echo json_encode(['success' => false, 'message' => $errorMessage]);
}
?> 