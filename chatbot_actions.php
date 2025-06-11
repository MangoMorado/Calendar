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

// Verificar que los datos necesarios estén presentes
if (empty($action) || empty($workflowId) || empty($newStatus)) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

// Obtener configuración de n8n desde la base de datos
$n8nConfig = [];
$sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('n8n_url', 'n8n_api_key')";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $n8nConfig[$row['setting_key']] = $row['setting_value'];
}

$n8nUrl = $n8nConfig['n8n_url'] ?? '';
$n8nApiKey = $n8nConfig['n8n_api_key'] ?? '';

// Verificar que tengamos la configuración necesaria
if (empty($n8nUrl) || empty($n8nApiKey)) {
    echo json_encode(['success' => false, 'message' => 'Configuración de n8n incompleta']);
    exit;
}

// Manejar la acción de toggle del workflow
if ($action === 'toggle_workflow') {
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
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
}
?> 