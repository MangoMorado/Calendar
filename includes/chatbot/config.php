<?php
// Obtener configuración de n8n desde la base de datos
$n8nConfig = [];
$sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('n8n_url', 'n8n_api_key', 'selected_workflow', 'selected_notifications_workflow', 'evolution_api_url', 'evolution_api_key', 'selected_evolution_instance', 'evolution_instance_name')";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $n8nConfig[$row['setting_key']] = $row['setting_value'];
}

$n8nUrl = $n8nConfig['n8n_url'] ?? '';
$n8nApiKey = $n8nConfig['n8n_api_key'] ?? '';
$selectedWorkflow = $n8nConfig['selected_workflow'] ?? '';
$selectedNotificationsWorkflow = $n8nConfig['selected_notifications_workflow'] ?? '';
$evolutionApiUrl = $n8nConfig['evolution_api_url'] ?? '';
$evolutionApiKey = $n8nConfig['evolution_api_key'] ?? '';
$selectedEvolutionInstance = $n8nConfig['selected_evolution_instance'] ?? '';
$evolutionInstanceName = $n8nConfig['evolution_instance_name'] ?? '';

// Extraer IDs de workflows seleccionados
$workflowId = '';
if (!empty($selectedWorkflow) && strpos($selectedWorkflow, '|') !== false) {
    $workflowId = explode('|', $selectedWorkflow)[0];
}
$notificationsWorkflowId = '';
if (!empty($selectedNotificationsWorkflow) && strpos($selectedNotificationsWorkflow, '|') !== false) {
    $notificationsWorkflowId = explode('|', $selectedNotificationsWorkflow)[0];
}

// Extraer el token de la instancia seleccionada
$evolutionInstanceToken = '';
if (!empty($selectedEvolutionInstance) && strpos($selectedEvolutionInstance, '|') !== false) {
    $parts = explode('|', $selectedEvolutionInstance);
    if (count($parts) >= 2) {
        $evolutionInstanceToken = $parts[1]; // El token está en la segunda parte
    }
}

// Verificar estado del workflow principal
$workflowStatus = 'error'; // Por defecto: problema con API
$workflowName = 'No configurado';

if (!empty($n8nUrl) && !empty($n8nApiKey) && !empty($workflowId)) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, rtrim($n8nUrl, '/') . '/api/v1/workflows/' . $workflowId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: application/json',
        'X-N8N-API-KEY: ' . $n8nApiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $workflowData = json_decode($response, true);
        if (is_array($workflowData)) {
            $workflowName = $workflowData['name'] ?? 'Nombre no disponible';
            $workflowStatus = $workflowData['active'] ? 'active' : 'inactive';
        }
    }
}

// Verificar estado del workflow de notificaciones
$notificationsWorkflowStatus = 'error';
$notificationsWorkflowName = 'No configurado';
if (!empty($n8nUrl) && !empty($n8nApiKey) && !empty($notificationsWorkflowId)) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, rtrim($n8nUrl, '/') . '/api/v1/workflows/' . $notificationsWorkflowId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: application/json',
        'X-N8N-API-KEY: ' . $n8nApiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode === 200 && $response) {
        $workflowData = json_decode($response, true);
        if (is_array($workflowData)) {
            $notificationsWorkflowName = $workflowData['name'] ?? 'Nombre no disponible';
            $notificationsWorkflowStatus = $workflowData['active'] ? 'active' : 'inactive';
        }
    }
}

// Verificar estado de la instancia de Evolution API
$evolutionStatus = 'error'; // Por defecto: problema con API
$evolutionConnectionStatus = 'No configurado';

if (!empty($evolutionApiUrl) && !empty($evolutionApiKey) && !empty($evolutionInstanceToken)) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, rtrim($evolutionApiUrl, '/') . '/instance/fetchInstances');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: application/json',
        'apikey: ' . $evolutionApiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $instancesData = json_decode($response, true);
        if (is_array($instancesData)) {
            foreach ($instancesData as $instance) {
                if (is_array($instance) && isset($instance['token']) && $instance['token'] === $evolutionInstanceToken) {
                    $evolutionConnectionStatus = $instance['connectionStatus'] ?? 'unknown';
                    $evolutionStatus = ($evolutionConnectionStatus === 'open') ? 'active' : 'inactive';
                    break;
                }
            }
        }
    }
}
?> 