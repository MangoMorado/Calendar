<?php
// Script de prueba para envío de imagen a Evolution API
session_start();
$_SESSION['user'] = ['id' => 1, 'name' => 'Test User', 'email' => 'test@test.com', 'role' => 'admin'];

require_once __DIR__ . '/../config/database.php';

// Configuración
$number = '573217058135@s.whatsapp.net';
$caption = 'Mensaje de prueba con imagen';
$imageUrl = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQeP835chGu_jniJSyXHpvjUR1R2y3tFH461g&s';

// Descargar la imagen
$uploadsDir = __DIR__ . '/uploads';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
}
$ext = 'jpg';
$filename = uniqid('img_', true) . '.' . $ext;
$filepath = $uploadsDir . '/' . $filename;

$imageData = file_get_contents($imageUrl);
if ($imageData === false) {
    echo "No se pudo descargar la imagen\n";
    exit;
}
file_put_contents($filepath, $imageData);

// Obtener configuración de Evolution API
global $conn;
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
    echo "Configuración de Evolution API incompleta\n";
    exit;
}

// Verificar estado de la instancia
echo "Verificando estado de la instancia...\n";
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

echo "Estado de instancia - HTTP Code: $checkHttpCode\n";
echo "Respuesta: $checkResponse\n";

$instanceState = 'unknown';
if ($checkHttpCode === 200) {
    $checkData = json_decode($checkResponse, true);
    if (isset($checkData['instance']['state'])) {
        $instanceState = $checkData['instance']['state'];
    } else if (isset($checkData['state'])) {
        $instanceState = $checkData['state'];
    }
}

echo "Estado de la instancia: $instanceState\n";

if (strtolower($instanceState) !== 'open') {
    echo "❌ La instancia no está conectada. Estado: $instanceState\n";
    exit;
}

echo "✅ Instancia conectada y lista\n";

// Enviar la imagen a Evolution API
$apiUrl = rtrim($evolutionApiUrl, '/') . '/message/sendMedia/' . rawurlencode($evolutionInstanceName);
$headers = [
    'apikey: ' . $evolutionApiKey
];
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

// Mostrar resultado
if ($curlError) {
    echo "Error de conexión: $curlError\n";
    exit;
}

if ($httpCode === 200 || $httpCode === 201) {
    echo "✅ Imagen enviada correctamente\n";
    echo "Respuesta Evolution: $response\n";
} else {
    echo "❌ Error HTTP $httpCode\n";
    echo "Respuesta Evolution: $response\n";
}
?> 