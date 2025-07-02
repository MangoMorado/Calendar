<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

$broadcastId = (int)($_GET['id'] ?? 0);
if (!$broadcastId) {
    echo json_encode(['success' => false, 'message' => 'ID de difusión requerido']);
    exit;
}

// Obtener datos generales de la difusión
$sql = "SELECT status, total_contacts FROM broadcast_history WHERE id = $broadcastId";
$res = mysqli_query($conn, $sql);
$broadcast = mysqli_fetch_assoc($res);
if (!$broadcast) {
    echo json_encode(['success' => false, 'message' => 'Difusión no encontrada']);
    exit;
}

// Obtener detalles
$sql = "SELECT status FROM broadcast_details WHERE broadcast_id = $broadcastId";
$res = mysqli_query($conn, $sql);
$total = 0;
$sent = 0;
$failed = 0;
$pending = 0;
while ($row = mysqli_fetch_assoc($res)) {
    $total++;
    switch ($row['status']) {
        case 'sent': $sent++; break;
        case 'failed': $failed++; break;
        default: $pending++; break;
    }
}
$percentage = $total > 0 ? round((($sent + $failed) / $total) * 100) : 0;
// El status debe ser 'completed' solo si no hay pendientes y la instancia está conectada
if ($pending === 0) {
    $status = 'completed';
} else {
    $status = 'in_progress';
}

// Consultar estado de la instancia Evolution API
$config = [];
$sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('evolution_api_url', 'evolution_api_key', 'evolution_instance_name')";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $config[$row['setting_key']] = $row['setting_value'];
}
$evolutionApiUrl = $config['evolution_api_url'] ?? '';
$evolutionApiKey = $config['evolution_api_key'] ?? '';
$evolutionInstanceName = $config['evolution_instance_name'] ?? '';
$instanceState = 'unknown';
if (!empty($evolutionApiUrl) && !empty($evolutionApiKey) && !empty($evolutionInstanceName)) {
    $checkApiUrl = rtrim($evolutionApiUrl, '/') . '/instance/connectionState/' . rawurlencode($evolutionInstanceName);
    $checkHeaders = ['apikey: ' . $evolutionApiKey];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $checkApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $checkHeaders);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $checkResponse = curl_exec($ch);
    $checkHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($checkHttpCode === 200) {
        $checkData = json_decode($checkResponse, true);
        if (isset($checkData['instance']['state'])) {
            $instanceState = $checkData['instance']['state'];
        } else if (isset($checkData['state'])) {
            $instanceState = $checkData['state'];
        }
    }
}
if (strtolower($instanceState) !== 'open') {
    $status = 'disconnected';
}

// Actualizar estado si terminó
if ($status === 'completed' && $broadcast['status'] !== 'completed') {
    mysqli_query($conn, "UPDATE broadcast_history SET status = 'completed', sent_successfully = $sent, sent_failed = $failed WHERE id = $broadcastId");
}

// Obtener logs de actividad recientes de broadcast_details
$logs = [];
$sql = "SELECT contact_number, status, error_message, sent_at FROM broadcast_details WHERE broadcast_id = $broadcastId ORDER BY id ASC";
$res = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($res)) {
    $logMsg = '';
    if ($row['status'] === 'sent') {
        $logMsg = '✅ Enviado a ' . $row['contact_number'] . ($row['sent_at'] ? ' (' . $row['sent_at'] . ')' : '');
    } elseif ($row['status'] === 'failed') {
        $logMsg = '❌ Error enviando a ' . $row['contact_number'] . ': ' . $row['error_message'];
    } else {
        $logMsg = '⏳ Pendiente: ' . $row['contact_number'];
    }
    $logs[] = $logMsg;
}

echo json_encode([
    'success' => true,
    'total' => $total,
    'sent' => $sent,
    'failed' => $failed,
    'pending' => $pending,
    'percentage' => $percentage,
    'status' => $status,
    'instance_state' => $instanceState,
    'logs' => $logs
]);
exit; 