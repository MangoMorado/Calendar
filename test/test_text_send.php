<?php
// Script de prueba para envío de mensaje de texto plano a Evolution API
session_start();
$_SESSION['user'] = ['id' => 1, 'name' => 'Test User', 'email' => 'test@test.com', 'role' => 'admin'];

require_once __DIR__ . '/../config/database.php';

// Configuración
$number = '573217058135@s.whatsapp.net';
$text = 'funciona!';

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
    echo "❌ Configuración de Evolution API incompleta<br>";
    exit;
}

// Mostrar configuración usada
echo "<b>Configuración usada:</b><br>";
echo "evolutionApiUrl: <code>" . htmlspecialchars($evolutionApiUrl) . "</code><br>";
echo "evolutionApiKey: <code>" . htmlspecialchars(substr($evolutionApiKey, 0, 8)) . str_repeat('*', max(0, strlen($evolutionApiKey)-8)) . "</code><br>";
echo "evolutionInstanceName: <code>" . htmlspecialchars($evolutionInstanceName) . "</code><br>";
echo "URL codificada: <code>" . htmlspecialchars($checkApiUrl) . "</code><br>";
echo "URL sin codificar: <code>" . htmlspecialchars(rtrim($evolutionApiUrl, '/') . '/instance/connectionState/' . $evolutionInstanceName) . "</code><br>";
if (strpos($evolutionInstanceName, ' ') !== false) {
    echo "<b style='color:orange'>Advertencia:</b> El nombre de la instancia contiene un espacio. Asegúrate de que Evolution API lo acepte exactamente así.<br>";
}

// 1. Validar estado de la instancia
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
    if (isset($checkData['instance']['state'])) {
        $instanceState = $checkData['instance']['state'];
    } else if (isset($checkData['state'])) {
        $instanceState = $checkData['state'];
    }
}
echo "<b>Estado de la instancia (campo leído):</b> $instanceState<br>";
if (strtolower($instanceState) !== 'open') {
    echo "❌ La instancia no está conectada (no está en estado 'open'). No se puede enviar el mensaje.<br>";
    // Mostrar respuesta cruda aunque no esté conectada
    echo "<b>Respuesta cruda de validación:</b> <pre>" . htmlspecialchars($checkResponse) . "</pre>";
    exit;
}

// Mostrar configuración usada
echo "<b>URL de validación de instancia:</b> <code>$checkApiUrl</code><br>";
echo "<b>Headers enviados:</b> <pre>" . htmlspecialchars(json_encode($checkHeaders, JSON_PRETTY_PRINT)) . "</pre>";
echo "<b>Respuesta cruda de validación:</b> <pre>" . htmlspecialchars($checkResponse) . "</pre>";
echo "<b>HTTP Code validación:</b> $checkHttpCode<br>";

// 2. Enviar mensaje de texto
$apiUrl = rtrim($evolutionApiUrl, '/') . '/message/sendText/' . rawurlencode($evolutionInstanceName);
$headers = [
    'Content-Type: application/json',
    'apikey: ' . $evolutionApiKey
];
$payload = [
    'number' => $number,
    'text' => $text,
    'textMessage' => [
        'text' => $text
    ]
];
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Mostrar configuración usada
echo "<b>URL de envío de mensaje:</b> <code>$apiUrl</code><br>";
echo "<b>Headers enviados:</b> <pre>" . htmlspecialchars(json_encode($headers, JSON_PRETTY_PRINT)) . "</pre>";
echo "<b>Payload enviado:</b> <pre>" . htmlspecialchars(json_encode($payload, JSON_PRETTY_PRINT)) . "</pre>";

// Mostrar resultado
if ($curlError) {
    echo "❌ Error de conexión: $curlError<br>";
    exit;
}
if ($httpCode === 200 || $httpCode === 201) {
    echo "✅ Mensaje enviado correctamente<br>";
    echo "<b>Respuesta Evolution:</b> <pre>$response</pre>";
} else {
    echo "❌ Error HTTP $httpCode<br>";
    echo "<b>Respuesta Evolution:</b> <pre>$response</pre>";
}
echo "<b>Respuesta cruda de envío:</b> <pre>" . htmlspecialchars($response) . "</pre>";
echo "<b>HTTP Code envío:</b> $httpCode<br>";
?> 