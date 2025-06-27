<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/BroadcastHistoryModel.php';
require_once __DIR__ . '/../models/BroadcastListModel.php';

// Verificar autenticación
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

// Obtener configuración de Evolution API
$config = [];
$sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('evolution_api_url', 'evolution_api_key', 'evolution_instance_name')";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $config[$row['setting_key']] = $row['setting_value'];
}

$evolutionApiUrl = $config['evolution_api_url'] ?? '';
$evolutionApiKey = $config['evolution_api_key'] ?? '';
$evolutionInstanceName = $config['evolution_instance_name'] ?? '';

// LOG de configuración
error_log('[BULK] Evolution API URL: ' . $evolutionApiUrl);
error_log('[BULK] Evolution API KEY: ' . substr($evolutionApiKey, 0, 8) . '...');
error_log('[BULK] Evolution Instance Name: "' . $evolutionInstanceName . '"');

if (empty($evolutionApiUrl) || empty($evolutionApiKey) || empty($evolutionInstanceName)) {
    error_log('[BULK] Configuración de Evolution API incompleta');
    echo json_encode(['success' => false, 'message' => 'Configuración de Evolution API incompleta']);
    exit;
}

// Verificar estado de la instancia
$checkApiUrl = rtrim($evolutionApiUrl, '/') . '/instance/connectionState/' . rawurlencode($evolutionInstanceName);
$checkHeaders = ['apikey: ' . $evolutionApiKey];

error_log('[BULK] Verificando estado de instancia: ' . $checkApiUrl);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $checkApiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $checkHeaders);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$checkResponse = curl_exec($ch);
$checkHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

error_log('[BULK] HTTP_CODE: ' . $checkHttpCode);
error_log('[BULK] RESPONSE: ' . $checkResponse);

$instanceState = 'unknown';
if ($checkHttpCode === 200) {
    $checkData = json_decode($checkResponse, true);
    $instanceState = $checkData['state'] ?? 'unknown';
}

error_log('[BULK] Instance state: ' . $instanceState);

if ($instanceState !== 'open') {
    error_log('[BULK] Instancia no conectada. State: ' . $instanceState);
    echo json_encode([
        'success' => false, 
        'message' => 'La instancia de WhatsApp no está conectada',
        'debug_info' => ['instance_state' => $instanceState]
    ]);
    exit;
}

// Procesar imagen si existe
$imagePath = null;
if ($image && $image['tmp_name']) {
    $uploadsDir = __DIR__ . '/../uploads';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0777, true);
    }
    
    $ext = pathinfo($image['name'], PATHINFO_EXTENSION);
    $filename = 'broadcast_' . uniqid() . '.' . $ext;
    $imagePath = $uploadsDir . '/' . $filename;
    
    if (!move_uploaded_file($image['tmp_name'], $imagePath)) {
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar la imagen']);
        exit;
    }
}

// Crear registro de difusión en la base de datos
$broadcastData = [
    'list_id' => $listId,
    'message' => $message,
    'image_path' => $imagePath,
    'total_contacts' => count($contacts),
    'user_id' => $currentUser['id'],
    'status' => 'in_progress'
];

$broadcastId = $broadcastHistoryModel->createBroadcast($broadcastData);

if (!$broadcastId) {
    echo json_encode(['success' => false, 'message' => 'Error al crear el registro de difusión']);
    exit;
}

// Función para enviar mensaje individual
function sendMessage($number, $message, $imagePath, $evolutionApiUrl, $evolutionApiKey, $evolutionInstanceName) {
    $result = ['success' => false, 'message' => '', 'debug_info' => []];
    
    try {
        if ($imagePath && file_exists($imagePath)) {
            // Enviar imagen con mensaje
            $apiUrl = rtrim($evolutionApiUrl, '/') . '/message/sendMedia/' . rawurlencode($evolutionInstanceName);
            $headers = ['apikey: ' . $evolutionApiKey];
            
            $postfields = [
                'number' => $number,
                'file' => new CURLFile($imagePath),
                'caption' => $message,
                'mediatype' => 'image'
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                $result['message'] = 'Error de conexión: ' . $curlError;
                $result['debug_info']['curl_error'] = $curlError;
                return $result;
            }
            
            if ($httpCode === 200 || $httpCode === 201) {
                $result['success'] = true;
                $result['message'] = 'Imagen enviada correctamente';
            } else {
                $result['message'] = 'Error HTTP ' . $httpCode;
                $result['debug_info']['http_code'] = $httpCode;
                $result['debug_info']['response'] = $response;
            }
        } else {
            // Enviar mensaje de texto
            $apiUrl = rtrim($evolutionApiUrl, '/') . '/message/sendText/' . rawurlencode($evolutionInstanceName);
            $headers = [
                'Content-Type: application/json',
                'apikey: ' . $evolutionApiKey
            ];
            
            $payload = [
                'number' => $number,
                'text' => $message
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                $result['message'] = 'Error de conexión: ' . $curlError;
                $result['debug_info']['curl_error'] = $curlError;
                return $result;
            }
            
            if ($httpCode === 200 || $httpCode === 201) {
                $result['success'] = true;
                $result['message'] = 'Mensaje enviado correctamente';
            } else {
                $result['message'] = 'Error HTTP ' . $httpCode;
                $result['debug_info']['http_code'] = $httpCode;
                $result['debug_info']['response'] = $response;
            }
        }
    } catch (Exception $e) {
        $result['message'] = 'Excepción: ' . $e->getMessage();
        $result['debug_info']['exception'] = $e->getMessage();
    }
    
    return $result;
}

// Iniciar envío de difusión
$totalContacts = count($contacts);
$sentSuccessfully = 0;
$sentFailed = 0;
$errors = [];

foreach ($contacts as $contact) {
    $number = $contact['number'];
    $contactId = $contact['id'];
    
    // Crear detalle de envío
    $detailData = [
        'broadcast_id' => $broadcastId,
        'contact_id' => $contactId,
        'contact_number' => $number,
        'status' => 'pending',
        'error_message' => null,
        'sent_at' => null
    ];
    
    $detailId = $broadcastHistoryModel->addBroadcastDetail($detailData);
    
    // Enviar mensaje
    $sendResult = sendMessage($number, $message, $imagePath, $evolutionApiUrl, $evolutionApiKey, $evolutionInstanceName);
    
    $status = $sendResult['success'] ? 'sent' : 'failed';
    $errorMessage = $sendResult['success'] ? null : $sendResult['message'];
    $sentAt = $sendResult['success'] ? date('Y-m-d H:i:s') : null;
    
    // Actualizar detalle
    if ($detailId) {
        $broadcastHistoryModel->updateBroadcastDetail($detailId, $status, $errorMessage, $sentAt);
    }
    
    // Contar resultados
    if ($sendResult['success']) {
        $sentSuccessfully++;
    } else {
        $sentFailed++;
        $errors[] = [
            'number' => $number,
            'error' => $sendResult['message'],
            'debug' => $sendResult['debug_info'] ?? []
        ];
    }
    
    // Pausa entre envíos para evitar rate limiting
    usleep(500000); // 0.5 segundos
}

// Actualizar estado final de la difusión
$finalStatus = ($sentFailed === 0) ? 'completed' : (($sentSuccessfully === 0) ? 'failed' : 'completed');
$broadcastHistoryModel->updateBroadcastStatus($broadcastId, $finalStatus, $sentSuccessfully, $sentFailed);

// Limpiar imagen temporal si existe
if ($imagePath && file_exists($imagePath)) {
    unlink($imagePath);
}

// Preparar respuesta
$response = [
    'success' => true,
    'message' => 'Difusión procesada',
    'data' => [
        'broadcast_id' => $broadcastId,
        'total_contacts' => $totalContacts,
        'sent_successfully' => $sentSuccessfully,
        'sent_failed' => $sentFailed,
        'status' => $finalStatus
    ]
];

// Agregar errores si existen
if (!empty($errors)) {
    $response['data']['errors'] = array_slice($errors, 0, 10); // Solo primeros 10 errores
    if (count($errors) > 10) {
        $response['data']['errors_note'] = 'Mostrando solo los primeros 10 errores de ' . count($errors) . ' totales';
    }
}

echo json_encode($response);
?> 