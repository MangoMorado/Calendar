<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

echo "<h2>Prueba de Importación de Contactos - Según Documentación Oficial</h2>";

// Verificar autenticación
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isAuthenticated()) {
    echo "❌ Usuario NO autenticado<br>";
    echo "Debes estar logueado para probar la importación.<br>";
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

echo "<h3>Configuración de Evolution API:</h3>";
echo "URL: " . htmlspecialchars($evolutionApiUrl) . "<br>";
echo "API Key: " . str_repeat('*', min(strlen($evolutionApiKey), 8)) . "<br>";
echo "Instancia: " . htmlspecialchars($evolutionInstanceName) . "<br><br>";

if (empty($evolutionApiUrl) || empty($evolutionApiKey) || empty($evolutionInstanceName)) {
    echo "❌ Configuración incompleta. Verifica los valores en la configuración.<br>";
    exit;
}

// Probar la importación con el método POST según documentación oficial
echo "<h3>Probando importación con método POST (documentación oficial):</h3>";

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

echo "URL: " . htmlspecialchars($apiUrl) . "<br>";
echo "Método: POST (según documentación oficial)<br>";
echo "Headers: " . htmlspecialchars(json_encode($headers)) . "<br>";
echo "Body: " . htmlspecialchars($body) . "<br><br>";

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

echo "<h3>Resultado de la petición:</h3>";
echo "Código HTTP: $httpCode<br>";

if ($curlError) {
    echo "❌ Error de cURL: " . htmlspecialchars($curlError) . "<br>";
    exit;
}

if ($httpCode !== 200) {
    echo "❌ Error HTTP $httpCode<br>";
    echo "Respuesta: " . htmlspecialchars(substr($response, 0, 500)) . "<br>";
    echo "<br><strong>Posibles causas del error:</strong><br>";
    echo "1. Instancia no conectada en Evolution API<br>";
    echo "2. API Key incorrecta o expirada<br>";
    echo "3. URL del servidor incorrecta<br>";
    echo "4. La instancia no tiene contactos<br>";
    echo "5. Problema de conectividad con el servidor<br>";
    echo "<br><strong>Recomendaciones:</strong><br>";
    echo "• Verifica el estado de la instancia en Evolution API<br>";
    echo "• Confirma que la API Key sea válida<br>";
    echo "• Revisa los logs del servidor Evolution API<br>";
    exit;
}

echo "✅ Petición exitosa<br>";
$data = json_decode($response, true);

if (!is_array($data)) {
    echo "❌ Respuesta no es un array válido<br>";
    echo "Respuesta: " . htmlspecialchars(substr($response, 0, 500)) . "<br>";
    exit;
}

echo "✅ Respuesta válida con " . count($data) . " contactos<br><br>";

// Procesar los contactos
echo "<h3>Procesando contactos:</h3>";
$imported = 0;
$updated = 0;
$skipped = 0;
$errores = [];

foreach ($data as $contact) {
    $remoteJid = $contact['remoteJid'] ?? '';
    $pushName = $contact['pushName'] ?? null;
    
    if (!$remoteJid || str_ends_with($remoteJid, '@g.us')) {
        $skipped++;
        continue; // Ignorar grupos
    }
    
    // Verificar si el contacto ya existe
    $sql = "SELECT id FROM contacts WHERE number = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $remoteJid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($res)) {
        // Actualizar contacto existente
        $sql2 = "UPDATE contacts SET pushName = ? WHERE number = ?";
        $stmt2 = mysqli_prepare($conn, $sql2);
        mysqli_stmt_bind_param($stmt2, "ss", $pushName, $remoteJid);
        if (mysqli_stmt_execute($stmt2)) {
            $updated++;
            echo "✅ Actualizado: " . htmlspecialchars($remoteJid) . " - " . htmlspecialchars($pushName) . "<br>";
        } else {
            $errores[] = "Error actualizando $remoteJid";
            echo "❌ Error actualizando: " . htmlspecialchars($remoteJid) . "<br>";
        }
    } else {
        // Insertar nuevo contacto
        $sql2 = "INSERT INTO contacts (number, pushName, send) VALUES (?, ?, 0)";
        $stmt2 = mysqli_prepare($conn, $sql2);
        mysqli_stmt_bind_param($stmt2, "ss", $remoteJid, $pushName);
        if (mysqli_stmt_execute($stmt2)) {
            $imported++;
            echo "✅ Insertado: " . htmlspecialchars($remoteJid) . " - " . htmlspecialchars($pushName) . "<br>";
        } else {
            $errores[] = "Error insertando $remoteJid";
            echo "❌ Error insertando: " . htmlspecialchars($remoteJid) . "<br>";
        }
    }
}

echo "<br><h3>Resumen de la importación:</h3>";
echo "✅ Contactos importados: $imported<br>";
echo "✅ Contactos actualizados: $updated<br>";
echo "⏭️ Contactos omitidos (grupos): $skipped<br>";
echo "❌ Errores: " . count($errores) . "<br>";

if (!empty($errores)) {
    echo "<br><h4>Errores detallados:</h4>";
    foreach ($errores as $error) {
        echo "• " . htmlspecialchars($error) . "<br>";
    }
}

echo "<br><h3>Verificación final:</h3>";
$countSql = "SELECT COUNT(*) as total FROM contacts";
$countResult = mysqli_query($conn, $countSql);
$countRow = mysqli_fetch_assoc($countResult);
echo "Total de contactos en la base de datos: " . $countRow['total'] . "<br>";

echo "<br><strong>✅ Importación completada exitosamente</strong><br>";
echo "<br><em>Nota: Este script usa el método POST según la <a href='https://doc.evolution-api.com/v1/api-reference/chat-controller/find-contacts' target='_blank'>documentación oficial de Evolution API</a></em><br>";
?> 