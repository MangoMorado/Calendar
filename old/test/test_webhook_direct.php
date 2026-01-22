<?php
/**
 * Script para probar directamente el webhook de n8n
 * Ejecutar desde el navegador: test/test_webhook_direct.php
 */

// Activar reporte de errores
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

echo '<h1>Prueba Directa del Webhook de n8n</h1>';

// Función para hacer petición POST
function makeWebhookRequest($url, $data)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 60 segundos para dar tiempo al procesamiento
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    return [
        'response' => $response,
        'http_code' => $httpCode,
        'curl_error' => $curlError,
        'info' => $info,
    ];
}

// Obtener configuración de n8n
try {
    require_once '../config/database.php';

    $sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key = 'n8n_broadcast_webhook_url'";
    $result = mysqli_query($conn, $sql);
    $config = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $config[$row['setting_key']] = $row['setting_value'];
    }

    $webhookUrl = $config['n8n_broadcast_webhook_url'] ?? '';

    if (empty($webhookUrl)) {
        echo '❌ <strong>Error:</strong> No se encontró la URL del webhook de n8n<br>';
        echo 'Configura la URL del webhook en el panel de administración<br><br>';
        exit;
    }

    echo '<h2>Configuración</h2>';
    echo '<strong>Webhook URL:</strong> '.htmlspecialchars($webhookUrl).'<br><br>';

} catch (Exception $e) {
    echo '❌ Error al obtener configuración: '.$e->getMessage().'<br>';
    exit;
}

// Procesar formulario de prueba
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testType = $_POST['test_type'] ?? 'text';
    $testMessage = $_POST['test_message'] ?? 'Prueba de webhook';
    $testContacts = $_POST['test_contacts'] ?? '573001234567';

    echo '<h2>Resultado de la Prueba</h2>';

    // Preparar datos de prueba según el tipo
    $testData = [
        'texto' => $testMessage,
        'broadcast_id' => 'test_'.time(),
        'contactos' => [
            ['numero' => $testContacts],
        ],
        'mediatype' => $testType,
    ];

    // Agregar datos específicos según el tipo
    if ($testType === 'image') {
        $testData['imagen_base64'] = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $testData['fileName'] = 'test_image.png';
        $testData['mimetype'] = 'image/png';
    } elseif ($testType === 'audio') {
        $testData['imagen_base64'] = 'data:audio/mp3;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmGgU7k9n1unEiBC13yO/eizEIHWq+8+OWT';
        $testData['fileName'] = 'test_audio.mp3';
        $testData['mimetype'] = 'audio/mp3';
    }

    echo '<strong>Tipo de prueba:</strong> '.ucfirst($testType).'<br>';
    echo '<strong>Mensaje:</strong> '.htmlspecialchars($testMessage).'<br>';
    echo '<strong>Contacto:</strong> '.htmlspecialchars($testContacts).'<br>';
    echo '<strong>Datos enviados:</strong><br>';
    echo '<pre>'.json_encode($testData, JSON_PRETTY_PRINT).'</pre><br>';

    // Hacer la petición
    $result = makeWebhookRequest($webhookUrl, $testData);

    echo '<h3>Respuesta del Webhook</h3>';
    echo '<strong>HTTP Code:</strong> '.$result['http_code'].'<br>';
    echo '<strong>cURL Error:</strong> '.($result['curl_error'] ?: 'Ninguno').'<br>';
    echo '<strong>Tiempo de respuesta:</strong> '.round($result['info']['total_time'], 2).' segundos<br>';
    echo '<strong>Tamaño de respuesta:</strong> '.strlen($result['response']).' bytes<br><br>';

    echo '<strong>Respuesta completa:</strong><br>';
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto;'>";
    echo htmlspecialchars($result['response']);
    echo '</pre><br>';

    // Análisis de la respuesta
    echo '<h3>Análisis de la Respuesta</h3>';

    if (empty($result['response'])) {
        echo '❌ <strong>Respuesta vacía</strong><br>';
        echo 'El webhook no está devolviendo ninguna respuesta.<br>';
        echo "<strong>Posible causa:</strong> El workflow no tiene un nodo 'Respond to Webhook'<br>";
    } else {
        // Verificar si es JSON válido
        $jsonData = json_decode($result['response'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo '✅ <strong>JSON válido</strong><br>';
            echo 'Estructura de respuesta:<br>';
            echo '<pre>'.json_encode($jsonData, JSON_PRETTY_PRINT).'</pre><br>';

            if (isset($jsonData['success'])) {
                if ($jsonData['success']) {
                    echo '✅ <strong>Respuesta exitosa</strong><br>';
                } else {
                    echo '❌ <strong>Respuesta con error</strong><br>';
                    echo 'Error: '.($jsonData['message'] ?? 'No especificado').'<br>';
                }
            }
        } else {
            echo '❌ <strong>JSON inválido</strong><br>';
            echo 'Error: '.json_last_error_msg().'<br>';
            echo 'Posible causa: El workflow está devolviendo texto en lugar de JSON<br>';
        }
    }

    echo '<hr>';
}

// Formulario de prueba
echo '<h2>Realizar Prueba</h2>';
echo "<form method='post' style='max-width: 600px;'>";
echo "<div style='margin-bottom: 15px;'>";
echo "<label for='test_type'><strong>Tipo de mensaje:</strong></label><br>";
echo "<select id='test_type' name='test_type' style='width: 100%; padding: 8px;'>";
echo "<option value='text'>Texto</option>";
echo "<option value='image'>Imagen</option>";
echo "<option value='audio'>Audio</option>";
echo '</select>';
echo '</div>';

echo "<div style='margin-bottom: 15px;'>";
echo "<label for='test_message'><strong>Mensaje:</strong></label><br>";
echo "<textarea id='test_message' name='test_message' style='width: 100%; padding: 8px; height: 80px;'>Prueba de webhook desde sistema</textarea>";
echo '</div>';

echo "<div style='margin-bottom: 15px;'>";
echo "<label for='test_contacts'><strong>Número de contacto (formato: 573001234567):</strong></label><br>";
echo "<input type='text' id='test_contacts' name='test_contacts' value='573001234567' style='width: 100%; padding: 8px;'>";
echo '</div>';

echo "<button type='submit' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'>Probar Webhook</button>";
echo '</form>';

echo '<br><hr>';
echo '<p><strong>Notas:</strong></p>';
echo '<ul>';
echo '<li>Esta prueba envía datos directamente al webhook de n8n</li>';
echo "<li>Si la respuesta está vacía, el workflow necesita un nodo 'Respond to Webhook'</li>";
echo '<li>El tiempo de respuesta puede ser alto debido a los delays en el workflow</li>';
echo '<li>Verifica los logs de n8n para ver el procesamiento completo</li>';
echo '</ul>';
?> 