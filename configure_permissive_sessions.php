<?php
require_once 'config/database.php';

echo "<h2>Configuración de Sesiones Permisivas</h2>";
echo "<p>Configurando el sistema para sesiones más permisivas...</p>";

// Configuración permisiva para sesiones
$permissiveSettings = [
    'session_timeout' => '0',           // 0 = Sin expiración (siempre válida)
    'remember_me_timeout' => '-1',      // -1 = Siempre (recordar equipo permanente)
    'max_sessions_per_user' => '0',     // 0 = Sin límite de sesiones
    'require_login_on_visit' => '0',    // 0 = No requerir login en cada visita
    'session_cleanup_interval' => '86400' // 24 horas (mantener limpieza automática)
];

echo "<h3>Configuración Actual:</h3>";
$currentSettings = "SELECT setting_key, setting_value FROM session_settings ORDER BY setting_key";
$result = mysqli_query($conn, $currentSettings);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Configuración</th><th>Valor Actual</th><th>Nuevo Valor</th><th>Descripción</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        $key = $row['setting_key'];
        $currentValue = $row['setting_value'];
        $newValue = $permissiveSettings[$key] ?? $currentValue;
        
        $description = '';
        switch ($key) {
            case 'session_timeout':
                $description = 'Tiempo de sesión normal (0 = sin expiración)';
                break;
            case 'remember_me_timeout':
                $description = 'Tiempo de recordar equipo (-1 = siempre)';
                break;
            case 'max_sessions_per_user':
                $description = 'Límite de sesiones por usuario (0 = sin límite)';
                break;
            case 'require_login_on_visit':
                $description = 'Requerir login en cada visita (0 = no)';
                break;
            case 'session_cleanup_interval':
                $description = 'Intervalo de limpieza automática (24 horas)';
                break;
        }
        
        $status = ($currentValue == $newValue) ? '✅ Actual' : '🔄 Cambiará';
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($key) . "</td>";
        echo "<td>" . htmlspecialchars($currentValue) . "</td>";
        echo "<td>" . htmlspecialchars($newValue) . "</td>";
        echo "<td>" . $description . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>Actualizando configuración:</h3>";

$updateCount = 0;
$errorCount = 0;

foreach ($permissiveSettings as $key => $value) {
    $updateSql = "UPDATE session_settings SET setting_value = ? WHERE setting_key = ?";
    $stmt = mysqli_prepare($conn, $updateSql);
    mysqli_stmt_bind_param($stmt, "ss", $value, $key);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "✅ $key actualizado a: $value<br>";
        $updateCount++;
    } else {
        echo "❌ Error actualizando $key: " . mysqli_error($conn) . "<br>";
        $errorCount++;
    }
}

// Verificar que se actualizó correctamente
echo "<h3>Verificando configuración actualizada:</h3>";
$verifySql = "SELECT setting_key, setting_value FROM session_settings ORDER BY setting_key";
$result = mysqli_query($conn, $verifySql);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Configuración</th><th>Valor</th><th>Estado</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        $key = $row['setting_key'];
        $value = $row['setting_value'];
        $status = '';
        
        if (isset($permissiveSettings[$key])) {
            if ($value === $permissiveSettings[$key]) {
                $status = '✅ Configurado correctamente';
            } else {
                $status = '❌ No se actualizó correctamente';
            }
        } else {
            $status = '⚠️ Configuración adicional';
        }
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($key) . "</td>";
        echo "<td>" . htmlspecialchars($value) . "</td>";
        echo "<td>" . $status . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>Resumen de la configuración:</h3>";
echo "✅ Configuraciones actualizadas: $updateCount<br>";
if ($errorCount > 0) {
    echo "❌ Errores encontrados: $errorCount<br>";
} else {
    echo "✅ No se encontraron errores<br>";
}

echo "<h3>🎯 Configuración Aplicada:</h3>";
echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; margin: 10px 0; border-radius: 5px;'>";
echo "<strong>✅ Sesiones sin expiración:</strong> Las sesiones normales no expirarán<br>";
echo "<strong>✅ Recordar equipo siempre:</strong> Las sesiones con 'recordar equipo' serán permanentes<br>";
echo "<strong>✅ Sin límite de sesiones:</strong> Los usuarios pueden tener tantas sesiones como quieran<br>";
echo "<strong>✅ No requerir login en cada visita:</strong> Los usuarios permanecerán logueados<br>";
echo "<strong>✅ Limpieza automática:</strong> Se mantiene la limpieza de sesiones expiradas cada 24 horas<br>";
echo "</div>";

echo "<h3>⚠️ Consideraciones de Seguridad:</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; margin: 10px 0; border-radius: 5px;'>";
echo "<strong>🔒 Riesgos:</strong><br>";
echo "• Sesiones que nunca expiran pueden ser un riesgo de seguridad<br>";
echo "• Sin límite de sesiones puede consumir más recursos<br>";
echo "• Usuarios pueden permanecer logueados indefinidamente<br>";
echo "<br>";
echo "<strong>🛡️ Recomendaciones:</strong><br>";
echo "• Monitorear regularmente las sesiones activas<br>";
echo "• Implementar logout manual para usuarios inactivos<br>";
echo "• Considerar implementar logout por inactividad después de mucho tiempo<br>";
echo "• Revisar logs de acceso regularmente<br>";
echo "</div>";

echo "<h3>📋 Próximos pasos:</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; margin: 10px 0; border-radius: 5px;'>";
echo "1. <strong>Probar el sistema:</strong> Inicia sesión y verifica que funciona como esperas<br>";
echo "2. <strong>Verificar 'recordar equipo':</strong> Marca la opción al hacer login<br>";
echo "3. <strong>Probar múltiples sesiones:</strong> Abre la aplicación en varios dispositivos<br>";
echo "4. <strong>Monitorear:</strong> Revisa la tabla user_sessions para ver las sesiones activas<br>";
echo "5. <strong>Configurar limpieza:</strong> Asegúrate de que el cron job de limpieza esté activo<br>";
echo "</div>";

echo "<h3>🔧 Scripts útiles:</h3>";
echo "<div style='margin: 10px 0;'>";
echo "<a href='debug_session.php' style='margin: 5px; padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 3px;'>🔍 Debug Sesiones</a>";
echo "<a href='test_session_creation.php' style='margin: 5px; padding: 8px 15px; background: #28a745; color: white; text-decoration: none; border-radius: 3px;'>🧪 Probar Sesiones</a>";
echo "<a href='profile.php' style='margin: 5px; padding: 8px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 3px;'>👤 Ver Perfil</a>";
echo "</div>";

echo "<h3>✅ Configuración completada</h3>";
echo "<p>El sistema ahora está configurado para sesiones más permisivas. Los usuarios podrán:</p>";
echo "<ul>";
echo "<li>Mantener sesiones activas indefinidamente</li>";
echo "<li>Usar 'recordar equipo' para sesiones permanentes</li>";
echo "<li>Tener múltiples sesiones simultáneas sin límite</li>";
echo "<li>No tener que iniciar sesión en cada visita</li>";
echo "</ul>";
?> 