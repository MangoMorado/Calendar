<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

echo "<h2>Diagnóstico Detallado de import_contacts.php - Error 400</h2>";

// Verificar autenticación
echo "<h3>1. Verificación de autenticación:</h3>";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isAuthenticated()) {
    $user = getCurrentUser();
    echo "✅ Usuario autenticado: " . htmlspecialchars($user['name']) . "<br>";
} else {
    echo "❌ Usuario NO autenticado<br>";
    echo "Este es el problema principal. El usuario debe estar autenticado.<br>";
    exit;
}

// Verificar configuración de Evolution API
echo "<h3>2. Configuración de Evolution API:</h3>";
$sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('evolution_api_url', 'evolution_api_key', 'evolution_instance_name')";
$result = mysqli_query($conn, $sql);

$config = [];
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
} else {
    echo "<br><strong>✅ Configuración de Evolution API completa</strong><br>";
}

// Probar la conexión a Evolution API
echo "<h3>3. Prueba de conexión a Evolution API:</h3>";
if (!empty($evolutionApiUrl) && !empty($evolutionApiKey) && !empty($evolutionInstanceName)) {
    // Método correcto según documentación oficial: POST con body específico
    $apiUrl = rtrim($evolutionApiUrl, '/') . '/chat/findContacts/' . $evolutionInstanceName;
    $headers = [
        'Content-Type: application/json',
        'apikey: ' . $evolutionApiKey
    ];
    
    // Body según la documentación oficial de Evolution API
    $body = json_encode([
        "where" => [
            "id" => $evolutionInstanceName
        ]
    ]);

    echo "<h4>Método según documentación oficial: POST</h4>";
    echo "URL de la API: " . htmlspecialchars($apiUrl) . "<br>";
    echo "Headers: " . htmlspecialchars(json_encode($headers)) . "<br>";
    echo "Body: " . htmlspecialchars($body) . "<br>";
    echo "Método: POST (según documentación oficial)<br>";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
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

    echo "Código de respuesta HTTP: $httpCode<br>";
    
    if ($curlError) {
        echo "❌ Error de cURL: " . htmlspecialchars($curlError) . "<br>";
    } else {
        echo "✅ Conexión exitosa<br>";
        echo "Respuesta: " . htmlspecialchars(substr($response, 0, 500)) . "...<br>";
        
        if ($httpCode !== 200) {
            echo "❌ La API devolvió un código de error: $httpCode<br>";
            echo "<br><strong>Posibles causas del error 400:</strong><br>";
            echo "1. Instancia no conectada o no válida<br>";
            echo "2. API Key incorrecta o expirada<br>";
            echo "3. URL del servidor incorrecta<br>";
            echo "4. La instancia no tiene contactos<br>";
            echo "5. Problema de conectividad con el servidor Evolution API<br>";
        } else {
            echo "✅ La API respondió correctamente<br>";
            $data = json_decode($response, true);
            if (is_array($data)) {
                echo "✅ Respuesta válida JSON con " . count($data) . " elementos<br>";
            } else {
                echo "❌ Respuesta no es un JSON válido<br>";
            }
        }
    }

    // Método alternativo: POST con body vacío (para comparar)
    echo "<h4>Método alternativo: POST con body vacío</h4>";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response2 = curl_exec($ch);
    $httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError2 = curl_error($ch);
    curl_close($ch);

    echo "Código de respuesta HTTP: $httpCode2<br>";
    
    if ($curlError2) {
        echo "❌ Error de cURL: " . htmlspecialchars($curlError2) . "<br>";
    } else {
        echo "✅ Conexión exitosa<br>";
        echo "Respuesta: " . htmlspecialchars(substr($response2, 0, 200)) . "...<br>";
        
        if ($httpCode2 !== 200) {
            echo "❌ La API devolvió un código de error: $httpCode2<br>";
        } else {
            echo "✅ La API respondió correctamente<br>";
        }
    }

} else {
    echo "❌ No se puede probar la conexión porque faltan configuraciones<br>";
}

// Verificar tabla contacts
echo "<h3>4. Verificación de la tabla contacts:</h3>";
$checkTable = "SHOW TABLES LIKE 'contacts'";
$result = mysqli_query($conn, $checkTable);
if (mysqli_num_rows($result) > 0) {
    echo "✅ La tabla 'contacts' existe<br>";
    
    // Contar contactos existentes
    $countSql = "SELECT COUNT(*) as total FROM contacts";
    $countResult = mysqli_query($conn, $countSql);
    $countRow = mysqli_fetch_assoc($countResult);
    echo "Contactos existentes en la base de datos: " . $countRow['total'] . "<br>";
} else {
    echo "❌ La tabla 'contacts' NO existe<br>";
}

// Análisis del problema
echo "<h3>5. Análisis del problema según documentación oficial:</h3>";
echo "Según la <a href='https://doc.evolution-api.com/v1/api-reference/chat-controller/find-contacts' target='_blank'>documentación oficial de Evolution API</a>:<br>";
echo "• El endpoint requiere método POST<br>";
echo "• El body debe tener la estructura: {\"where\": {\"id\": \"nombre_instancia\"}}<br>";
echo "• El error 400 puede deberse a:<br>";
echo "  - Instancia no conectada<br>";
echo "  - API Key inválida<br>";
echo "  - URL del servidor incorrecta<br>";
echo "  - Problemas de conectividad<br>";

echo "<h3>6. Solución recomendada:</h3>";
echo "1. Verificar que la instancia esté conectada en Evolution API<br>";
echo "2. Confirmar que la API Key sea válida<br>";
echo "3. Verificar la URL del servidor Evolution API<br>";
echo "4. Revisar los logs del servidor Evolution API<br>";
echo "5. Probar la conexión desde Postman o similar<br>";

echo "<h3>7. Configuración de la petición POST probada:</h3>";

$config_json = [
    'url' => $apiUrl ?? null,
    'headers' => $headers ?? null,
    'body' => $body ?? null
];

echo '<pre style="background:#222;color:#0f0;padding:10px;border-radius:5px;">';
echo htmlspecialchars(json_encode($config_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
echo '</pre>';

?> 