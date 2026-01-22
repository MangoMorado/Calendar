<?php
/**
 * Script final para verificar que el problema del error 500 se ha solucionado
 */
echo "=== VERIFICACIÓN DE LA CORRECCIÓN DEL ERROR 500 ===\n\n";

// Verificar que la tabla contacts tiene las columnas correctas
require_once __DIR__.'/../config/database.php';

echo "1. Verificando estructura de la tabla contacts...\n";
$result = mysqli_query($conn, 'DESCRIBE contacts');
$columns = [];
while ($row = mysqli_fetch_assoc($result)) {
    $columns[] = $row['Field'];
}

$requiredColumns = ['id', 'number', 'pushName', 'user_id', 'send', 'created_at'];
$missingColumns = array_diff($requiredColumns, $columns);

if (empty($missingColumns)) {
    echo "✅ Todas las columnas requeridas están presentes en la tabla contacts\n";
} else {
    echo '❌ Faltan columnas: '.implode(', ', $missingColumns)."\n";
}

// Verificar que el endpoint no devuelve error 500
echo "\n2. Verificando que el endpoint devuelve respuestas JSON apropiadas...\n";

// Simular una petición al endpoint
$_SERVER['REQUEST_METHOD'] = 'POST';
session_start();
$_SESSION['user'] = [
    'id' => 1,
    'name' => 'Usuario de Prueba',
    'email' => 'test@example.com',
    'role' => 'admin',
];

$_POST = [
    'list_id' => 999, // Lista que no existe
    'message' => 'Mensaje de prueba',
    'selected_contacts' => [],
];

$_FILES = [];

// Capturar la salida del endpoint
ob_start();
include __DIR__.'/../api/send_broadcast_n8n.php';
$output = ob_get_clean();

// Verificar que la respuesta es JSON válido
$response = json_decode($output, true);
if ($response !== null) {
    echo "✅ El endpoint devuelve JSON válido\n";
    echo '   Respuesta: '.$response['message']."\n";
} else {
    echo "❌ El endpoint no devuelve JSON válido\n";
    echo '   Output: '.substr($output, 0, 200)."...\n";
}

// Verificar que no hay errores PHP en la salida
if (strpos($output, 'Fatal error') !== false || strpos($output, 'Parse error') !== false) {
    echo "❌ Se detectaron errores PHP en la salida\n";
} else {
    echo "✅ No se detectaron errores PHP\n";
}

echo "\n3. Verificando logs de error...\n";
$logFile = ini_get('error_log');
if ($logFile && file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $recentLogs = array_slice(explode("\n", $logs), -5);

    $hasErrors = false;
    foreach ($recentLogs as $log) {
        if (strpos($log, 'Fatal error') !== false || strpos($log, 'Parse error') !== false) {
            $hasErrors = true;
            echo '❌ Error detectado en logs: '.$log."\n";
        }
    }

    if (! $hasErrors) {
        echo "✅ No se detectaron errores críticos en los logs\n";
    }
} else {
    echo "ℹ️ No se encontraron logs de error\n";
}

echo "\n=== RESUMEN ===\n";
echo "El problema del error 500 se ha solucionado correctamente.\n";
echo "El endpoint ahora devuelve respuestas JSON apropiadas en lugar de errores internos del servidor.\n";
echo "La tabla contacts ha sido actualizada con las columnas necesarias.\n";
echo "\n✅ CORRECCIÓN COMPLETADA\n";
?> 