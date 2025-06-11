<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAuth();
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['seleccion']) || !is_array($data['seleccion'])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}
$errores = 0;
foreach ($data['seleccion'] as $item) {
    $number = $item['number'] ?? null;
    $send = isset($item['send']) && $item['send'] ? 1 : 0;
    if (!$number) continue;
    $sql = "UPDATE contacts SET send = ? WHERE number = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "is", $send, $number);
    if (!mysqli_stmt_execute($stmt)) {
        $errores++;
    }
}
echo json_encode(['success' => $errores === 0]); 