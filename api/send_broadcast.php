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

// Verificar estado de la instancia antes de enviar
$checkApiUrl = rtrim($evolutionApiUrl, '/') . '/instance/connectionState/' . rawurlencode($evolutionInstanceName);
$checkHeaders = ['apikey: ' . $evolutionApiKey];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $checkApiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $checkHeaders);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$checkResponse = curl_exec($ch);
$checkHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$instanceState = 'unknown';
if ($checkHttpCode === 200) {
    $checkData = json_decode($checkResponse, true);
    $instanceState = $checkData['state'] ?? 'unknown';
}

// Log detallado para debugging
error_log("[EVOLUTION BROADCAST] Number: $number");
error_log("[EVOLUTION BROADCAST] Instance State: $instanceState");
error_log("[EVOLUTION BROADCAST] Instance Name: $evolutionInstanceName");
error_log("[EVOLUTION BROADCAST] Encoded Instance Name: " . rawurlencode($evolutionInstanceName));

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
    $apiUrl = rtrim($evolutionApiUrl, '/') . '/message/sendMedia';
    $headers = [
        'apikey: ' . $evolutionApiKey
    ];
    $caption = $mensaje;
    $postfields = [
        'instanceName' => $evolutionInstanceName,
        'to' => $number,
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
        $errorDetails = [];
        
        if ($response) {
            $errorData = json_decode($response, true);
            if (is_array($errorData)) {
                if (isset($errorData['message'])) {
                    $errorMessage = $errorData['message'];
                }
                $errorDetails = $errorData;
            }
        }
        
        // Agregar información adicional para debugging
        $errorDetails['instance_state'] = $instanceState;
        $errorDetails['number'] = $number;
        $errorDetails['http_code'] = $httpCode;
        
        echo json_encode([
            'success' => false, 
            'message' => $errorMessage,
            'debug_info' => $errorDetails
        ]);
    }
    exit;
}

// Si no hay imagen, enviar mensaje de texto
$apiUrl = rtrim($evolutionApiUrl, '/') . '/message/sendText/' . rawurlencode($evolutionInstanceName);
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
error_log("[EVOLUTION SEND TEXT] Payload: " . json_encode($payload));
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
    $errorDetails = [];
    
    if ($response) {
        $errorData = json_decode($response, true);
        if (is_array($errorData)) {
            if (isset($errorData['message'])) {
                $errorMessage = $errorData['message'];
            }
            $errorDetails = $errorData;
        }
    }
    
    // Agregar información adicional para debugging
    $errorDetails['instance_state'] = $instanceState;
    $errorDetails['number'] = $number;
    $errorDetails['http_code'] = $httpCode;
    $errorDetails['payload'] = $payload;
    
    // Mensajes específicos para errores comunes
    if ($httpCode === 400) {
        if ($instanceState !== 'open') {
            $errorMessage = 'La instancia no está conectada. Estado actual: ' . $instanceState;
        } else {
            $errorMessage = 'Error 400: Verifica que el número esté registrado en WhatsApp y que la instancia esté conectada';
        }
    } elseif ($httpCode === 401) {
        $errorMessage = 'Error 401: API Key inválida o expirada';
    } elseif ($httpCode === 404) {
        $errorMessage = 'Error 404: Instancia no encontrada o URL incorrecta';
    } elseif ($httpCode === 500) {
        $errorMessage = 'Error 500: Error interno del servidor Evolution API';
    }
    
    echo json_encode([
        'success' => false, 
        'message' => $errorMessage,
        'debug_info' => $errorDetails
    ]);
}
?> 