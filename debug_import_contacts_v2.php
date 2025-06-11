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
    $apiUrl = rtrim($evolutionApiUrl, '/') . '/chat/findContacts/' . $evolutionInstanceName;
    $headers = [
        'Content-Type: application/json',
        'apikey: ' . $evolutionApiKey
    ];
    $body = json_encode(["where" => ["id" => $evolutionInstanceName]]);

    echo "URL de la API: " . htmlspecialchars($apiUrl) . "<br>";
    echo "Headers: " . htmlspecialchars(json_encode($headers)) . "<br>";
    echo "Body: " . htmlspecialchars($body) . "<br>";

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
        echo "Respuesta: " . htmlspecialchars(substr($response, 0, 200)) . "...<br>";
        
        if ($httpCode !== 200) {
            echo "❌ La API devolvió un código de error: $httpCode<br>";
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

// Simular el flujo completo
echo "<h3>5. Simulación del flujo completo:</h3>";
echo "Si todo está configurado correctamente, el endpoint debería funcionar.<br>";
echo "El error 400 probablemente se debe a:<br>";
echo "1. Usuario no autenticado<br>";
echo "2. Configuración de Evolution API incompleta<br>";
echo "3. Error en la respuesta de Evolution API<br>";

echo "<h3>6. Solución recomendada:</h3>";
echo "1. Asegúrate de estar logueado en el navegador<br>";
echo "2. Verifica que las configuraciones de Evolution API estén correctas<br>";
echo "3. Si el problema persiste, revisa los logs del servidor<br>";
?> 