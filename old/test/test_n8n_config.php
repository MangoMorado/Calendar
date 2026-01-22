<?php
/**
 * Script para verificar la configuración de n8n
 * Ejecutar desde el navegador: test/test_n8n_config.php
 */

// Activar reporte de errores
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

echo '<h1>Verificación de Configuración n8n</h1>';

try {
    require_once '../config/database.php';
    echo '✅ Conexión a base de datos: OK<br><br>';

    // Obtener configuración de n8n
    $sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('n8n_url', 'n8n_api_key', 'n8n_broadcast_webhook_url')";
    $result = mysqli_query($conn, $sql);

    if (! $result) {
        echo '❌ Error al consultar configuración: '.mysqli_error($conn).'<br>';
        exit;
    }

    $config = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $config[$row['setting_key']] = $row['setting_value'];
    }

    echo '<h2>Configuración Actual</h2>';
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo '<tr><th>Configuración</th><th>Valor</th><th>Estado</th></tr>';

    $n8nUrl = $config['n8n_url'] ?? '';
    $n8nApiKey = $config['n8n_api_key'] ?? '';
    $n8nWebhookUrl = $config['n8n_broadcast_webhook_url'] ?? '';

    echo '<tr><td>n8n_url</td><td>'.htmlspecialchars($n8nUrl).'</td><td>'.(! empty($n8nUrl) ? '✅' : '❌').'</td></tr>';
    echo '<tr><td>n8n_api_key</td><td>'.(! empty($n8nApiKey) ? 'Configurado' : 'No configurado').'</td><td>'.(! empty($n8nApiKey) ? '✅' : '❌').'</td></tr>';
    echo '<tr><td>n8n_broadcast_webhook_url</td><td>'.htmlspecialchars($n8nWebhookUrl).'</td><td>'.(! empty($n8nWebhookUrl) ? '✅' : '❌').'</td></tr>';
    echo '</table><br>';

    if (empty($n8nUrl) || empty($n8nApiKey) || empty($n8nWebhookUrl)) {
        echo '❌ <strong>Configuración incompleta. Completa la configuración en el panel de administración.</strong><br><br>';
        exit;
    }

    // Verificar conectividad con n8n
    echo '<h2>Verificación de Conectividad</h2>';

    // 1. Verificar que n8n esté respondiendo
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, rtrim($n8nUrl, '/').'/api/v1/health');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    echo '<strong>1. Verificación de salud de n8n:</strong><br>';
    echo 'URL: '.rtrim($n8nUrl, '/').'/api/v1/health'.'<br>';
    echo 'HTTP Code: '.$httpCode.'<br>';
    echo 'Respuesta: '.htmlspecialchars($response).'<br>';
    echo 'Error cURL: '.($curlError ?: 'Ninguno').'<br>';
    echo 'Estado: '.($httpCode === 200 ? '✅ OK' : '❌ Error').'<br><br>';

    // 2. Verificar autenticación con API key
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, rtrim($n8nUrl, '/').'/api/v1/workflows');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: application/json',
        'X-N8N-API-KEY: '.$n8nApiKey,
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    echo '<strong>2. Verificación de autenticación:</strong><br>';
    echo 'URL: '.rtrim($n8nUrl, '/').'/api/v1/workflows'.'<br>';
    echo 'HTTP Code: '.$httpCode.'<br>';
    echo 'Respuesta: '.htmlspecialchars(substr($response, 0, 200)).(strlen($response) > 200 ? '...' : '').'<br>';
    echo 'Error cURL: '.($curlError ?: 'Ninguno').'<br>';
    echo 'Estado: '.($httpCode === 200 ? '✅ OK' : '❌ Error').'<br><br>';

    // 3. Verificar webhook URL
    echo '<strong>3. Verificación de webhook URL:</strong><br>';
    echo 'URL del webhook: '.htmlspecialchars($n8nWebhookUrl).'<br>';

    if (filter_var($n8nWebhookUrl, FILTER_VALIDATE_URL)) {
        echo 'Formato de URL: ✅ Válido<br>';

        // Intentar hacer una petición de prueba al webhook
        $testPayload = [
            'test' => true,
            'message' => 'Prueba de conectividad',
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $n8nWebhookUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-N8N-API-KEY: '.$n8nApiKey,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testPayload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        echo 'Petición de prueba HTTP Code: '.$httpCode.'<br>';
        echo 'Respuesta: '.htmlspecialchars(substr($response, 0, 200)).(strlen($response) > 200 ? '...' : '').'<br>';
        echo 'Error cURL: '.($curlError ?: 'Ninguno').'<br>';
        echo 'Estado: '.($httpCode >= 200 && $httpCode < 300 ? '✅ OK' : '❌ Error').'<br>';

    } else {
        echo 'Formato de URL: ❌ Inválido<br>';
    }

    echo '<br><h2>Recomendaciones</h2>';

    if ($httpCode === 200) {
        echo '✅ La configuración parece estar correcta.<br>';
        echo '✅ n8n está respondiendo correctamente.<br>';
        echo '✅ La API key es válida.<br>';
        echo '✅ El webhook está configurado.<br><br>';

        echo '<strong>Próximos pasos:</strong><br>';
        echo '1. Verifica que el workflow de n8n esté activo.<br>';
        echo '2. Prueba enviar una difusión desde la interfaz.<br>';
        echo '3. Revisa los logs de n8n para verificar que reciba las peticiones.<br>';

    } else {
        echo '❌ Hay problemas con la configuración.<br><br>';

        echo '<strong>Posibles soluciones:</strong><br>';
        echo '1. Verifica que n8n esté ejecutándose en la URL especificada.<br>';
        echo '2. Confirma que la API key sea correcta.<br>';
        echo '3. Verifica que el webhook URL sea válido y accesible.<br>';
        echo '4. Revisa los logs de n8n para más detalles.<br>';
        echo '5. Asegúrate de que el firewall permita las conexiones.<br>';
    }

} catch (Exception $e) {
    echo '❌ Error: '.$e->getMessage().'<br>';
    echo 'Archivo: '.$e->getFile().'<br>';
    echo 'Línea: '.$e->getLine().'<br>';
}

echo '<br><hr>';
echo '<p><strong>Nota:</strong> Si sigues teniendo problemas, revisa los logs del servidor web (Apache/Nginx) para más detalles.</p>';
?> 