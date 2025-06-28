<?php
header('Content-Type: application/json');

// Incluir el test real y capturar la salida
ob_start();
include dirname(__DIR__, 2) . '/test/test_session_creation.php';
$output = ob_get_clean();

// Analizar la salida para determinar el resultado
$status = 'ok';
$message = 'Test ejecutado correctamente.';

if (stripos($output, 'error') !== false || stripos($output, 'fatal') !== false) {
    $status = 'error';
    $message = 'Se detectaron errores en el test.';
} elseif (stripos($output, 'warning') !== false || stripos($output, 'advertencia') !== false) {
    $status = 'warning';
    $message = 'El test arrojó advertencias.';
}

// Respuesta JSON
$response = [
    'status' => $status,
    'message' => $message,
    'output' => $output
];
echo json_encode($response); 