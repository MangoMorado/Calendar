<?php
// Script de prueba para el endpoint de envío de mensajes
session_start();
$_SESSION['user'] = ['id' => 1, 'name' => 'Test User', 'email' => 'test@test.com', 'role' => 'admin'];

// Forzar método POST para el endpoint
$_SERVER['REQUEST_METHOD'] = 'POST';

require_once __DIR__ . '/../config/database.php';

echo "Probando endpoint de envío de mensajes...\n";

// Usar el número exacto de la base de datos para debug
$_POST['number'] = '573217058135@s.whatsapp.net';
$_POST['mensaje'] = 'Mensaje de prueba desde el sistema Mundo Animal - ' . date('Y-m-d H:i:s');

// Incluir el endpoint
ob_start();
include __DIR__ . '/../api/send_broadcast.php';
$response = ob_get_clean();

echo "Respuesta del endpoint:\n";
echo $response . "\n";

// Decodificar respuesta para análisis
$data = json_decode($response, true);
if ($data) {
    if ($data['success']) {
        echo "✅ Prueba exitosa\n";
    } else {
        echo "❌ Error: " . $data['message'] . "\n";
    }
} else {
    echo "❌ Respuesta inválida\n";
}
?> 