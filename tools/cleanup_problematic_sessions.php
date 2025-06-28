<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>Limpieza de Sesiones Problemáticas</h2>";

// 1. Mostrar sesiones problemáticas actuales
echo "<h3>1. Sesiones Problemáticas Actuales:</h3>";
$problematicSessions = "SELECT us.*, u.name, u.email 
                       FROM user_sessions us 
                       JOIN users u ON us.user_id = u.id 
                       WHERE us.expires_at = '2099-12-31 23:59:59' 
                       OR us.expires_at < NOW() 
                       OR us.is_active = 0
                       ORDER BY us.last_activity DESC";
$result = mysqli_query($conn, $problematicSessions);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Usuario</th><th>Session ID</th><th>IP</th><th>Última Actividad</th><th>Expira</th><th>Estado</th><th>Problema</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        $problem = '';
        if ($row['expires_at'] == '2099-12-31 23:59:59') {
            $problem = 'Sesión infinita (problemática)';
        } elseif ($row['expires_at'] < date('Y-m-d H:i:s')) {
            $problem = 'Sesión expirada';
        } elseif ($row['is_active'] == 0) {
            $problem = 'Sesión inactiva';
        }
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['name']) . " (" . htmlspecialchars($row['email']) . ")</td>";
        echo "<td>" . htmlspecialchars(substr($row['session_id'], 0, 16)) . "...</td>";
        echo "<td>" . htmlspecialchars($row['ip_address']) . "</td>";
        echo "<td>" . htmlspecialchars($row['last_activity']) . "</td>";
        echo "<td>" . htmlspecialchars($row['expires_at']) . "</td>";
        echo "<td>" . ($row['is_active'] ? 'Activa' : 'Inactiva') . "</td>";
        echo "<td>" . $problem . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "✅ No se encontraron sesiones problemáticas<br>";
}

// 2. Limpiar sesiones expiradas
echo "<h3>2. Limpiando Sesiones Expiradas:</h3>";
$cleanupExpired = "DELETE FROM user_sessions WHERE expires_at < NOW()";
$result = mysqli_query($conn, $cleanupExpired);
$expiredCount = mysqli_affected_rows($conn);
echo "✅ Sesiones expiradas eliminadas: $expiredCount<br>";

// 3. Limpiar sesiones infinitas (problemáticas)
echo "<h3>3. Limpiando Sesiones Infinitas (Problemáticas):</h3>";
$cleanupInfinite = "DELETE FROM user_sessions WHERE expires_at = '2099-12-31 23:59:59'";
$result = mysqli_query($conn, $cleanupInfinite);
$infiniteCount = mysqli_affected_rows($conn);
echo "✅ Sesiones infinitas eliminadas: $infiniteCount<br>";

// 4. Limpiar sesiones inactivas
echo "<h3>4. Limpiando Sesiones Inactivas:</h3>";
$cleanupInactive = "DELETE FROM user_sessions WHERE is_active = 0";
$result = mysqli_query($conn, $cleanupInactive);
$inactiveCount = mysqli_affected_rows($conn);
echo "✅ Sesiones inactivas eliminadas: $inactiveCount<br>";

// 5. Verificar sesiones restantes
echo "<h3>5. Sesiones Restantes (Después de la Limpieza):</h3>";
$remainingSessions = "SELECT us.*, u.name, u.email 
                     FROM user_sessions us 
                     JOIN users u ON us.user_id = u.id 
                     WHERE us.is_active = 1 
                     ORDER BY us.last_activity DESC";
$result = mysqli_query($conn, $remainingSessions);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Usuario</th><th>Session ID</th><th>IP</th><th>Última Actividad</th><th>Expira</th><th>Recordar</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['name']) . " (" . htmlspecialchars($row['email']) . ")</td>";
        echo "<td>" . htmlspecialchars(substr($row['session_id'], 0, 16)) . "...</td>";
        echo "<td>" . htmlspecialchars($row['ip_address']) . "</td>";
        echo "<td>" . htmlspecialchars($row['last_activity']) . "</td>";
        echo "<td>" . htmlspecialchars($row['expires_at']) . "</td>";
        echo "<td>" . ($row['remember_me'] ? 'Sí' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "✅ No hay sesiones activas restantes<br>";
}

// 6. Resumen de la limpieza
echo "<h3>6. Resumen de la Limpieza:</h3>";
$totalCleaned = $expiredCount + $infiniteCount + $inactiveCount;
echo "✅ Total de sesiones eliminadas: $totalCleaned<br>";
echo "- Sesiones expiradas: $expiredCount<br>";
echo "- Sesiones infinitas: $infiniteCount<br>";
echo "- Sesiones inactivas: $inactiveCount<br>";

// 7. Verificar configuración actual
echo "<h3>7. Configuración Actual de Sesiones:</h3>";
$sessionSettings = "SELECT setting_key, setting_value FROM session_settings ORDER BY setting_key";
$result = mysqli_query($conn, $sessionSettings);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Configuración</th><th>Valor</th><th>Estado</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        $key = $row['setting_key'];
        $value = $row['setting_value'];
        $status = '';
        
        if ($key === 'session_timeout' && $value === '0') {
            $status = '❌ PROBLEMÁTICO - Sin expiración';
        } elseif ($key === 'remember_me_timeout' && $value === '-1') {
            $status = '❌ PROBLEMÁTICO - Inválido';
        } elseif ($key === 'max_sessions_per_user' && $value === '0') {
            $status = '❌ PROBLEMÁTICO - Sin límite';
        } else {
            $status = '✅ Correcto';
        }
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($key) . "</td>";
        echo "<td>" . htmlspecialchars($value) . "</td>";
        echo "<td>" . $status . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>8. Próximos Pasos:</h3>";
echo "1. Ejecuta <strong>fix_session_config.php</strong> para corregir la configuración<br>";
echo "2. Prueba el sistema de autenticación<br>";
echo "3. Verifica que las sesiones funcionan correctamente<br>";
echo "4. Si hay problemas, ejecuta este script nuevamente<br>";

echo "<h3>9. Beneficios de la Limpieza:</h3>";
echo "✅ Base de datos más limpia y eficiente<br>";
echo "✅ Eliminación de sesiones problemáticas<br>";
echo "✅ Mejor rendimiento del sistema<br>";
echo "✅ Reducción de conflictos de autenticación<br>";
?> 