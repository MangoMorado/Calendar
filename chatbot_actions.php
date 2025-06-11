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
        $apiUrl = rtrim($evolutionApiUrl, '/') . '/instance/logout/' . $evolutionInstanceName;
        
        // Configurar cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'apikey: ' . $evolutionApiKey
        ]);
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
    
    $apiUrl = rtrim($evolutionApiUrl, '/') . '/instance/connect/' . $evolutionInstanceName;
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
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
}
?> 