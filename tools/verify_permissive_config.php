<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

echo "<h2>Verificación de Configuración Permisiva</h2>";

// Configuración esperada para sesiones permisivas
$expectedSettings = [
    'session_timeout' => '0',           // Sin expiración
    'remember_me_timeout' => '-1',      // Siempre
    'max_sessions_per_user' => '0',     // Sin límite
    'require_login_on_visit' => '0',    // No requerir login
    'session_cleanup_interval' => '86400' // 24 horas
];

echo "<h3>1. Verificando configuración en base de datos:</h3>";
$sessionSettings = "SELECT setting_key, setting_value FROM session_settings ORDER BY setting_key";
$result = mysqli_query($conn, $sessionSettings);

$configStatus = true;
if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Configuración</th><th>Valor Actual</th><th>Valor Esperado</th><th>Estado</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        $key = $row['setting_key'];
        $value = $row['setting_value'];
        $expected = $expectedSettings[$key] ?? 'N/A';
        
        if (isset($expectedSettings[$key])) {
            if ($value === $expected) {
                $status = '✅ Correcto';
            } else {
                $status = '❌ Incorrecto';
                $configStatus = false;
            }
        } else {
            $status = '⚠️ Configuración adicional';
        }
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($key) . "</td>";
        echo "<td>" . htmlspecialchars($value) . "</td>";
        echo "<td>" . htmlspecialchars($expected) . "</td>";
        echo "<td>" . $status . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>2. Verificando sesiones activas:</h3>";
$activeSessions = "SELECT us.*, u.name, u.email 
                  FROM user_sessions us 
                  JOIN users u ON us.user_id = u.id 
                  WHERE us.is_active = 1 
                  ORDER BY us.last_activity DESC";
$result = mysqli_query($conn, $activeSessions);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Usuario</th><th>IP</th><th>Última Actividad</th><th>Expira</th><th>Recordar</th><th>Estado</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        $expiresAt = $row['expires_at'];
        $status = '';
        
        if ($expiresAt == '2099-12-31 23:59:59') {
            $status = '✅ Permanente';
        } elseif ($expiresAt == '0000-00-00 00:00:00') {
            $status = '❌ Fecha inválida';
        } else {
            $status = '⚠️ Otra fecha';
        }
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['name']) . "<br><small>" . htmlspecialchars($row['email']) . "</small></td>";
        echo "<td>" . htmlspecialchars($row['ip_address']) . "</td>";
        echo "<td>" . htmlspecialchars($row['last_activity']) . "</td>";
        echo "<td>" . htmlspecialchars($expiresAt) . "</td>";
        echo "<td>" . ($row['remember_me'] ? 'Sí' : 'No') . "</td>";
        echo "<td>" . $status . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; margin: 10px 0; border-radius: 5px;'>";
    echo "⚠️ No hay sesiones activas en la base de datos";
    echo "</div>";
}

echo "<h3>3. Verificando funcionalidad del SessionManager:</h3>";
global $sessionManager;

if (isset($sessionManager)) {
    echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; margin: 10px 0; border-radius: 5px;'>";
    echo "✅ SessionManager está disponible<br>";
    
    // Verificar configuraciones específicas
    $sessionTimeout = $sessionManager->getSetting('session_timeout');
    $rememberMeTimeout = $sessionManager->getSetting('remember_me_timeout');
    $maxSessions = $sessionManager->getSetting('max_sessions_per_user');
    $requireLogin = $sessionManager->getSetting('require_login_on_visit');
    
    echo "• session_timeout: $sessionTimeout<br>";
    echo "• remember_me_timeout: $rememberMeTimeout<br>";
    echo "• max_sessions_per_user: $maxSessions<br>";
    echo "• require_login_on_visit: $requireLogin<br>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; margin: 10px 0; border-radius: 5px;'>";
    echo "❌ SessionManager no está disponible";
    echo "</div>";
}

echo "<h3>4. Verificando estado de autenticación actual:</h3>";
if (isAuthenticated()) {
    $currentUser = getCurrentUser();
    echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; margin: 10px 0; border-radius: 5px;'>";
    echo "✅ Usuario autenticado: " . htmlspecialchars($currentUser['name']) . "<br>";
    echo "Email: " . htmlspecialchars($currentUser['email']) . "<br>";
    echo "Rol: " . htmlspecialchars($currentUser['role']) . "<br>";
    echo "</div>";
    
    // Verificar sesiones del usuario actual
    $userSessions = getUserActiveSessions();
    if (!empty($userSessions)) {
        echo "<div style='background: #e8f5e8; padding: 10px; border: 1px solid #4caf50; margin: 10px 0; border-radius: 5px;'>";
        echo "✅ Sesiones activas del usuario actual: " . count($userSessions) . "<br>";
        foreach ($userSessions as $session) {
            echo "• Sesión: " . htmlspecialchars(substr($session['session_id'], 0, 16)) . "...<br>";
            echo "&nbsp;&nbsp;&nbsp;IP: " . htmlspecialchars($session['ip_address']) . "<br>";
            echo "&nbsp;&nbsp;&nbsp;Expira: " . htmlspecialchars($session['expires_at']) . "<br>";
            echo "&nbsp;&nbsp;&nbsp;Recordar: " . ($session['remember_me'] ? 'Sí' : 'No') . "<br>";
        }
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 10px; border: 1px solid #ffeaa7; margin: 10px 0; border-radius: 5px;'>";
        echo "⚠️ No hay sesiones activas para el usuario actual";
        echo "</div>";
    }
} else {
    echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; margin: 10px 0; border-radius: 5px;'>";
    echo "❌ No hay usuario autenticado actualmente";
    echo "</div>";
}

echo "<h3>5. Resumen de verificación:</h3>";
if ($configStatus) {
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; margin: 10px 0; border-radius: 5px;'>";
    echo "<h4 style='color: #155724; margin-top: 0;'>✅ Configuración Permisiva Aplicada Correctamente</h4>";
    echo "<p>El sistema está configurado para sesiones permisivas:</p>";
    echo "<ul>";
    echo "<li>✅ Sesiones sin expiración (session_timeout = 0)</li>";
    echo "<li>✅ Recordar equipo siempre (remember_me_timeout = -1)</li>";
    echo "<li>✅ Sin límite de sesiones por usuario (max_sessions_per_user = 0)</li>";
    echo "<li>✅ No requerir login en cada visita (require_login_on_visit = 0)</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; margin: 10px 0; border-radius: 5px;'>";
    echo "<h4 style='color: #721c24; margin-top: 0;'>❌ Configuración Incompleta</h4>";
    echo "<p>Algunas configuraciones no se aplicaron correctamente. Ejecuta configure_permissive_sessions.php nuevamente.</p>";
    echo "</div>";
}

echo "<h3>6. Pruebas recomendadas:</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; margin: 10px 0; border-radius: 5px;'>";
echo "<ol>";
echo "<li><strong>Iniciar sesión con 'recordar equipo':</strong> Ve a login.php y marca la opción</li>";
echo "<li><strong>Cerrar y abrir el navegador:</strong> Verifica que sigues logueado</li>";
echo "<li><strong>Abrir en múltiples pestañas/dispositivos:</strong> Verifica que puedes tener múltiples sesiones</li>";
echo "<li><strong>Verificar sesiones en perfil:</strong> Ve a profile.php y revisa la pestaña de sesiones</li>";
echo "<li><strong>Probar API:</strong> Verifica que los endpoints funcionan sin problemas de autenticación</li>";
echo "</ol>";
echo "</div>";

echo "<h3>🔧 Enlaces útiles:</h3>";
echo "<div style='margin: 10px 0;'>";
echo "<a href='configure_permissive_sessions.php' style='margin: 5px; padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 3px;'>⚙️ Configurar Permisivo</a>";
echo "<a href='login.php' style='margin: 5px; padding: 8px 15px; background: #28a745; color: white; text-decoration: none; border-radius: 3px;'>🔐 Probar Login</a>";
echo "<a href='profile.php' style='margin: 5px; padding: 8px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 3px;'>👤 Ver Perfil</a>";
echo "<a href='debug_session.php' style='margin: 5px; padding: 8px 15px; background: #ffc107; color: black; text-decoration: none; border-radius: 3px;'>🔍 Debug</a>";
echo "</div>";
?> 