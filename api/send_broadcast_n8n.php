<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/BroadcastHistoryModel.php';
require_once __DIR__ . '/../models/BroadcastListModel.php';

requireAuth();
header('Content-Type: application/json; charset=utf-8');

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos de la petición
$listId = (int)($_POST['list_id'] ?? 0);
$message = trim($_POST['message'] ?? '');
$image = $_FILES['image'] ?? null;
$selectedContacts = $_POST['selected_contacts'] ?? [];

// Validar datos requeridos
if (!$listId || (empty($message) && !$image)) {
    echo json_encode(['success' => false, 'message' => 'Lista, mensaje o imagen son requeridos']);
    exit;
}

// Obtener información del usuario actual
$currentUser = getCurrentUser();

// Inicializar modelos
$broadcastHistoryModel = new BroadcastHistoryModel($conn);
$broadcastListModel = new BroadcastListModel($conn);

// Verificar permisos de acceso a la lista
if (!$broadcastListModel->canAccessList($listId, $currentUser['id'])) {
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para usar esta lista']);
    exit;
}

// Obtener contactos de la lista
$contacts = $broadcastListModel->getContactsInList($listId);

// Filtrar contactos seleccionados si se especificaron
if (!empty($selectedContacts)) {
    $contacts = array_filter($contacts, function($contact) use ($selectedContacts) {
        return in_array($contact['number'], $selectedContacts);
    });
}

if (empty($contacts)) {
    echo json_encode(['success' => false, 'message' => 'No hay contactos disponibles en la lista']);
    exit;
}

// Obtener configuración de n8n
$config = [];
$sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('n8n_url', 'n8n_api_key', 'n8n_broadcast_webhook_url')";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $config[$row['setting_key']] = $row['setting_value'];
}

$n8nUrl = $config['n8n_url'] ?? '';
$n8nApiKey = $config['n8n_api_key'] ?? '';
$n8nWebhookUrl = $config['n8n_broadcast_webhook_url'] ?? '';

if (empty($n8nUrl) || empty($n8nWebhookUrl)) {
    echo json_encode(['success' => false, 'message' => 'Configuración de n8n incompleta']);
    exit;
}

// Procesar imagen si existe
$imageData = null;
if ($image && $image['tmp_name']) {
    $imageData = [
        'name' => $image['name'],
        'type' => $image['type'],
        'size' => $image['size'],
        'base64' => base64_encode(file_get_contents($image['tmp_name']))
    ];
    
    // Determinar el mimetype basado en la extensión del archivo
    $extension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
    $mimetype = '';
    
    switch ($extension) {
        // Imágenes
        case 'jpg':
        case 'jpeg':
            $mimetype = 'image/jpeg';
            break;
        case 'png':
            $mimetype = 'image/png';
            break;
        case 'gif':
            $mimetype = 'image/gif';
            break;
        case 'webp':
            $mimetype = 'image/webp';
            break;
        case 'bmp':
            $mimetype = 'image/bmp';
            break;
        case 'svg':
            $mimetype = 'image/svg+xml';
            break;
        case 'ico':
            $mimetype = 'image/x-icon';
            break;
        case 'tiff':
        case 'tif':
            $mimetype = 'image/tiff';
            break;
            
        // Videos
        case 'mp4':
            $mimetype = 'video/mp4';
            break;
        case 'avi':
            $mimetype = 'video/x-msvideo';
            break;
        case 'mov':
            $mimetype = 'video/quicktime';
            break;
        case 'wmv':
            $mimetype = 'video/x-ms-wmv';
            break;
        case 'flv':
            $mimetype = 'video/x-flv';
            break;
        case 'webm':
            $mimetype = 'video/webm';
            break;
        case 'mkv':
            $mimetype = 'video/x-matroska';
            break;
        case '3gp':
            $mimetype = 'video/3gpp';
            break;
        case 'm4v':
            $mimetype = 'video/x-m4v';
            break;
            
        // Audio
        case 'mp3':
            $mimetype = 'audio/mpeg';
            break;
        case 'wav':
            $mimetype = 'audio/wav';
            break;
        case 'ogg':
            $mimetype = 'audio/ogg';
            break;
        case 'aac':
            $mimetype = 'audio/aac';
            break;
        case 'wma':
            $mimetype = 'audio/x-ms-wma';
            break;
        case 'flac':
            $mimetype = 'audio/flac';
            break;
        case 'm4a':
            $mimetype = 'audio/mp4';
            break;
            
        // Documentos
        case 'pdf':
            $mimetype = 'application/pdf';
            break;
        case 'doc':
            $mimetype = 'application/msword';
            break;
        case 'docx':
            $mimetype = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            break;
        case 'xls':
            $mimetype = 'application/vnd.ms-excel';
            break;
        case 'xlsx':
            $mimetype = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            break;
        case 'ppt':
            $mimetype = 'application/vnd.ms-powerpoint';
            break;
        case 'pptx':
            $mimetype = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
            break;
        case 'txt':
            $mimetype = 'text/plain';
            break;
        case 'rtf':
            $mimetype = 'application/rtf';
            break;
        case 'csv':
            $mimetype = 'text/csv';
            break;
        case 'json':
            $mimetype = 'application/json';
            break;
        case 'xml':
            $mimetype = 'application/xml';
            break;
        case 'html':
        case 'htm':
            $mimetype = 'text/html';
            break;
        case 'css':
            $mimetype = 'text/css';
            break;
        case 'js':
            $mimetype = 'application/javascript';
            break;
            
        // Comprimidos
        case 'zip':
            $mimetype = 'application/zip';
            break;
        case 'rar':
            $mimetype = 'application/vnd.rar';
            break;
        case '7z':
            $mimetype = 'application/x-7z-compressed';
            break;
        case 'tar':
            $mimetype = 'application/x-tar';
            break;
        case 'gz':
            $mimetype = 'application/gzip';
            break;
            
        // Otros
        case 'exe':
            $mimetype = 'application/x-msdownload';
            break;
        case 'apk':
            $mimetype = 'application/vnd.android.package-archive';
            break;
        case 'ipa':
            $mimetype = 'application/octet-stream';
            break;
        case 'deb':
            $mimetype = 'application/vnd.debian.binary-package';
            break;
        case 'rpm':
            $mimetype = 'application/x-rpm';
            break;
            
        default:
            // Si no se reconoce la extensión, usar el tipo detectado por PHP
            $mimetype = $image['type'] ?: 'application/octet-stream';
            break;
    }
    
    $imageData['mimetype'] = $mimetype;
}

// Crear registro de difusión en la base de datos
$broadcastData = [
    'list_id' => $listId,
    'message' => $message,
    'image_path' => null,
    'total_contacts' => count($contacts),
    'user_id' => $currentUser['id'],
    'status' => 'queued'
];

$broadcastId = $broadcastHistoryModel->createBroadcast($broadcastData);

if (!$broadcastId) {
    echo json_encode(['success' => false, 'message' => 'Error al crear el registro de difusión']);
    exit;
}

// Crear detalles de envío para cada contacto
foreach ($contacts as $contact) {
    $detailData = [
        'broadcast_id' => $broadcastId,
        'contact_id' => $contact['id'],
        'contact_number' => $contact['number'],
        'status' => 'queued',
        'error_message' => null,
        'sent_at' => null
    ];
    $broadcastHistoryModel->addBroadcastDetail($detailData);
}

// Preparar payload para n8n
$n8nPayload = [
    'texto' => $message,
    'imagen_base64' => $imageData ? $imageData['base64'] : null,
    'fileName' => $imageData ? $imageData['name'] : null,
    'mimetype' => $imageData ? $imageData['mimetype'] : null,
    'contactos' => array_map(function($contact) {
        return ['numero' => $contact['number']];
    }, $contacts),
    'mediatype' => $imageData ? (
        strpos($imageData['mimetype'], 'video/') === 0 ? 'video' : 
        (strpos($imageData['mimetype'], 'audio/') === 0 ? 'audio' : 
        (strpos($imageData['mimetype'], 'image/') === 0 ? 'image' : 'document'))
    ) : 'text',
    'broadcast_id' => $broadcastId
];

// Enviar a n8n via webhook
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $n8nWebhookUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-N8N-API-KEY: ' . $n8nApiKey
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($n8nPayload));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Log para debugging
error_log('[N8N BROADCAST] Enviando difusión ID: ' . $broadcastId);
error_log('[N8N BROADCAST] Webhook URL: ' . $n8nWebhookUrl);
error_log('[N8N BROADCAST] HTTP Code: ' . $httpCode);
error_log('[N8N BROADCAST] Response: ' . $response);

if ($curlError) {
    error_log('[N8N BROADCAST] cURL Error: ' . $curlError);
    $broadcastHistoryModel->updateBroadcastStatus($broadcastId, 'failed', 0, count($contacts));
    echo json_encode(['success' => false, 'message' => 'Error de conexión con n8n: ' . $curlError]);
    exit;
}

if ($httpCode === 200 || $httpCode === 201) {
    $responseData = json_decode($response, true);
    
    // Actualizar estado a in_progress
    $broadcastHistoryModel->updateBroadcastStatus($broadcastId, 'in_progress');
    
    echo json_encode([
        'success' => true,
        'message' => 'Difusión enviada a n8n para procesamiento',
        'data' => [
            'broadcast_id' => $broadcastId,
            'total_contacts' => count($contacts),
            'status' => 'in_progress',
            'n8n_response' => $responseData
        ]
    ]);
} else {
    $broadcastHistoryModel->updateBroadcastStatus($broadcastId, 'failed', 0, count($contacts));
    
    $errorMessage = 'Error HTTP ' . $httpCode;
    if ($response) {
        $errorData = json_decode($response, true);
        if (is_array($errorData) && isset($errorData['message'])) {
            $errorMessage = $errorData['message'];
        }
    }
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error al enviar a n8n: ' . $errorMessage,
        'debug_info' => [
            'http_code' => $httpCode,
            'response' => $response
        ]
    ]);
}
?> 