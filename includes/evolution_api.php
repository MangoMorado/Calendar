<?php
function sendEvolutionText($conn, $number, $text) {
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

    if (empty($evolutionApiUrl) || empty($evolutionApiKey) || empty($evolutionInstanceName)) {
        return ['success' => false, 'message' => 'Configuración de Evolution API incompleta'];
    }

    // Validar estado de la instancia
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
    $instanceState = 'unknown';
    if ($checkHttpCode === 200) {
        $checkData = json_decode($checkResponse, true);
        if (isset($checkData['instance']['state'])) {
            $instanceState = $checkData['instance']['state'];
        }
    }
    if (strtolower($instanceState) !== 'open') {
        return ['success' => false, 'message' => 'La instancia de Evolution API no está conectada'];
    }

    // Enviar mensaje
    $apiUrl = rtrim($evolutionApiUrl, '/') . '/message/sendText/' . rawurlencode($evolutionInstanceName);
    $headers = [
        'Content-Type: application/json',
        'apikey: ' . $evolutionApiKey
    ];
    $payload = [
        'number' => $number,
        'text' => $text,
        'textMessage' => [
            'text' => $text
        ]
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
    curl_close($ch);

    $evolutionResponse = json_decode($response, true);

    if ($httpCode === 201 && isset($evolutionResponse['status']) && strtoupper($evolutionResponse['status']) === 'PENDING') {
        return [
            'success' => true,
            'message' => 'Mensaje enviado correctamente',
            'evolution_response' => $evolutionResponse
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Error al enviar el mensaje',
            'evolution_response' => $evolutionResponse
        ];
    }
}

function sendEvolutionMedia($conn, $number, $text, $imagePath) {
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

    if (empty($evolutionApiUrl) || empty($evolutionApiKey) || empty($evolutionInstanceName)) {
        return ['success' => false, 'message' => 'Configuración de Evolution API incompleta'];
    }

    // Validar estado de la instancia
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
    $instanceState = 'unknown';
    if ($checkHttpCode === 200) {
        $checkData = json_decode($checkResponse, true);
        if (isset($checkData['instance']['state'])) {
            $instanceState = $checkData['instance']['state'];
        }
    }
    if (strtolower($instanceState) !== 'open') {
        return ['success' => false, 'message' => 'La instancia de Evolution API no está conectada'];
    }

    // Verificar que la imagen existe
    if (!file_exists($imagePath)) {
        return ['success' => false, 'message' => 'La imagen no existe en el servidor'];
    }

    // Obtener información del archivo
    $fileInfo = pathinfo($imagePath);
    $fileExtension = strtolower($fileInfo['extension']);
    
    // Determinar el tipo de medio
    $mediaType = 'image';
    if (in_array($fileExtension, ['mp4', 'avi', 'mov'])) {
        $mediaType = 'video';
    } elseif (in_array($fileExtension, ['mp3', 'wav', 'ogg'])) {
        $mediaType = 'audio';
    } elseif (in_array($fileExtension, ['pdf', 'doc', 'docx'])) {
        $mediaType = 'document';
    }

    // Enviar medio usando CURLFile (mismo método que test_img_send.php)
    $apiUrl = rtrim($evolutionApiUrl, '/') . '/message/sendMedia/' . rawurlencode($evolutionInstanceName);
    $headers = [
        'apikey: ' . $evolutionApiKey
    ];
    
    $postfields = [
        'number' => $number,
        'file' => new CURLFile($imagePath),
        'caption' => $text,
        'mediatype' => $mediaType
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

    $evolutionResponse = json_decode($response, true);

    if (($httpCode === 200 || $httpCode === 201) && isset($evolutionResponse['status']) && strtoupper($evolutionResponse['status']) === 'PENDING') {
        return [
            'success' => true,
            'message' => 'Mensaje con imagen enviado correctamente',
            'evolution_response' => $evolutionResponse
        ];
    } else {
        $errorMsg = 'Error al enviar el mensaje con imagen';
        if ($curlError) {
            $errorMsg .= ' (CURL: ' . $curlError . ')';
        } else {
            $errorMsg .= ' (HTTP: ' . $httpCode . ')';
            if ($evolutionResponse && isset($evolutionResponse['message'])) {
                $errorMsg .= ' - ' . $evolutionResponse['message'];
            }
        }
        return [
            'success' => false,
            'message' => $errorMsg,
            'evolution_response' => $evolutionResponse
        ];
    }
} 