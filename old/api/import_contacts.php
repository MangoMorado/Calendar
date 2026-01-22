<?php

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/chatbot/contactos-validation.php';

// Verificar autenticación y devolver JSON en lugar de redirigir
if (! isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// Obtener configuración de Evolution API y nombre de instancia
$config = [];
$sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('evolution_api_url', 'evolution_api_key', 'evolution_instance_name')";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $config[$row['setting_key']] = $row['setting_value'];
}

$evolutionApiUrl = $config['evolution_api_url'] ?? '';
$evolutionApiKey = $config['evolution_api_key'] ?? '';
$evolutionInstanceName = $config['evolution_instance_name'] ?? '';

if (empty($evolutionApiUrl) || empty($evolutionApiKey) || empty($evolutionInstanceName)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Configuración de Evolution API incompleta']);
    exit;
}

// Preparar cURL para obtener contactos - USAR POST como indica la documentación oficial
$apiUrl = rtrim($evolutionApiUrl, '/').'/chat/findContacts/'.rawurlencode($evolutionInstanceName);
$headers = [
    'Content-Type: application/json',
    'apikey: '.$evolutionApiKey,
];

$body = '{}';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión: '.$curlError]);
    exit;
}

if ($httpCode !== 200 || ! $response) {
    http_response_code($httpCode);
    echo json_encode(['success' => false, 'message' => 'Error HTTP '.$httpCode, 'response' => $response]);
    exit;
}

$data = json_decode($response, true);
if (! is_array($data)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Respuesta inválida de Evolution API']);
    exit;
}

$imported = 0;
$updated = 0;
$skipped = 0;
$errores = [];

foreach ($data as $contact) {
    $remoteJid = $contact['remoteJid'] ?? '';
    $pushName = $contact['pushName'] ?? null;

    // Validaciones básicas
    if (! $remoteJid || str_ends_with($remoteJid, '@g.us') || str_contains($remoteJid, '@lid')) {
        $skipped++;

        continue;
    }

    // Validación robusta del número de WhatsApp
    $validacion = limpiarYValidarNumeroWhatsApp($remoteJid);
    if (! $validacion['valid']) {
        $skipped++;

        continue;
    }
    // Insertar o actualizar contacto
    $sql = 'SELECT id FROM contacts WHERE number = ?';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $remoteJid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        // Actualizar nombre si cambió
        $sql2 = 'UPDATE contacts SET pushName = ? WHERE number = ?';
        $stmt2 = mysqli_prepare($conn, $sql2);
        mysqli_stmt_bind_param($stmt2, 'ss', $pushName, $remoteJid);
        if (mysqli_stmt_execute($stmt2)) {
            $updated++;
        } else {
            $errores[] = "Error actualizando $remoteJid";
        }
    } else {
        // Insertar nuevo contacto, send = false por defecto
        $sql2 = 'INSERT INTO contacts (number, pushName, send) VALUES (?, ?, 0)';
        $stmt2 = mysqli_prepare($conn, $sql2);
        mysqli_stmt_bind_param($stmt2, 'ss', $remoteJid, $pushName);
        if (mysqli_stmt_execute($stmt2)) {
            $imported++;
        } else {
            $errores[] = "Error insertando $remoteJid";
        }
    }
}

echo json_encode([
    'success' => true,
    'imported' => $imported,
    'updated' => $updated,
    'skipped' => $skipped,
    'errores' => $errores,
]);
