<?php
/**
 * Script de prueba para verificar que el endpoint de broadcast funciona correctamente
 */

// Simular variables del servidor
$_SERVER['REQUEST_METHOD'] = 'POST';

// Simular una sesión de usuario autenticado
session_start();
$_SESSION['user'] = [
    'id' => 1,
    'name' => 'Usuario de Prueba',
    'email' => 'test@example.com',
    'role' => 'admin'
];

// Simular datos POST
$_POST = [
    'list_id' => 1,
    'message' => 'Mensaje de prueba desde script de verificación',
    'selected_contacts' => []
];

// Simular archivos (sin imagen)
$_FILES = [];

// Incluir el endpoint
ob_start();
include __DIR__ . '/../api/send_broadcast_n8n.php';
$output = ob_get_clean();

echo "=== PRUEBA DEL ENDPOINT DE BROADCAST ===\n";
echo "Output del endpoint:\n";
echo $output . "\n";

// Verificar si hay errores en los logs
echo "\n=== VERIFICACIÓN DE LOGS ===\n";
$logFile = ini_get('error_log');
if ($logFile && file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $recentLogs = array_slice(explode("\n", $logs), -10);
    echo "Últimos 10 logs:\n";
    foreach ($recentLogs as $log) {
        if (strpos($log, 'send_broadcast_n8n') !== false) {
            echo $log . "\n";
        }
    }
} else {
    echo "No se encontraron logs de error\n";
}

echo "\n=== VERIFICACIÓN DE BASE DE DATOS ===\n";
require_once __DIR__ . '/../config/database.php';

// Verificar si existe la tabla contacts con las columnas correctas
$result = mysqli_query($conn, "DESCRIBE contacts");
if ($result) {
    echo "Estructura de la tabla contacts:\n";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- {$row['Field']}: {$row['Type']}\n";
    }
} else {
    echo "Error al verificar tabla contacts: " . mysqli_error($conn) . "\n";
}

// Verificar si hay listas de broadcast
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM broadcast_lists");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "Listas de broadcast disponibles: {$row['count']}\n";
} else {
    echo "Error al verificar listas de broadcast: " . mysqli_error($conn) . "\n";
}

// Verificar si hay contactos
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM contacts");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "Contactos disponibles: {$row['count']}\n";
} else {
    echo "Error al verificar contactos: " . mysqli_error($conn) . "\n";
}

echo "\n=== PRUEBA COMPLETADA ===\n";
?> 