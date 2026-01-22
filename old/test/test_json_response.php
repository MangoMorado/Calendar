<?php
/**
 * Script para probar la respuesta JSON del endpoint de difusiones
 * Ejecutar desde el navegador: test/test_json_response.php
 */

// Activar reporte de errores
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

echo '<h1>Prueba de Respuesta JSON - Endpoint de Difusiones</h1>';

// Función para hacer petición POST
function makePostRequest($url, $data)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');

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

// Función para simular login
function login()
{
    $loginData = [
        'email' => 'admin@example.com',
        'password' => 'admin123',
        'action' => 'login',
    ];

    $result = makePostRequest('http://localhost/Calendar/login.php', $loginData);

    return $result['http_code'] === 200;
}

echo '<h2>1. Verificando Login</h2>';
if (login()) {
    echo '✅ Login exitoso<br>';
} else {
    echo '❌ Error en login<br>';
    echo 'Asegúrate de estar logueado en el sistema<br><br>';
}

echo '<h2>2. Probando Endpoint con Datos Mínimos</h2>';

// Datos de prueba mínimos
$testData = [
    'list_id' => '1',
    'message' => 'Prueba de respuesta JSON',
    'selected_contacts' => [],
];

echo 'Enviando petición a: api/send_broadcast_n8n.php<br>';
echo 'Datos: '.json_encode($testData).'<br><br>';

$result = makePostRequest('http://localhost/Calendar/api/send_broadcast_n8n.php', $testData);

echo '<h3>Resultado de la Petición:</h3>';
echo '<strong>HTTP Code:</strong> '.$result['http_code'].'<br>';
echo '<strong>cURL Error:</strong> '.($result['curl_error'] ?: 'Ninguno').'<br>';
echo '<strong>Tamaño de respuesta:</strong> '.strlen($result['response']).' bytes<br>';

echo '<h3>Respuesta Completa:</h3>';
echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto;'>";
echo htmlspecialchars($result['response']);
echo '</pre>';

echo '<h3>Análisis de la Respuesta:</h3>';

if (empty($result['response'])) {
    echo '❌ <strong>Respuesta vacía</strong><br>';
    echo 'Esto indica que el endpoint no está devolviendo nada.<br>';
} else {
    // Verificar si es JSON válido
    $jsonData = json_decode($result['response'], true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo '✅ <strong>JSON válido</strong><br>';
        echo 'Estructura: '.json_encode($jsonData, JSON_PRETTY_PRINT).'<br>';
    } else {
        echo '❌ <strong>JSON inválido</strong><br>';
        echo 'Error: '.json_last_error_msg().'<br>';
        echo 'Posible causa: Hay texto antes o después del JSON<br>';

        // Buscar dónde empieza el JSON
        $jsonStart = strpos($result['response'], '{');
        if ($jsonStart !== false) {
            echo 'JSON empieza en la posición: '.$jsonStart.'<br>';
            if ($jsonStart > 0) {
                echo 'Texto antes del JSON: '.htmlspecialchars(substr($result['response'], 0, $jsonStart)).'<br>';
            }
        }
    }
}

echo '<h2>3. Verificando Headers de Respuesta</h2>';
echo '<pre>';
print_r($result['info']);
echo '</pre>';

echo '<h2>4. Prueba con Endpoint Alternativo</h2>';
echo '<p>Si el problema persiste, prueba con el endpoint alternativo:</p>';
echo "<a href='test_endpoint_alternative.php'>Probar endpoint alternativo</a>";

echo '<br><hr>';
echo '<p><strong>Recomendaciones:</strong></p>';
echo '<ul>';
echo '<li>Si la respuesta está vacía, revisa los logs de PHP</li>';
echo "<li>Si hay texto antes del JSON, busca 'echo' o 'print' antes del JSON</li>";
echo '<li>Si el JSON es inválido, verifica que no haya errores PHP mezclados</li>';
echo '<li>Verifica que no haya espacios o líneas en blanco antes de &lt;?php</li>';
echo '</ul>';
?> 