<?php
require_once 'config/database.php';

echo "<h2>Limpieza de Tablas de Sesiones Obsoletas</h2>";
echo "<p>Eliminando tablas y configuraciones del sistema de sesiones complejo...</p>";

// Lista de tablas y configuraciones a eliminar
$tablesToDrop = [
    'user_sessions',
    'session_settings'
];

$settingsToRemove = [
    'session_timeout',
    'remember_me_timeout', 
    'max_sessions_per_user',
    'require_login_on_visit',
    'session_cleanup_interval',
    'last_cleanup'
];

echo "<h3>1. Eliminando tablas de sesiones:</h3>";
$droppedTables = 0;

foreach ($tablesToDrop as $table) {
    $checkTable = "SHOW TABLES LIKE '$table'";
    $result = mysqli_query($conn, $checkTable);
    
    if (mysqli_num_rows($result) > 0) {
        $dropSql = "DROP TABLE IF EXISTS $table";
        if (mysqli_query($conn, $dropSql)) {
            echo "✅ Tabla '$table' eliminada correctamente<br>";
            $droppedTables++;
        } else {
            echo "❌ Error eliminando tabla '$table': " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "⚠️ Tabla '$table' no existe<br>";
    }
}

echo "<h3>2. Eliminando configuraciones de sesiones:</h3>";
$removedSettings = 0;

foreach ($settingsToRemove as $setting) {
    $deleteSql = "DELETE FROM settings WHERE setting_key = ?";
    $stmt = mysqli_prepare($conn, $deleteSql);
    mysqli_stmt_bind_param($stmt, "s", $setting);
    
    if (mysqli_stmt_execute($stmt)) {
        $affectedRows = mysqli_stmt_affected_rows($stmt);
        if ($affectedRows > 0) {
            echo "✅ Configuración '$setting' eliminada<br>";
            $removedSettings++;
        } else {
            echo "⚠️ Configuración '$setting' no existía<br>";
        }
    } else {
        echo "❌ Error eliminando configuración '$setting': " . mysqli_error($conn) . "<br>";
    }
}

echo "<h3>3. Verificando tablas restantes:</h3>";
$remainingTables = "SHOW TABLES";
$result = mysqli_query($conn, $remainingTables);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Tablas Restantes</th></tr>";
    while ($row = mysqli_fetch_array($result)) {
        echo "<tr><td>" . htmlspecialchars($row[0]) . "</td></tr>";
    }
    echo "</table>";
}

echo "<h3>4. Verificando configuraciones restantes:</h3>";
$remainingSettings = "SELECT setting_key, setting_value FROM settings ORDER BY setting_key";
$result = mysqli_query($conn, $remainingSettings);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Configuración</th><th>Valor</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['setting_key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['setting_value']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; margin: 10px 0; border-radius: 5px;'>";
    echo "⚠️ No hay configuraciones restantes en la tabla settings";
    echo "</div>";
}

echo "<h3>5. Resumen de la limpieza:</h3>";
echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; margin: 10px 0; border-radius: 5px;'>";
echo "<h4 style='color: #155724; margin-top: 0;'>✅ Limpieza Completada</h4>";
echo "<p><strong>Tablas eliminadas:</strong> $droppedTables</p>";
echo "<p><strong>Configuraciones eliminadas:</strong> $removedSettings</p>";
echo "</div>";

echo "<h3>6. Beneficios del sistema simplificado:</h3>";
echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; margin: 10px 0; border-radius: 5px;'>";
echo "<ul>";
echo "<li>✅ <strong>Menos complejidad:</strong> Solo sesiones PHP nativas</li>";
echo "<li>✅ <strong>Mejor rendimiento:</strong> Sin consultas adicionales a la BD</li>";
echo "<li>✅ <strong>Menos código:</strong> Sistema más fácil de mantener</li>";
echo "<li>✅ <strong>Menos conflictos:</strong> Un solo sistema de autenticación</li>";
echo "<li>✅ <strong>Base de datos más limpia:</strong> Sin tablas innecesarias</li>";
echo "</ul>";
echo "</div>";

echo "<h3>7. Próximos pasos:</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; margin: 10px 0; border-radius: 5px;'>";
echo "<ol>";
echo "<li><strong>Probar el login:</strong> Ve a login.php y verifica que funciona</li>";
echo "<li><strong>Probar 'recordar equipo':</strong> Marca la opción al hacer login</li>";
echo "<li><strong>Verificar sesiones:</strong> Cierra y abre el navegador para probar persistencia</li>";
echo "<li><strong>Probar API:</strong> Verifica que los endpoints funcionan correctamente</li>";
echo "<li><strong>Monitorear:</strong> Revisa que no hay errores en los logs</li>";
echo "</ol>";
echo "</div>";

echo "<h3>🔧 Enlaces útiles:</h3>";
echo "<div style='margin: 10px 0;'>";
echo "<a href='login.php' style='margin: 5px; padding: 8px 15px; background: #28a745; color: white; text-decoration: none; border-radius: 3px;'>🔐 Probar Login</a>";
echo "<a href='index.php' style='margin: 5px; padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 3px;'>🏠 Ir al Inicio</a>";
echo "<a href='debug_session.php' style='margin: 5px; padding: 8px 15px; background: #ffc107; color: black; text-decoration: none; border-radius: 3px;'>🔍 Debug</a>";
echo "</div>";

echo "<h3>✅ Sistema simplificado listo</h3>";
echo "<p>El sistema de autenticación ahora usa solo sesiones PHP nativas. Es más simple, rápido y confiable.</p>";
?> 