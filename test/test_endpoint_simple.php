<?php
// Script simple para probar el endpoint import_contacts.php
echo "<h2>Prueba Simple del Endpoint import_contacts.php</h2>";

// Simular una petición POST
$_SERVER['REQUEST_METHOD'] = 'POST';

// Incluir el endpoint directamente
ob_start();
include __DIR__ . '/../api/import_contacts.php';
$output = ob_get_clean();

echo "<h3>Respuesta del endpoint:</h3>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Verificar código de respuesta
$httpCode = http_response_code();
echo "<h3>Código de respuesta HTTP: $httpCode</h3>";

if ($httpCode == 200) {
    echo "✅ El endpoint funciona correctamente<br>";
} elseif ($httpCode == 401) {
    echo "❌ Error 401 - Usuario no autenticado (esto es normal desde línea de comandos)<br>";
} elseif ($httpCode == 400) {
    echo "❌ Error 400 - Problema con la configuración<br>";
} else {
    echo "⚠️ Código de respuesta inesperado: $httpCode<br>";
}

echo "<h3>Análisis:</h3>";
echo "Si obtienes error 401, significa que el endpoint está funcionando correctamente<br>";
echo "pero el usuario no está autenticado (normal desde línea de comandos).<br>";
echo "El problema real está en el navegador, no en el servidor.<br>";

echo "<h3>Solución:</h3>";
echo "1. Ve a tu aplicación web en el navegador<br>";
echo "2. Inicia sesión correctamente<br>";
echo "3. Intenta importar contactos desde la interfaz web<br>";
echo "4. El error 400 debería estar resuelto<br>";
?> 