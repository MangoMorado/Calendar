<?php
require_once __DIR__ . '/../includes/session_config.php';
require_once 'config/database.php';

echo "<h2>Corrigiendo Configuración de Sesiones</h2>";

// Configuración correcta de sesiones
$correctSettings = [
    'session_timeout' => '3600', // 1 hora en segundos
    'remember_me_timeout' => '604800', // 7 días en segundos
    'max_sessions_per_user' => '5',
    'require_login_on_visit' => '1', // 1 = sí, 0 = no
    'session_cleanup_interval' => '86400' // 24 horas en segundos
];

echo "<h3>Actualizando configuración de sesiones:</h3>";

foreach ($correctSettings as $key => $value) {
    $updateSql = "UPDATE session_settings SET setting_value = ? WHERE setting_key = ?";
    $stmt = mysqli_prepare($conn, $updateSql);
    mysqli_stmt_bind_param($stmt, "ss", $value, $key);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "✅ $key actualizado a: $value<br>";
    } else {
        echo "❌ Error actualizando $key: " . mysqli_error($conn) . "<br>";
    }
}

// Verificar que se actualizó correctamente
echo "<h3>Verificando configuración actualizada:</h3>";
$verifySql = "SELECT setting_key, setting_value FROM session_settings ORDER BY setting_key";
$result = mysqli_query($conn, $verifySql);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Configuración</th><th>Valor</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr><td>" . htmlspecialchars($row['setting_key']) . "</td><td>" . htmlspecialchars($row['setting_value']) . "</td></tr>";
    }
    echo "</table>";
}

echo "<h3>✅ Configuración de sesiones corregida</h3>";
echo "<p>Ahora las sesiones deberían funcionar correctamente. Intenta acceder a la aplicación web nuevamente.</p>";
?> 