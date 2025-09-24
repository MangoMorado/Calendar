<?php
// Incluir archivos necesarios
require_once 'includes/auth.php';
require_once 'config/database.php';

// Verificar autenticación
requireAuth();

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos de la petición
$action = $_POST['action'] ?? '';
$workflowId = $_POST['workflow_id'] ?? '';
$newStatus = $_POST['new_status'] ?? '';
$instanceToken = $_POST['instance_token'] ?? '';
$notificationsSendTime = $_POST['notifications_send_time'] ?? '';
$notificationsDailyEnabled = $_POST['notifications_daily_appointments_enabled'] ?? '';

// Verificar que los datos necesarios estén presentes
if (empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Acción no especificada']);
    exit;
}

// Obtener configuración de n8n desde la base de datos
$n8nConfig = [];
$sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('n8n_url', 'n8n_api_key', 'evolution_api_url', 'evolution_api_key')";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $n8nConfig[$row['setting_key']] = $row['setting_value'];
}

$n8nUrl = $n8nConfig['n8n_url'] ?? '';
$n8nApiKey = $n8nConfig['n8n_api_key'] ?? '';
$evolutionApiUrl = $n8nConfig['evolution_api_url'] ?? '';
$evolutionApiKey = $n8nConfig['evolution_api_key'] ?? '';

// Manejar la acción de toggle del workflow
if ($action === 'toggle_workflow') {
    // Verificar que tengamos la configuración necesaria
    if (empty($n8nUrl) || empty($n8nApiKey) || empty($workflowId) || empty($newStatus)) {
        echo json_encode(['success' => false, 'message' => 'Configuración de n8n incompleta o datos faltantes']);
        exit;
    }
    
    // Determinar el endpoint según el nuevo estado
    $endpoint = $newStatus === 'active' ? '/activate' : '/deactivate';
    $apiUrl = rtrim($n8nUrl, '/') . '/api/v1/workflows/' . $workflowId . $endpoint;
    
    // Configurar cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: application/json',
        'X-N8N-API-KEY: ' . $n8nApiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    // Ejecutar la petición
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Verificar si hubo error en cURL
    if ($curlError) {
        echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $curlError]);
        exit;
    }
    
    // Verificar el código de respuesta HTTP
    if ($httpCode === 200 || $httpCode === 201) {
        echo json_encode(['success' => true, 'message' => 'Workflow ' . ($newStatus === 'active' ? 'activado' : 'desactivado') . ' correctamente']);
    } else {
        // Intentar obtener más información del error
        $errorMessage = 'Error HTTP ' . $httpCode;
        if ($response) {
            $errorData = json_decode($response, true);
            if (is_array($errorData) && isset($errorData['message'])) {
                $errorMessage = $errorData['message'];
            }
        }
        echo json_encode(['success' => false, 'message' => $errorMessage]);
    }
} 
// Manejar la acción de toggle de Evolution API
elseif ($action === 'toggle_evolution_instance') {
    // Verificar que tengamos la configuración necesaria
    if (empty($evolutionApiUrl) || empty($evolutionApiKey) || empty($newStatus)) {
        echo json_encode(['success' => false, 'message' => 'Configuración de Evolution API incompleta o datos faltantes']);
        exit;
    }
    
    // Obtener el nombre de la instancia desde la base de datos
    $evolutionInstanceName = '';
    $sql = "SELECT setting_value FROM settings WHERE setting_key = 'evolution_instance_name' LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $evolutionInstanceName = $row['setting_value'];
    }
    
    if (empty($evolutionInstanceName)) {
        echo json_encode(['success' => false, 'message' => 'Nombre de instancia no encontrado en la configuración']);
        exit;
    }
    
    // Para Evolution API, solo podemos desconectar (no reconectar desde aquí)
    if ($newStatus === 'inactive') {
        $apiUrl = rtrim($evolutionApiUrl, '/') . '/instance/logout/' . rawurlencode($evolutionInstanceName);
        $headers = [
            'accept: application/json',
            'apikey: ' . $evolutionApiKey
        ];
        
        // Configurar cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        // Ejecutar la petición
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // LOG de depuración
        error_log("[EVOLUTION LOGOUT] Instance Name from DB: $evolutionInstanceName");
        error_log("[EVOLUTION LOGOUT] URL: $apiUrl");
        error_log("[EVOLUTION LOGOUT] HTTP Code: $httpCode");
        error_log("[EVOLUTION LOGOUT] Response: $response");
        if ($curlError) {
            error_log("[EVOLUTION LOGOUT] cURL Error: $curlError");
        }
        
        // Verificar si hubo error en cURL
        if ($curlError) {
            echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $curlError]);
            exit;
        }
        
        // Verificar el código de respuesta HTTP
        if ($httpCode === 200 || $httpCode === 201) {
            echo json_encode(['success' => true, 'message' => 'Instancia desconectada correctamente']);
        } else {
            // Intentar obtener más información del error
            $errorMessage = 'Error HTTP ' . $httpCode;
            if ($response) {
                $errorData = json_decode($response, true);
                if (is_array($errorData) && isset($errorData['message'])) {
                    $errorMessage = $errorData['message'];
                }
            }
            echo json_encode(['success' => false, 'message' => $errorMessage]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Para conectar la instancia, usa la interfaz de Evolution API']);
    }
}
// Manejar la acción de conectar instancia de Evolution API
elseif ($action === 'connect_evolution_instance') {
    // Verificar que tengamos la configuración necesaria
    if (empty($evolutionApiUrl) || empty($evolutionApiKey) || empty($instanceToken)) {
        echo json_encode(['success' => false, 'message' => 'Configuración de Evolution API incompleta o datos faltantes']);
        exit;
    }
    
    // Obtener el nombre de la instancia desde la base de datos
    $evolutionInstanceName = '';
    $sql = "SELECT setting_value FROM settings WHERE setting_key = 'evolution_instance_name' LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $evolutionInstanceName = $row['setting_value'];
    }
    
    if (empty($evolutionInstanceName)) {
        echo json_encode(['success' => false, 'message' => 'Nombre de instancia no encontrado en la configuración']);
        exit;
    }
    
    $apiUrl = rtrim($evolutionApiUrl, '/') . '/instance/connect/' . rawurlencode($evolutionInstanceName);
    $headers = [
        'accept: application/json',
        'apikey: ' . $evolutionApiKey
    ];
    
    // LOG de depuración
    error_log("[EVOLUTION CONNECT] Instance Name from DB: $evolutionInstanceName");
    error_log("[EVOLUTION CONNECT] URL: $apiUrl");
    error_log("[EVOLUTION CONNECT] Headers: " . json_encode($headers));
    
    // Configurar cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    // Ejecutar la petición
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // LOG de depuración
    error_log("[EVOLUTION CONNECT] HTTP Code: $httpCode");
    error_log("[EVOLUTION CONNECT] Response: $response");
    if ($curlError) {
        error_log("[EVOLUTION CONNECT] cURL Error: $curlError");
    }
    
    // Verificar si hubo error en cURL
    if ($curlError) {
        echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $curlError]);
        exit;
    }
    
    // Verificar el código de respuesta HTTP
    if ($httpCode === 200 && $response) {
        $responseData = json_decode($response, true);
        if (is_array($responseData) && isset($responseData['base64'])) {
            echo json_encode([
                'success' => true, 
                'message' => 'QR generado correctamente',
                'qr_code' => $responseData['base64'],
                'pairing_code' => $responseData['pairingCode'] ?? null,
                'code' => $responseData['code'] ?? null
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Respuesta inválida de la API']);
        }
    } else {
        // Intentar obtener más información del error
        $errorMessage = 'Error HTTP ' . $httpCode;
        if ($response) {
            $errorData = json_decode($response, true);
            if (is_array($errorData) && isset($errorData['message'])) {
                $errorMessage = $errorData['message'];
            }
        }
        echo json_encode(['success' => false, 'message' => $errorMessage]);
    }
}
// Guardar configuración de notificaciones
elseif ($action === 'save_notifications_settings') {
    if (empty($notificationsSendTime)) {
        echo json_encode(['success' => false, 'message' => 'Hora inválida']);
        exit;
    }
    $ok = true;
    // Guardar hora
    $sql = "INSERT INTO settings (setting_key, setting_value) VALUES ('notifications_send_time', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $notificationsSendTime);
    $ok = $ok && mysqli_stmt_execute($stmt);

    // Guardar toggle (1/0)
    $enabled = ($notificationsDailyEnabled === '1') ? '1' : '0';
    $sql2 = "INSERT INTO settings (setting_key, setting_value) VALUES ('notifications_daily_appointments_enabled', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
    $stmt2 = mysqli_prepare($conn, $sql2);
    mysqli_stmt_bind_param($stmt2, 's', $enabled);
    $ok = $ok && mysqli_stmt_execute($stmt2);

    if ($ok) {
        echo json_encode(['success' => true, 'message' => 'Configuración guardada']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar']);
    }
}
// Ejecutar envío inmediato de recordatorio del día
elseif ($action === 'trigger_notifications_now') {
    // Enviar en línea las citas del día al webhook configurado
    // 1) Settings
    $settings = [];
    $res = mysqli_query($conn, "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('timezone','notifications_webhook_url')");
    while ($row = mysqli_fetch_assoc($res)) { $settings[$row['setting_key']] = $row['setting_value']; }
    $tz = $settings['timezone'] ?? 'America/Bogota';
    $webhook = $settings['notifications_webhook_url'] ?? 'https://n8n.mangomorado.com/webhook/notificaciones_mundoanimal';

    date_default_timezone_set($tz);
    $start = (new DateTime('today'))->format('Y-m-d 00:00:00');
    $end = (new DateTime('today 23:59:59'))->format('Y-m-d H:i:s');

    // 2) Citas de hoy
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
    while ($row = mysqli_fetch_assoc($result)) { $appointments[] = $row; }

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
        echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $curlError]);
        exit;
    }
    $ok = ($httpCode >= 200 && $httpCode < 300);
    echo json_encode([
        'success' => $ok,
        'message' => $ok ? ('Notificación enviada (HTTP ' . $httpCode . ')') : ('Error HTTP ' . $httpCode),
        'response' => $response,
        'count' => count($appointments)
    ]);
}
// Probar webhook de notificaciones con un payload de ejemplo
elseif ($action === 'test_notifications_webhook') {
    // Obtener URL desde settings
    $sql = "SELECT setting_value FROM settings WHERE setting_key = 'notifications_webhook_url' LIMIT 1";
    $res = mysqli_query($conn, $sql);
    $webhook = 'https://n8n.mangomorado.com/webhook/notificaciones_mundoanimal';
    if ($res && $row = mysqli_fetch_assoc($res)) {
        if (!empty($row['setting_value'])) $webhook = $row['setting_value'];
    }

    // Construir payload de prueba
    $tz = date_default_timezone_get();
    $payload = [
        'test' => true,
        'timestamp' => date('c'),
        'timezone' => $tz,
        'message' => 'Prueba de webhook desde Configuración',
        'sample' => [ 'count' => 0, 'appointments' => [] ]
    ];

    // Enviar POST JSON
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $webhook);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $curlError]);
        exit;
    }

    $ok = ($httpCode >= 200 && $httpCode < 300);
    echo json_encode([
        'success' => $ok,
        'message' => $ok ? 'Webhook respondió HTTP ' . $httpCode : 'Error HTTP ' . $httpCode,
        'response' => $response
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
}
?> 