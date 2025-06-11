<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

echo "<h2>Diagnóstico de Comparación de Instancias Evolution API</h2>";

// Verificar autenticación
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isAuthenticated()) {
    echo "❌ Usuario NO autenticado<br>";
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

echo "<h3>Configuración actual:</h3>";
echo "URL: " . htmlspecialchars($evolutionApiUrl) . "<br>";
echo "API Key: " . str_repeat('*', min(strlen($evolutionApiKey), 8)) . "<br>";
echo "Instancia configurada: " . htmlspecialchars($evolutionInstanceName) . "<br><br>";

if (empty($evolutionApiUrl) || empty($evolutionApiKey)) {
    echo "❌ Configuración incompleta<br>";
    exit;
}

// Función para probar una instancia
function testInstance($apiUrl, $apiKey, $instanceName) {
    echo "<h4>Probando instancia: " . htmlspecialchars($instanceName) . "</h4>";
    
    $url = rtrim($apiUrl, '/') . '/chat/findContacts/' . $instanceName;
    $headers = [
        'Content-Type: application/json',
        'apikey: ' . $apiKey
    ];
    
    $body = json_encode([
        "where" => [
            "id" => $instanceName
        ]
    ]);
    
    echo "URL: " . htmlspecialchars($url) . "<br>";
    echo "Body: " . htmlspecialchars($body) . "<br>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    echo "Código HTTP: $httpCode<br>";
    
    if ($curlError) {
        echo "❌ Error de cURL: " . htmlspecialchars($curlError) . "<br>";
        return false;
    }
    
    if ($httpCode !== 200) {
        echo "❌ Error HTTP $httpCode<br>";
        echo "Respuesta: " . htmlspecialchars(substr($response, 0, 300)) . "<br>";
        return false;
    }
    
    echo "✅ Petición exitosa<br>";
    $data = json_decode($response, true);
    
    if (is_array($data)) {
        echo "✅ Respuesta válida con " . count($data) . " contactos<br>";
        return true;
    } else {
        echo "❌ Respuesta no es un array válido<br>";
        return false;
    }
}

// Probar la instancia configurada
echo "<h3>1. Probando instancia configurada:</h3>";
$configSuccess = testInstance($evolutionApiUrl, $evolutionApiKey, $evolutionInstanceName);

// Obtener lista de todas las instancias disponibles
echo "<h3>2. Obteniendo lista de instancias disponibles:</h3>";
$instancesUrl = rtrim($evolutionApiUrl, '/') . '/instance/fetchInstances';
$headers = [
    'accept: application/json',
    'apikey: ' . $evolutionApiKey
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $instancesUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo "❌ Error obteniendo instancias: " . htmlspecialchars($curlError) . "<br>";
} elseif ($httpCode !== 200) {
    echo "❌ Error HTTP $httpCode al obtener instancias<br>";
} else {
    $instances = json_decode($response, true);
    if (is_array($instances)) {
        echo "✅ Se encontraron " . count($instances) . " instancias:<br>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Instancia</th><th>Estado</th><th>Token</th><th>Prueba</th></tr>";
        
        foreach ($instances as $instance) {
            $instanceName = $instance['instanceName'] ?? 'N/A';
            $connectionStatus = $instance['connectionStatus'] ?? 'unknown';
            $token = $instance['token'] ?? 'N/A';
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($instanceName) . "</td>";
            echo "<td>" . htmlspecialchars($connectionStatus) . "</td>";
            echo "<td>" . htmlspecialchars(substr($token, 0, 10)) . "...</td>";
            
            // Probar esta instancia
            echo "<td>";
            if ($connectionStatus === 'open') {
                $testResult = testInstance($evolutionApiUrl, $evolutionApiKey, $instanceName);
                if ($testResult) {
                    echo "✅ Funciona";
                } else {
                    echo "❌ Error";
                }
            } else {
                echo "⚠️ No conectada";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ Respuesta inválida al obtener instancias<br>";
    }
}

// Análisis de diferencias
echo "<h3>3. Análisis de diferencias:</h3>";
if ($configSuccess) {
    echo "✅ La instancia configurada funciona correctamente<br>";
} else {
    echo "❌ La instancia configurada tiene problemas<br>";
    echo "<br><strong>Posibles soluciones:</strong><br>";
    echo "1. Verificar que la instancia esté conectada<br>";
    echo "2. Reconectar la instancia desde Evolution API<br>";
    echo "3. Verificar que la instancia tenga contactos<br>";
    echo "4. Cambiar a otra instancia que funcione<br>";
}

echo "<br><h3>4. Recomendaciones:</h3>";
echo "• Si otra instancia funciona, considera cambiar la configuración a esa instancia<br>";
echo "• Verifica el estado de conexión de la instancia 'Mundo Animal'<br>";
echo "• Revisa los logs del servidor Evolution API para más detalles<br>";
echo "• Considera reconectar la instancia problemática<br>";
?> 