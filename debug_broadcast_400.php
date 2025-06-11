<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

echo "<h2>🔍 Diagnóstico de Error HTTP 400 en Difusiones</h2>";

// Verificar autenticación
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isAuthenticated()) {
    echo "❌ Usuario NO autenticado<br>";
    echo "Debes estar logueado para diagnosticar las difusiones.<br>";
    exit;
}

$user = getCurrentUser();
echo "✅ Usuario autenticado: " . htmlspecialchars($user['name']) . "<br><br>";

// 1. Verificar configuración de Evolution API
echo "<h3>1. 🔧 Configuración de Evolution API:</h3>";
$config = [];
$sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('evolution_api_url', 'evolution_api_key', 'evolution_instance_name')";
$result = mysqli_query($conn, $sql);

while ($row = mysqli_fetch_assoc($result)) {
    $config[$row['setting_key']] = $row['setting_value'];
}

$evolutionApiUrl = $config['evolution_api_url'] ?? '';
$evolutionApiKey = $config['evolution_api_key'] ?? '';
$evolutionInstanceName = $config['evolution_instance_name'] ?? '';

echo "evolution_api_url: " . ($evolutionApiUrl ? "✅ Configurado: " . htmlspecialchars($evolutionApiUrl) : "❌ NO configurado") . "<br>";
echo "evolution_api_key: " . ($evolutionApiKey ? "✅ Configurado: " . str_repeat('*', min(strlen($evolutionApiKey), 8)) : "❌ NO configurado") . "<br>";
echo "evolution_instance_name: " . ($evolutionInstanceName ? "✅ Configurado: " . htmlspecialchars($evolutionInstanceName) : "❌ NO configurado") . "<br>";

if (empty($evolutionApiUrl) || empty($evolutionApiKey) || empty($evolutionInstanceName)) {
    echo "<br><strong>❌ PROBLEMA IDENTIFICADO: Configuración de Evolution API incompleta</strong><br>";
    echo "Este es el motivo del error 400. Necesitas configurar estos valores.<br>";
    exit;
}

// 2. Verificar estado de la instancia
echo "<h3>2. 📱 Estado de la instancia:</h3>";
$apiUrl = rtrim($evolutionApiUrl, '/') . '/instance/connectionState/' . rawurlencode($evolutionInstanceName);
$headers = [
    'apikey: ' . $evolutionApiKey
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "URL de verificación: " . htmlspecialchars($apiUrl) . "<br>";
echo "Código de respuesta HTTP: $httpCode<br>";

if ($curlError) {
    echo "❌ Error de cURL: " . htmlspecialchars($curlError) . "<br>";
} else {
    echo "✅ Conexión exitosa<br>";
    echo "Respuesta: " . htmlspecialchars(substr($response, 0, 500)) . "...<br>";
    
    $data = json_decode($response, true);
    if (is_array($data)) {
        $state = $data['state'] ?? 'unknown';
        echo "Estado de la instancia: <strong>$state</strong><br>";
        
        if ($state !== 'open') {
            echo "❌ <strong>PROBLEMA IDENTIFICADO: La instancia no está conectada</strong><br>";
            echo "El estado debe ser 'open' para poder enviar mensajes.<br>";
        } else {
            echo "✅ La instancia está conectada correctamente<br>";
        }
    }
}

// 3. Probar envío de mensaje de prueba
echo "<h3>3. 🧪 Prueba de envío de mensaje:</h3>";
$testNumber = '573217058135@s.whatsapp.net'; // Número del error
$testMessage = 'Mensaje de prueba - ' . date('Y-m-d H:i:s');

$apiUrl = rtrim($evolutionApiUrl, '/') . '/message/sendText/' . rawurlencode($evolutionInstanceName);
$headers = [
    'Content-Type: application/json',
    'apikey: ' . $evolutionApiKey
];

$payload = [
    'number' => $testNumber,
    'text' => $testMessage
];

echo "Número de prueba: $testNumber<br>";
echo "Mensaje de prueba: $testMessage<br>";
echo "URL de envío: " . htmlspecialchars($apiUrl) . "<br>";
echo "Payload: " . htmlspecialchars(json_encode($payload)) . "<br><br>";

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

echo "Código de respuesta HTTP: $httpCode<br>";

if ($curlError) {
    echo "❌ Error de cURL: " . htmlspecialchars($curlError) . "<br>";
} else {
    echo "✅ Petición enviada correctamente<br>";
    echo "Respuesta completa: " . htmlspecialchars($response) . "<br>";
    
    if ($httpCode === 200 || $httpCode === 201) {
        echo "✅ <strong>¡ENVÍO EXITOSO!</strong><br>";
        $data = json_decode($response, true);
        if (is_array($data)) {
            echo "Datos de respuesta: " . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "<br>";
        }
    } else {
        echo "❌ <strong>ERROR HTTP $httpCode</strong><br>";
        $data = json_decode($response, true);
        if (is_array($data)) {
            echo "Error detallado: " . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "<br>";
        }
    }
}

// 4. Verificar contactos en la base de datos
echo "<h3>4. 📋 Verificación de contactos:</h3>";
$sql = "SELECT COUNT(*) as total FROM contacts";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
echo "Total de contactos en la base de datos: " . $row['total'] . "<br>";

$sql = "SELECT COUNT(*) as total FROM contacts WHERE send = 1";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
echo "Contactos marcados para envío: " . $row['total'] . "<br>";

// Verificar el contacto específico del error
$sql = "SELECT * FROM contacts WHERE number = '573217058135@s.whatsapp.net'";
$result = mysqli_query($conn, $sql);
$contact = mysqli_fetch_assoc($result);

if ($contact) {
    echo "✅ Contacto encontrado en la base de datos:<br>";
    echo "- Número: " . htmlspecialchars($contact['number']) . "<br>";
    echo "- Nombre: " . htmlspecialchars($contact['pushName']) . "<br>";
    echo "- Envío habilitado: " . ($contact['send'] ? 'Sí' : 'No') . "<br>";
} else {
    echo "❌ El contacto 573217058135@s.whatsapp.net NO está en la base de datos<br>";
}

// 5. Análisis del problema y soluciones
echo "<h3>5. 🔍 Análisis del problema:</h3>";
echo "<strong>Posibles causas del error HTTP 400:</strong><br>";
echo "1. ❌ Instancia no conectada (estado != 'open')<br>";
echo "2. ❌ Número de teléfono inválido o no registrado en WhatsApp<br>";
echo "3. ❌ API Key incorrecta o expirada<br>";
echo "4. ❌ URL del servidor Evolution API incorrecta<br>";
echo "5. ❌ Formato de payload incorrecto<br>";
echo "6. ❌ Problemas de conectividad con el servidor<br>";

echo "<h3>6. 💡 Soluciones recomendadas:</h3>";
echo "1. <strong>Verificar conexión de instancia:</strong> Asegúrate de que la instancia esté conectada en Evolution API<br>";
echo "2. <strong>Verificar número:</strong> Confirma que el número esté registrado en WhatsApp<br>";
echo "3. <strong>Revisar configuración:</strong> Verifica URL, API Key y nombre de instancia<br>";
echo "4. <strong>Revisar logs:</strong> Consulta los logs del servidor Evolution API<br>";
echo "5. <strong>Probar con Postman:</strong> Prueba la API directamente desde Postman<br>";

echo "<h3>7. 🛠️ Scripts de prueba disponibles:</h3>";
echo "<ul>";
echo "<li><a href='test_send.php'>test_send.php</a> - Prueba básica de envío</li>";
echo "<li><a href='debug_import_contacts_v2.php'>debug_import_contacts_v2.php</a> - Diagnóstico de Evolution API</li>";
echo "<li><a href='change_evolution_instance.php'>change_evolution_instance.php</a> - Cambiar instancia</li>";
echo "</ul>";

echo "<h3>8. 📊 Información de debugging:</h3>";
echo "Para obtener más información, revisa los logs de PHP:<br>";
echo "- Archivo: php_error.log<br>";
echo "- Busca entradas con '[EVOLUTION SEND TEXT]' o '[EVOLUTION SEND MEDIA]'<br>";
echo "- Verifica los logs del servidor Evolution API<br>";
?> 