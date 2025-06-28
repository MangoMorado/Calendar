<?php
header('Content-Type: application/json');
ob_start();
include dirname(__DIR__, 2) . '/test/test_session_fixes.php';
$output = ob_get_clean();
$status = 'ok';
$message = 'Test ejecutado correctamente.';
if (stripos($output, 'error') !== false || stripos($output, 'fatal') !== false) {
    $status = 'error';
    $message = 'Se detectaron errores en el test.';
} elseif (stripos($output, 'warning') !== false || stripos($output, 'advertencia') !== false) {
    $status = 'warning';
    $message = 'El test arrojó advertencias.';
}
$response = [
    'status' => $status,
    'message' => $message,
    'output' => $output
];
echo json_encode($response); 