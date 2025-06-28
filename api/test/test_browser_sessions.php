<?php
header('Content-Type: application/json');
$GLOBALS['__TEST_API_MODE__'] = true;
ob_start();
include dirname(__DIR__, 2) . '/test/test_browser_sessions.php';
$output = trim(ob_get_clean());

$status = 'error';
$message = 'Se detectaron errores en el test.';

if ($output === 'ok') {
    $status = 'ok';
    $message = 'Test ejecutado correctamente.';
} elseif ($output === 'warning') {
    $status = 'warning';
    $message = 'El test arrojó advertencias.';
}

$response = [
    'status' => $status,
    'message' => $message,
    'output' => $output
];
echo json_encode($response); 