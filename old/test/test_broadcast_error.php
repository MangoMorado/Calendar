<?php
/**
 * Script de diagnóstico para el error 500 en send_broadcast_n8n.php
 * Ejecutar desde el navegador: test/test_broadcast_error.php
 */

// Activar reporte de errores
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

echo '<h1>Diagnóstico de Error en Difusiones</h1>';

// 1. Verificar conexión a base de datos
echo '<h2>1. Verificación de Base de Datos</h2>';
try {
    require_once '../config/database.php';
    echo '✅ Conexión a base de datos: OK<br>';

    // Verificar tablas necesarias
    $tables = ['broadcast_history', 'broadcast_lists', 'broadcast_list_contacts', 'contacts', 'settings'];
    foreach ($tables as $table) {
        $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
        if (mysqli_num_rows($result) > 0) {
            echo "✅ Tabla $table: Existe<br>";
        } else {
            echo "❌ Tabla $table: NO EXISTE<br>";
        }
    }
} catch (Exception $e) {
    echo '❌ Error de base de datos: '.$e->getMessage().'<br>';
}

// 2. Verificar archivos requeridos
echo '<h2>2. Verificación de Archivos</h2>';
$requiredFiles = [
    '../config/database.php',
    '../includes/auth.php',
    '../models/BroadcastHistoryModel.php',
    '../models/BroadcastListModel.php',
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file: Existe<br>";
    } else {
        echo "❌ $file: NO EXISTE<br>";
    }
}

// 3. Verificar configuración de n8n
echo '<h2>3. Configuración de n8n</h2>';
$sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('n8n_url', 'n8n_api_key', 'n8n_broadcast_webhook_url')";
$result = mysqli_query($conn, $sql);
$config = [];
while ($row = mysqli_fetch_assoc($result)) {
    $config[$row['setting_key']] = $row['setting_value'];
}

foreach (['n8n_url', 'n8n_api_key', 'n8n_broadcast_webhook_url'] as $key) {
    if (! empty($config[$key])) {
        echo "✅ $key: Configurado<br>";
    } else {
        echo "❌ $key: NO CONFIGURADO<br>";
    }
}

// 4. Verificar permisos de directorios
echo '<h2>4. Permisos de Directorios</h2>';
$directories = [
    '../uploads',
    '../logs',
];

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "✅ $dir: Existe y es escribible<br>";
        } else {
            echo "⚠️ $dir: Existe pero NO es escribible<br>";
        }
    } else {
        echo "❌ $dir: NO EXISTE<br>";
    }
}

// 5. Verificar funciones PHP requeridas
echo '<h2>5. Funciones PHP Requeridas</h2>';
$functions = ['curl_init', 'json_encode', 'base64_encode', 'file_get_contents'];
foreach ($functions as $function) {
    if (function_exists($function)) {
        echo "✅ $function(): Disponible<br>";
    } else {
        echo "❌ $function(): NO DISPONIBLE<br>";
    }
}

// 6. Verificar logs de error
echo '<h2>6. Logs de Error Recientes</h2>';
$logFile = '../php_error.log';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $recentLogs = array_slice(explode("\n", $logs), -20); // Últimas 20 líneas
    echo '<pre>';
    foreach ($recentLogs as $log) {
        if (trim($log)) {
            echo htmlspecialchars($log)."\n";
        }
    }
    echo '</pre>';
} else {
    echo '❌ No se encontró archivo de log<br>';
}

// 7. Simular una petición POST básica
echo '<h2>7. Simulación de Petición POST</h2>';
echo "<form method='post' action='../api/send_broadcast_n8n.php' enctype='multipart/form-data'>";
echo "<input type='hidden' name='list_id' value='1'>";
echo "<textarea name='message' placeholder='Mensaje de prueba'>Mensaje de prueba</textarea><br>";
echo "<input type='submit' value='Probar Endpoint'>";
echo '</form>';

// 8. Verificar versión de PHP
echo '<h2>8. Información del Sistema</h2>';
echo 'PHP Version: '.phpversion().'<br>';
echo 'Server Software: '.$_SERVER['SERVER_SOFTWARE'].'<br>';
echo 'Document Root: '.$_SERVER['DOCUMENT_ROOT'].'<br>';

echo '<hr>';
echo '<p><strong>Si encuentras errores, revisa los logs del servidor web (Apache/Nginx) para más detalles.</strong></p>';
?> 