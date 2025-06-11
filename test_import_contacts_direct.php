<?php
// Simular una petición POST al endpoint import_contacts.php
echo "<h2>Prueba Directa del Endpoint import_contacts.php</h2>";

// Incluir el archivo directamente
ob_start();
include 'api/import_contacts.php';
$output = ob_get_clean();

echo "<h3>Respuesta del endpoint:</h3>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Verificar si hay errores en los logs
echo "<h3>Verificación de errores:</h3>";
$errorLog = error_get_last();
if ($errorLog) {
    echo "❌ Error detectado:<br>";
    echo "Tipo: " . $errorLog['type'] . "<br>";
    echo "Mensaje: " . $errorLog['message'] . "<br>";
    echo "Archivo: " . $errorLog['file'] . "<br>";
    echo "Línea: " . $errorLog['line'] . "<br>";
} else {
    echo "✅ No se detectaron errores de PHP<br>";
}

// Verificar headers enviados
echo "<h3>Headers enviados:</h3>";
$headers = headers_list();
if (!empty($headers)) {
    foreach ($headers as $header) {
        echo htmlspecialchars($header) . "<br>";
    }
} else {
    echo "No se enviaron headers<br>";
}

// Verificar código de respuesta HTTP
echo "<h3>Código de respuesta HTTP:</h3>";
$httpCode = http_response_code();
echo "Código: $httpCode<br>";

if ($httpCode == 400) {
    echo "❌ Error 400 detectado - Esto confirma el problema<br>";
} elseif ($httpCode == 200) {
    echo "✅ Respuesta exitosa<br>";
} else {
    echo "⚠️ Código de respuesta inesperado: $httpCode<br>";
}
?> 