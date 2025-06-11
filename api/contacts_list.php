<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAuth();
header('Content-Type: application/json; charset=utf-8');

$sql = "SELECT number, pushName, send FROM contacts ORDER BY pushName, number";
$result = mysqli_query($conn, $sql);
$contactos = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Ignorar grupos por si acaso
    if (str_ends_with($row['number'], '@g.us')) continue;
    $contactos[] = [
        'number' => $row['number'],
        'pushName' => $row['pushName'],
        'send' => (bool)$row['send']
    ];
}
echo json_encode(['success' => true, 'contactos' => $contactos]); 