<?php
// CLI script para enviar recordatorio de citas del día a n8n
// Uso recomendado: programar con el Task Scheduler de Windows a ejecutar cada hora/minuto

ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php_error.log');
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';

function getSetting($conn, $key, $default = null) {
    $sql = "SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $key);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) return $row['setting_value'];
    return $default;
}

// 1) Validar si el recordatorio está habilitado
$enabled = getSetting($conn, 'notifications_daily_appointments_enabled', '0') === '1';
if (!$enabled) {
    echo "Recordatorio desactivado.\n";
    exit(0);
}

// 2) Validar hora programada
$sendTime = getSetting($conn, 'notifications_send_time', '09:00');
$tz = getSetting($conn, 'timezone', 'America/Bogota');
date_default_timezone_set($tz);

$now = new DateTime('now');
$currentHm = $now->format('H:i');

// Si se llama con ?force=1 o CLI arg --force, enviar siempre
$force = false;
if (php_sapi_name() === 'cli') {
    $force = in_array('--force', $argv ?? []);
} else {
    $force = isset($_GET['force']) && $_GET['force'] == '1';
}

if (!$force && $currentHm !== $sendTime) {
    echo "No es la hora programada ({$sendTime}). Ahora: {$currentHm}\n";
    exit(0);
}

// 3) Obtener citas del día en curso
$start = (new DateTime('today'))->format('Y-m-d 00:00:00');
$end = (new DateTime('today 23:59:59'))->format('Y-m-d H:i:s');

$sql = "SELECT a.id, a.title, a.description, a.start_time, a.end_time, a.calendar_type, a.all_day,
               u.id as user_id, u.name as user_name, u.email as user_email, u.phone as user_phone
        FROM appointments a
        LEFT JOIN users u ON a.user_id = u.id
        WHERE a.start_time BETWEEN ? AND ?
        ORDER BY a.start_time ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ss', $start, $end);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$appointments = [];
while ($row = mysqli_fetch_assoc($result)) {
    $appointments[] = $row;
}

// 4) Construir payload
$payload = [
    'date' => (new DateTime('today'))->format('Y-m-d'),
    'timezone' => $tz,
    'count' => count($appointments),
    'appointments' => array_map(function($a) {
        return [
            'id' => (int)$a['id'],
            'title' => $a['title'],
            'description' => $a['description'],
            'start_time' => $a['start_time'],
            'end_time' => $a['end_time'],
            'calendar_type' => $a['calendar_type'],
            'all_day' => (int)$a['all_day'] === 1,
            'user' => [
                'id' => isset($a['user_id']) ? (int)$a['user_id'] : null,
                'name' => $a['user_name'] ?? null,
                'email' => $a['user_email'] ?? null,
                'phone' => $a['user_phone'] ?? null
            ]
        ];
    }, $appointments)
];

// 5) Resolver webhook (desde settings o fallback)
$webhook = getSetting($conn, 'notifications_webhook_url', 'https://n8n.mangomorado.com/webhook/notificaciones_mundoanimal');

// 6) Enviar a webhook n8n
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhook);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    error_log('[CRON NOTIFY] cURL error: ' . $curlError);
    echo "Error de conexión: $curlError\n";
    exit(1);
}

echo "Webhook respondio HTTP {$httpCode}\n";
echo $response . "\n";
exit($httpCode >= 200 && $httpCode < 300 ? 0 : 1);
?>


