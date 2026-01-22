<?php
// Simular una petición POST al endpoint
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['list_id'] = '1';
$_POST['message'] = 'Test message';

// Incluir el endpoint
ob_start();
include __DIR__.'/../api/send_broadcast_bulk.php';
$output = ob_get_clean();

echo "Respuesta del endpoint:\n";
echo $output."\n";

// Verificar si hay errores en el log
echo "\nÚltimos errores del log:\n";
$logFile = 'C:/xampp/apache/logs/error.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -10);
    foreach ($lastLines as $line) {
        if (strpos($line, 'BROADCAST_BULK') !== false) {
            echo trim($line)."\n";
        }
    }
}
?> 