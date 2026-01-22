<?php
/**
 * Endpoint mejorado para envío de difusiones con n8n
 * Versión con mejor manejo de errores y logging
 */

// Activar reporte de errores para debugging
ini_set('display_errors', 0); // No mostrar errores al cliente
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Función para logging
function logError($message, $data = null)
{
    $logMessage = date('Y-m-d H:i:s').' - '.$message;
    if ($data) {
        $logMessage .= ' - Data: '.json_encode($data);
    }
    error_log($logMessage);
}

// Función para respuesta JSON consistente
function sendJsonResponse($success, $message, $data = null, $httpCode = 200)
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');

    $response = [
        'success' => $success,
        'message' => $message,
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode($response);
    exit;
}

try {
    // Verificar método HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(false, 'Método no permitido', null, 405);
    }

    // Incluir archivos requeridos
    $requiredFiles = [
        __DIR__.'/../config/database.php',
        __DIR__.'/../includes/auth.php',
        __DIR__.'/../models/BroadcastHistoryModel.php',
        __DIR__.'/../models/BroadcastListModel.php',
    ];

    foreach ($requiredFiles as $file) {
        if (! file_exists($file)) {
            logError('Archivo requerido no encontrado: '.$file);
            sendJsonResponse(false, 'Error de configuración del servidor', null, 500);
        }
        require_once $file;
    }

    // Verificar autenticación
    requireAuth();
    $currentUser = getCurrentUser();

    if (! $currentUser) {
        sendJsonResponse(false, 'Usuario no autenticado', null, 401);
    }

    // Obtener y validar datos de entrada
    $listId = (int) ($_POST['list_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    $image = $_FILES['image'] ?? null;
    $selectedContacts = $_POST['selected_contacts'] ?? [];

    logError('Datos recibidos', [
        'list_id' => $listId,
        'message_length' => strlen($message),
        'has_image' => ! empty($image),
        'selected_contacts_count' => count($selectedContacts),
    ]);

    // Validar datos requeridos
    if (! $listId) {
        sendJsonResponse(false, 'ID de lista requerido');
    }

    if (empty($message) && ! $image) {
        sendJsonResponse(false, 'Mensaje o imagen requeridos');
    }

    // Inicializar modelos
    $broadcastHistoryModel = new BroadcastHistoryModel($conn);
    $broadcastListModel = new BroadcastListModel($conn);

    // Verificar permisos de acceso a la lista
    if (! $broadcastListModel->canAccessList($listId, $currentUser['id'])) {
        sendJsonResponse(false, 'No tienes permisos para usar esta lista', null, 403);
    }

    // Obtener contactos de la lista
    $contacts = $broadcastListModel->getContactsInList($listId);

    if (empty($contacts)) {
        sendJsonResponse(false, 'No hay contactos disponibles en la lista');
    }

    // Filtrar contactos seleccionados si se especificaron
    if (! empty($selectedContacts)) {
        $contacts = array_filter($contacts, function ($contact) use ($selectedContacts) {
            return in_array($contact['number'], $selectedContacts);
        });
    }

    if (empty($contacts)) {
        sendJsonResponse(false, 'No hay contactos disponibles después del filtrado');
    }

    // Obtener configuración de n8n
    $config = [];
    $sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('n8n_url', 'n8n_api_key', 'n8n_broadcast_webhook_url', 'evolution_instance_name')";
    $result = mysqli_query($conn, $sql);

    if (! $result) {
        logError('Error al consultar configuración: '.mysqli_error($conn));
        sendJsonResponse(false, 'Error al obtener configuración del sistema', null, 500);
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $config[$row['setting_key']] = $row['setting_value'];
    }

    $n8nUrl = $config['n8n_url'] ?? '';
    $n8nApiKey = $config['n8n_api_key'] ?? '';
    $n8nWebhookUrl = $config['n8n_broadcast_webhook_url'] ?? '';
    $evolutionInstanceName = $config['evolution_instance_name'] ?? '';

    if (empty($n8nUrl) || empty($n8nWebhookUrl)) {
        sendJsonResponse(false, 'Configuración de n8n incompleta. Verifica la configuración del sistema.');
    }

    // Procesar imagen si existe
    $imageData = null;
    if ($image && $image['tmp_name'] && is_uploaded_file($image['tmp_name'])) {
        try {
            $imageData = [
                'name' => $image['name'],
                'type' => $image['type'],
                'size' => $image['size'],
                'base64' => base64_encode(file_get_contents($image['tmp_name'])),
            ];

            // Determinar mimetype
            $extension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
            $mimetype = '';

            switch ($extension) {
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
                default:
                    $mimetype = $image['type'] ?: 'application/octet-stream';
                    break;
            }

            $imageData['mimetype'] = $mimetype;

        } catch (Exception $e) {
            logError('Error al procesar imagen: '.$e->getMessage());
            sendJsonResponse(false, 'Error al procesar la imagen');
        }
    }

    // Crear registro de difusión en la base de datos
    $broadcastData = [
        'list_id' => $listId,
        'message' => $message,
        'image_path' => null,
        'total_contacts' => count($contacts),
        'user_id' => $currentUser['id'],
        'status' => 'queued',
    ];

    $broadcastId = $broadcastHistoryModel->createBroadcast($broadcastData);

    if (! $broadcastId) {
        logError('Error al crear registro de difusión');
        sendJsonResponse(false, 'Error al crear el registro de difusión', null, 500);
    }

    // Crear detalles de envío para cada contacto
    foreach ($contacts as $contact) {
        $detailData = [
            'broadcast_id' => $broadcastId,
            'contact_id' => $contact['id'],
            'contact_number' => $contact['number'],
            'status' => 'queued',
            'error_message' => null,
            'sent_at' => null,
        ];
        $broadcastHistoryModel->addBroadcastDetail($detailData);
    }

    // Preparar payload para n8n
    $n8nPayload = [
        'texto' => $message,
        'imagen_base64' => $imageData ? $imageData['base64'] : null,
        'fileName' => $imageData ? $imageData['name'] : null,
        'mimetype' => $imageData ? $imageData['mimetype'] : null,
        'contactos' => array_map(function ($contact) {
            return ['numero' => $contact['number']];
        }, $contacts),
        'mediatype' => $imageData ? (
            strpos($imageData['mimetype'], 'video/') === 0 ? 'video' :
            (strpos($imageData['mimetype'], 'audio/') === 0 ? 'audio' :
            (strpos($imageData['mimetype'], 'image/') === 0 ? 'image' : 'document'))
        ) : 'text',
        'broadcast_id' => $broadcastId,
        'evolution_instance_name' => $evolutionInstanceName,
    ];

    logError('Enviando a n8n', [
        'broadcast_id' => $broadcastId,
        'webhook_url' => $n8nWebhookUrl,
        'contacts_count' => count($contacts),
    ]);

    // Enviar a n8n via webhook
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $n8nWebhookUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-N8N-API-KEY: '.$n8nApiKey,
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($n8nPayload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    logError('Respuesta de n8n', [
        'http_code' => $httpCode,
        'response' => $response,
        'curl_error' => $curlError,
    ]);

    if ($curlError) {
        $broadcastHistoryModel->updateBroadcastStatus($broadcastId, 'failed', 0, count($contacts));
        sendJsonResponse(false, 'Error de conexión con n8n: '.$curlError);
    }

    if ($httpCode === 200 || $httpCode === 201) {
        $responseData = json_decode($response, true);

        // Validar si la respuesta es JSON válido o si n8n no devolvió respuesta (común en workflows)
        $isValidJson = json_last_error() === JSON_ERROR_NONE;

        // Si n8n no devolvió JSON pero el código HTTP es exitoso, considerar como éxito
        if (! $isValidJson && ($httpCode === 200 || $httpCode === 201)) {
            logError('n8n no devolvió JSON pero HTTP fue exitoso', [
                'http_code' => $httpCode,
                'response' => $response,
                'json_error' => json_last_error_msg(),
            ]);

            // Actualizar estado a in_progress
            $broadcastHistoryModel->updateBroadcastStatus($broadcastId, 'in_progress');

            sendJsonResponse(true, 'Difusión enviada a n8n para procesamiento', [
                'broadcast_id' => $broadcastId,
                'total_contacts' => count($contacts),
                'status' => 'in_progress',
                'note' => 'n8n procesando en segundo plano',
            ]);
        } elseif ($isValidJson) {
            // Respuesta JSON válida
            $broadcastHistoryModel->updateBroadcastStatus($broadcastId, 'in_progress');

            sendJsonResponse(true, 'Difusión enviada a n8n para procesamiento', [
                'broadcast_id' => $broadcastId,
                'total_contacts' => count($contacts),
                'status' => 'in_progress',
                'n8n_response' => $responseData,
            ]);
        } else {
            // Error en la respuesta
            $broadcastHistoryModel->updateBroadcastStatus($broadcastId, 'failed', 0, count($contacts));
            sendJsonResponse(false, 'Respuesta inválida de n8n', [
                'http_code' => $httpCode,
                'response' => $response,
            ]);
        }
    } else {
        $broadcastHistoryModel->updateBroadcastStatus($broadcastId, 'failed', 0, count($contacts));

        $errorMessage = 'Error HTTP '.$httpCode;
        if ($response) {
            $errorData = json_decode($response, true);
            if (is_array($errorData) && isset($errorData['message'])) {
                $errorMessage = $errorData['message'];
            }
        }

        sendJsonResponse(false, 'Error al enviar a n8n: '.$errorMessage, [
            'http_code' => $httpCode,
            'response' => $response,
        ]);
    }

} catch (Exception $e) {
    logError('Error general en send_broadcast_n8n: '.$e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);

    sendJsonResponse(false, 'Error interno del servidor', null, 500);
}
?> 