<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

echo "<h2>🔍 Diagnóstico de Codificación de URL - Espacios en Instancia</h2>";

// Verificar autenticación
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isAuthenticated()) {
    echo "❌ Usuario NO autenticado<br>";
    echo "Debes estar logueado para diagnosticar la codificación de URL.<br>";
    exit;
}

$user = getCurrentUser();
echo "✅ Usuario autenticado: " . htmlspecialchars($user['name']) . "<br><br>";

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

echo "<h3>1. 🔧 Configuración Actual:</h3>";
echo "evolution_api_url: " . htmlspecialchars($evolutionApiUrl) . "<br>";
echo "evolution_api_key: " . str_repeat('*', min(strlen($evolutionApiKey), 8)) . "<br>";
echo "evolution_instance_name: <strong>" . htmlspecialchars($evolutionInstanceName) . "</strong><br>";

// Verificar si hay espacios en el nombre de la instancia
$hasSpaces = strpos($evolutionInstanceName, ' ') !== false;
echo "¿Contiene espacios?: " . ($hasSpaces ? "❌ SÍ" : "✅ NO") . "<br>";

if ($hasSpaces) {
    echo "<br><strong>🚨 PROBLEMA IDENTIFICADO: El nombre de la instancia contiene espacios</strong><br>";
    echo "Los espacios en las URLs pueden causar errores HTTP 400.<br><br>";
}

// Mostrar diferentes formas de codificar la URL
echo "<h3>2. 🔗 URLs Generadas (Comparación):</h3>";

// URL sin codificar
$urlSinCodificar = rtrim($evolutionApiUrl, '/') . '/message/sendText/' . $evolutionInstanceName;
echo "<strong>URL sin codificar:</strong><br>";
echo "<code>" . htmlspecialchars($urlSinCodificar) . "</code><br><br>";

// URL con urlencode()
$urlConUrlencode = rtrim($evolutionApiUrl, '/') . '/message/sendText/' . urlencode($evolutionInstanceName);
echo "<strong>URL con urlencode():</strong><br>";
echo "<code>" . htmlspecialchars($urlConUrlencode) . "</code><br><br>";

// URL con rawurlencode()
$urlConRawurlencode = rtrim($evolutionApiUrl, '/') . '/message/sendText/' . rawurlencode($evolutionInstanceName);
echo "<strong>URL con rawurlencode():</strong><br>";
echo "<code>" . htmlspecialchars($urlConRawurlencode) . "</code><br><br>";

// URL con str_replace para espacios
$urlConReplace = rtrim($evolutionApiUrl, '/') . '/message/sendText/' . str_replace(' ', '%20', $evolutionInstanceName);
echo "<strong>URL con str_replace (espacios por %20):</strong><br>";
echo "<code>" . htmlspecialchars($urlConReplace) . "</code><br><br>";

// Probar las diferentes URLs
echo "<h3>3. 🧪 Pruebas de Conexión:</h3>";

$testNumber = '573217058135@s.whatsapp.net';
$testMessage = 'Prueba de codificación - ' . date('Y-m-d H:i:s');
$headers = [
    'Content-Type: application/json',
    'apikey: ' . $evolutionApiKey
];

$payload = [
    'number' => $testNumber,
    'text' => $testMessage
];

$urlsToTest = [
    'Sin codificar' => $urlSinCodificar,
    'Con urlencode()' => $urlConUrlencode,
    'Con rawurlencode()' => $urlConRawurlencode,
    'Con str_replace' => $urlConReplace
];

foreach ($urlsToTest as $method => $url) {
    echo "<h4>Probando: $method</h4>";
    echo "URL: <code>" . htmlspecialchars($url) . "</code><br>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
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

    echo "Código HTTP: <strong>$httpCode</strong><br>";
    
    if ($curlError) {
        echo "❌ Error de cURL: " . htmlspecialchars($curlError) . "<br>";
    } else {
        echo "✅ Petición enviada correctamente<br>";
        echo "Respuesta: " . htmlspecialchars(substr($response, 0, 200)) . "...<br>";
        
        if ($httpCode === 200 || $httpCode === 201) {
            echo "🎉 <strong>¡ÉXITO! Este método funciona</strong><br>";
        } else {
            echo "❌ Error HTTP $httpCode<br>";
        }
    }
    echo "<br>";
}

// Solución recomendada
echo "<h3>4. 💡 Solución Recomendada:</h3>";
if ($hasSpaces) {
    echo "<strong>Opción 1: Cambiar el nombre de la instancia</strong><br>";
    echo "Cambia el nombre de la instancia en Evolution API para que no tenga espacios.<br>";
    echo "Ejemplo: 'Mi Instancia' → 'MiInstancia' o 'mi-instancia'<br><br>";
    
    echo "<strong>Opción 2: Usar codificación URL en el código</strong><br>";
    echo "Modificar el código para usar <code>rawurlencode()</code> en el nombre de la instancia.<br><br>";
    
    echo "<strong>Opción 3: Reemplazar espacios por guiones</strong><br>";
    echo "Reemplazar espacios por guiones o guiones bajos en el nombre.<br><br>";
    
    // Mostrar código de ejemplo
    echo "<h4>📝 Código de ejemplo para la solución:</h4>";
    echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
    echo htmlspecialchars('// En lugar de:
$apiUrl = rtrim($evolutionApiUrl, \'/\') . \'/message/sendText/\' . $evolutionInstanceName;

// Usar:
$apiUrl = rtrim($evolutionApiUrl, \'/\') . \'/message/sendText/\' . rawurlencode($evolutionInstanceName);');
    echo "</pre>";
} else {
    echo "✅ No hay espacios en el nombre de la instancia. El problema debe ser otro.<br>";
}

echo "<h3>5. 🛠️ Scripts de Prueba:</h3>";
echo "<ul>";
echo "<li><a href='debug_broadcast_400.php'>debug_broadcast_400.php</a> - Diagnóstico completo de difusiones</li>";
echo "<li><a href='test_send.php'>test_send.php</a> - Prueba básica de envío</li>";
echo "</ul>";

echo "<h3>6. 📊 Información Adicional:</h3>";
echo "Para verificar el estado de la instancia con diferentes codificaciones:<br>";
echo "<ul>";
echo "<li>Estado sin codificar: " . htmlspecialchars(rtrim($evolutionApiUrl, '/') . '/instance/connectionState/' . $evolutionInstanceName) . "</li>";
echo "<li>Estado con rawurlencode: " . htmlspecialchars(rtrim($evolutionApiUrl, '/') . '/instance/connectionState/' . rawurlencode($evolutionInstanceName)) . "</li>";
echo "</ul>";
?> 