<?php
require_once __DIR__.'/../config/database.php';
require_once 'config/database.php';
require_once 'includes/auth.php';

echo '<h2>Diagnóstico de import_contacts.php</h2>';

// Verificar conexión a la base de datos
echo '<h3>1. Verificación de conexión a la base de datos:</h3>';
if ($conn) {
    echo '✅ Conexión a la base de datos exitosa<br>';
} else {
    echo '❌ Error de conexión a la base de datos: '.mysqli_connect_error().'<br>';
    exit;
}

// Verificar si la tabla settings existe
echo '<h3>2. Verificación de la tabla settings:</h3>';
$checkTable = "SHOW TABLES LIKE 'settings'";
$result = mysqli_query($conn, $checkTable);
if (mysqli_num_rows($result) > 0) {
    echo "✅ La tabla 'settings' existe<br>";
} else {
    echo "❌ La tabla 'settings' NO existe<br>";
    exit;
}

// Verificar configuraciones de Evolution API
echo '<h3>3. Configuraciones de Evolution API:</h3>';
$sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('evolution_api_url', 'evolution_api_key', 'evolution_instance_name')";
$result = mysqli_query($conn, $sql);

$config = [];
while ($row = mysqli_fetch_assoc($result)) {
    $config[$row['setting_key']] = $row['setting_value'];
}

$evolutionApiUrl = $config['evolution_api_url'] ?? '';
$evolutionApiKey = $config['evolution_api_key'] ?? '';
$evolutionInstanceName = $config['evolution_instance_name'] ?? '';

echo 'evolution_api_url: '.($evolutionApiUrl ? '✅ Configurado' : '❌ NO configurado').'<br>';
echo 'evolution_api_key: '.($evolutionApiKey ? '✅ Configurado' : '❌ NO configurado').'<br>';
echo 'evolution_instance_name: '.($evolutionInstanceName ? '✅ Configurado' : '❌ NO configurado').'<br>';

if (empty($evolutionApiUrl) || empty($evolutionApiKey) || empty($evolutionInstanceName)) {
    echo '<br><strong>❌ PROBLEMA IDENTIFICADO: Configuración de Evolution API incompleta</strong><br>';
    echo 'Este es el motivo del error 400. Necesitas configurar estos valores en la página de configuración.<br>';
}

// Verificar si la tabla contacts existe
echo '<h3>4. Verificación de la tabla contacts:</h3>';
$checkContactsTable = "SHOW TABLES LIKE 'contacts'";
$result = mysqli_query($conn, $checkContactsTable);
if (mysqli_num_rows($result) > 0) {
    echo "✅ La tabla 'contacts' existe<br>";
} else {
    echo "❌ La tabla 'contacts' NO existe<br>";
}

// Verificar autenticación
echo '<h3>5. Verificación de autenticación:</h3>';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user']) && ! empty($_SESSION['user'])) {
    echo '✅ Usuario autenticado: '.$_SESSION['user']['name'].'<br>';
} else {
    echo '❌ Usuario NO autenticado<br>';
}

// Mostrar todas las configuraciones disponibles
echo '<h3>6. Todas las configuraciones en la tabla settings:</h3>';
$allSettings = 'SELECT setting_key, setting_value FROM settings ORDER BY setting_key';
$result = mysqli_query($conn, $allSettings);
if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo '<tr><th>Clave</th><th>Valor</th></tr>';
    while ($row = mysqli_fetch_assoc($result)) {
        $value = $row['setting_value'];
        // Ocultar valores sensibles
        if (strpos($row['setting_key'], 'key') !== false || strpos($row['setting_key'], 'password') !== false) {
            $value = str_repeat('*', min(strlen($value), 8));
        }
        echo '<tr><td>'.htmlspecialchars($row['setting_key']).'</td><td>'.htmlspecialchars($value).'</td></tr>';
    }
    echo '</table>';
} else {
    echo 'No hay configuraciones en la tabla settings<br>';
}

echo '<h3>7. Solución recomendada:</h3>';
echo 'Para solucionar el error 400, necesitas:<br>';
echo '1. Ir a la página de configuración del sistema<br>';
echo '2. Configurar los valores de Evolution API:<br>';
echo '   - evolution_api_url<br>';
echo '   - evolution_api_key<br>';
echo '   - evolution_instance_name<br>';
echo '3. Guardar la configuración<br>';
echo '4. Intentar importar contactos nuevamente<br>';
?> 