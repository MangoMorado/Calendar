<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

echo "<h1>Test de Conexión API</h1>";

// Verificar conexión a base de datos
echo "<h2>1. Conexión a Base de Datos</h2>";
if ($conn) {
    echo "✅ Conexión exitosa a MySQL<br>";
    echo "Base de datos: " . DB_NAME . "<br>";
} else {
    echo "❌ Error de conexión: " . mysqli_connect_error() . "<br>";
    exit;
}

// Verificar si las tablas necesarias existen
echo "<h2>2. Verificación de Tablas</h2>";
$requiredTables = [
    'users',
    'broadcast_lists', 
    'broadcast_history',
    'broadcast_details',
    'settings'
];

foreach ($requiredTables as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        echo "✅ Tabla '$table' existe<br>";
    } else {
        echo "❌ Tabla '$table' NO existe<br>";
    }
}

// Verificar autenticación
echo "<h2>3. Verificación de Autenticación</h2>";
if (isAuthenticated()) {
    $user = getCurrentUser();
    echo "✅ Usuario autenticado: " . $user['name'] . " (ID: " . $user['id'] . ")<br>";
} else {
    echo "❌ Usuario NO autenticado<br>";
}

// Verificar configuración de Evolution API
echo "<h2>4. Configuración de Evolution API</h2>";
$config = [];
$sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('evolution_api_url', 'evolution_api_key', 'evolution_instance_name')";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $config[$row['setting_key']] = $row['setting_value'];
}

if (!empty($config['evolution_api_url'])) {
    echo "✅ Evolution API URL configurada<br>";
} else {
    echo "❌ Evolution API URL NO configurada<br>";
}

if (!empty($config['evolution_api_key'])) {
    echo "✅ Evolution API Key configurada<br>";
} else {
    echo "❌ Evolution API Key NO configurada<br>";
}

if (!empty($config['evolution_instance_name'])) {
    echo "✅ Evolution Instance Name configurado<br>";
} else {
    echo "❌ Evolution Instance Name NO configurado<br>";
}

// Verificar directorio uploads
echo "<h2>5. Directorio Uploads</h2>";
$uploadsDir = __DIR__ . '/../uploads';
if (is_dir($uploadsDir)) {
    echo "✅ Directorio uploads existe<br>";
    if (is_writable($uploadsDir)) {
        echo "✅ Directorio uploads es escribible<br>";
    } else {
        echo "❌ Directorio uploads NO es escribible<br>";
    }
} else {
    echo "❌ Directorio uploads NO existe<br>";
}

echo "<h2>Test completado</h2>";
?> 