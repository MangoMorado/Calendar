<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

echo "<h2>Cambio de Instancia Evolution API</h2>";

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

// Obtener configuración actual
$config = [];
$sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('evolution_api_url', 'evolution_api_key', 'evolution_instance_name')";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $config[$row['setting_key']] = $row['setting_value'];
}

$evolutionApiUrl = $config['evolution_api_url'] ?? '';
$evolutionApiKey = $config['evolution_api_key'] ?? '';
$currentInstance = $config['evolution_instance_name'] ?? '';

echo "<h3>Configuración actual:</h3>";
echo "Instancia actual: " . htmlspecialchars($currentInstance) . "<br><br>";

if (empty($evolutionApiUrl) || empty($evolutionApiKey)) {
    echo "❌ Configuración incompleta<br>";
    exit;
}

// Procesar cambio de instancia
if (isset($_POST['new_instance']) && !empty($_POST['new_instance'])) {
    $newInstance = $_POST['new_instance'];
    
    // Actualizar en la base de datos
    $updateSql = "UPDATE settings SET setting_value = ? WHERE setting_key = 'evolution_instance_name'";
    $stmt = mysqli_prepare($conn, $updateSql);
    mysqli_stmt_bind_param($stmt, "s", $newInstance);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<div style='background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "✅ Instancia cambiada exitosamente a: " . htmlspecialchars($newInstance);
        echo "</div>";
        
        // Actualizar variable local
        $currentInstance = $newInstance;
    } else {
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "❌ Error al cambiar la instancia: " . mysqli_error($conn);
        echo "</div>";
    }
}

// Obtener lista de instancias disponibles
echo "<h3>Instancias disponibles:</h3>";
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
    if (is_array($instances) && count($instances) > 0) {
        echo "<form method='POST' style='margin: 20px 0;'>";
        echo "<label for='new_instance'><strong>Selecciona una nueva instancia:</strong></label><br>";
        echo "<select name='new_instance' id='new_instance' style='padding: 8px; margin: 10px 0; width: 300px;'>";
        
        foreach ($instances as $instance) {
            $instanceName = $instance['instanceName'] ?? 'N/A';
            $connectionStatus = $instance['connectionStatus'] ?? 'unknown';
            $selected = ($instanceName === $currentInstance) ? 'selected' : '';
            $statusText = ($connectionStatus === 'open') ? '✅ Conectada' : '❌ Desconectada';
            
            echo "<option value='" . htmlspecialchars($instanceName) . "' $selected>";
            echo htmlspecialchars($instanceName) . " - $statusText";
            echo "</option>";
        }
        
        echo "</select><br>";
        echo "<button type='submit' style='background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
        echo "Cambiar Instancia";
        echo "</button>";
        echo "</form>";
        
        echo "<h3>Estado de las instancias:</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Instancia</th><th>Estado</th><th>Token</th><th>Acciones</th></tr>";
        
        foreach ($instances as $instance) {
            $instanceName = $instance['instanceName'] ?? 'N/A';
            $connectionStatus = $instance['connectionStatus'] ?? 'unknown';
            $token = $instance['token'] ?? 'N/A';
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($instanceName) . "</td>";
            echo "<td>" . htmlspecialchars($connectionStatus) . "</td>";
            echo "<td>" . htmlspecialchars(substr($token, 0, 10)) . "...</td>";
            echo "<td>";
            
            if ($connectionStatus === 'open') {
                echo "<a href='test_import_contacts_fixed.php' style='color: #007bff; text-decoration: none;'>Probar importación</a>";
            } else {
                echo "<span style='color: #6c757d;'>Reconectar desde Evolution API</span>";
            }
            
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "❌ No se encontraron instancias disponibles<br>";
    }
}

echo "<br><h3>Instrucciones:</h3>";
echo "1. Selecciona una instancia que esté conectada (✅ Conectada)<br>";
echo "2. Haz clic en 'Cambiar Instancia'<br>";
echo "3. Prueba la importación de contactos<br>";
echo "4. Si una instancia no está conectada, reconéctala desde Evolution API<br>";

echo "<br><h3>Enlaces útiles:</h3>";
echo "• <a href='debug_compare_instances.php'>Diagnóstico completo de instancias</a><br>";
echo "• <a href='test_import_contacts_fixed.php'>Probar importación de contactos</a><br>";
echo "• <a href='debug_import_contacts_v2.php'>Diagnóstico detallado</a><br>";
?> 