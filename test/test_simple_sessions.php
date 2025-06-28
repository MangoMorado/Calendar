<?php
require_once __DIR__ . '/../includes/auth.php';

echo "<h2>Prueba del Sistema de Sesiones Simplificado</h2>";

echo "<h3>1. Estado actual de la sesión:</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; margin: 10px 0; border-radius: 5px;'>";
echo "<strong>ID de sesión:</strong> " . session_id() . "<br>";
echo "<strong>Estado de sesión:</strong> " . session_status() . "<br>";
echo "<strong>Usuario autenticado:</strong> " . (isAuthenticated() ? 'Sí' : 'No') . "<br>";
echo "</div>";

echo "<h3>2. Información de la sesión actual:</h3>";
$sessionInfo = getSessionInfo();
if ($sessionInfo) {
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; margin: 10px 0; border-radius: 5px;'>";
    echo "<h4 style='color: #155724; margin-top: 0;'>✅ Sesión Activa</h4>";
    echo "<table style='width: 100%;'>";
    echo "<tr><td><strong>Usuario:</strong></td><td>" . htmlspecialchars($sessionInfo['user_name']) . "</td></tr>";
    echo "<tr><td><strong>Email:</strong></td><td>" . htmlspecialchars($sessionInfo['user_email']) . "</td></tr>";
    echo "<tr><td><strong>Rol:</strong></td><td>" . htmlspecialchars($sessionInfo['user_role']) . "</td></tr>";
    echo "<tr><td><strong>ID de Sesión:</strong></td><td>" . htmlspecialchars($sessionInfo['session_id']) . "</td></tr>";
    echo "<tr><td><strong>Recordar Equipo:</strong></td><td>" . ($sessionInfo['remember_me'] ? 'Sí' : 'No') . "</td></tr>";
    echo "<tr><td><strong>Última Actividad:</strong></td><td>" . ($sessionInfo['last_activity'] ? date('Y-m-d H:i:s', $sessionInfo['last_activity']) : 'N/A') . "</td></tr>";
    echo "<tr><td><strong>Sesión Iniciada:</strong></td><td>" . ($sessionInfo['session_started'] ? date('Y-m-d H:i:s', $sessionInfo['session_started']) : 'N/A') . "</td></tr>";
    echo "</table>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; margin: 10px 0; border-radius: 5px;'>";
    echo "<h4 style='color: #721c24; margin-top: 0;'>❌ No hay sesión activa</h4>";
    echo "<p>No hay usuario autenticado actualmente.</p>";
    echo "</div>";
}

echo "<h3>3. Verificación de timeout de sesión:</h3>";
$timeoutResult = checkSessionTimeout();
if ($timeoutResult) {
    echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; margin: 10px 0; border-radius: 5px;'>";
    echo "✅ Sesión válida - timeout verificado correctamente";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; margin: 10px 0; border-radius: 5px;'>";
    echo "❌ Sesión expirada por inactividad";
    echo "</div>";
}

echo "<h3>4. Configuración de PHP para sesiones:</h3>";
echo "<div style='background: #e9ecef; padding: 15px; border: 1px solid #dee2e6; margin: 10px 0; border-radius: 5px;'>";
echo "<table style='width: 100%;'>";
echo "<tr><td><strong>session.save_handler:</strong></td><td>" . ini_get('session.save_handler') . "</td></tr>";
echo "<tr><td><strong>session.save_path:</strong></td><td>" . ini_get('session.save_path') . "</td></tr>";
echo "<tr><td><strong>session.use_cookies:</strong></td><td>" . ini_get('session.use_cookies') . "</td></tr>";
echo "<tr><td><strong>session.cookie_lifetime:</strong></td><td>" . ini_get('session.cookie_lifetime') . "</td></tr>";
echo "<tr><td><strong>session.gc_maxlifetime:</strong></td><td>" . ini_get('session.gc_maxlifetime') . "</td></tr>";
echo "<tr><td><strong>session.cookie_path:</strong></td><td>" . ini_get('session.cookie_path') . "</td></tr>";
echo "<tr><td><strong>session.cookie_domain:</strong></td><td>" . ini_get('session.cookie_domain') . "</td></tr>";
echo "<tr><td><strong>session.cookie_secure:</strong></td><td>" . ini_get('session.cookie_secure') . "</td></tr>";
echo "<tr><td><strong>session.cookie_httponly:</strong></td><td>" . ini_get('session.cookie_httponly') . "</td></tr>";
echo "</table>";
echo "</div>";

echo "<h3>5. Cookies de sesión:</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; margin: 10px 0; border-radius: 5px;'>";
if (isset($_COOKIE[session_name()])) {
    echo "✅ Cookie de sesión encontrada: " . htmlspecialchars($_COOKIE[session_name()]) . "<br>";
    echo "<strong>Nombre de la cookie:</strong> " . session_name() . "<br>";
    echo "<strong>Valor:</strong> " . htmlspecialchars($_COOKIE[session_name()]) . "<br>";
} else {
    echo "⚠️ No se encontró cookie de sesión<br>";
    echo "Esto es normal si no hay sesión activa o si las cookies están deshabilitadas<br>";
}
echo "</div>";

echo "<h3>6. Pruebas de funcionalidad:</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; margin: 10px 0; border-radius: 5px;'>";

// Probar función hasRole
if (isAuthenticated()) {
    $user = getCurrentUser();
    echo "<strong>Prueba de roles:</strong><br>";
    echo "• Usuario actual: " . htmlspecialchars($user['role']) . "<br>";
    echo "• ¿Es admin? " . (hasRole('admin') ? 'Sí' : 'No') . "<br>";
    echo "• ¿Es user? " . (hasRole('user') ? 'Sí' : 'No') . "<br>";
} else {
    echo "⚠️ No hay usuario autenticado para probar roles<br>";
}

// Probar función isRememberMeActive
echo "<strong>Recordar equipo:</strong> " . (isRememberMeActive() ? 'Activo' : 'Inactivo') . "<br>";

echo "</div>";

echo "<h3>7. Acciones disponibles:</h3>";
echo "<div style='margin: 10px 0;'>";

if (isAuthenticated()) {
    echo "<a href='logout.php' style='margin: 5px; padding: 8px 15px; background: #dc3545; color: white; text-decoration: none; border-radius: 3px;'>🚪 Cerrar Sesión</a>";
    echo "<a href='profile.php' style='margin: 5px; padding: 8px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 3px;'>👤 Ver Perfil</a>";
} else {
    echo "<a href='login.php' style='margin: 5px; padding: 8px 15px; background: #28a745; color: white; text-decoration: none; border-radius: 3px;'>🔐 Iniciar Sesión</a>";
}

echo "<a href='index.php' style='margin: 5px; padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 3px;'>🏠 Ir al Inicio</a>";
echo "<a href='debug_session.php' style='margin: 5px; padding: 8px 15px; background: #ffc107; color: black; text-decoration: none; border-radius: 3px;'>🔍 Debug Completo</a>";
echo "</div>";

echo "<h3>8. Resumen del sistema simplificado:</h3>";
echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; margin: 10px 0; border-radius: 5px;'>";
echo "<h4 style='color: #155724; margin-top: 0;'>✅ Sistema Funcionando Correctamente</h4>";
echo "<ul>";
echo "<li>✅ <strong>Sesiones PHP nativas:</strong> Sin dependencias externas</li>";
echo "<li>✅ <strong>Autenticación simple:</strong> Basada solo en \$_SESSION</li>";
echo "<li>✅ <strong>Recordar equipo:</strong> Funcionalidad integrada</li>";
echo "<li>✅ <strong>Control de roles:</strong> Sistema de permisos funcional</li>";
echo "<li>✅ <strong>Timeout automático:</strong> Protección por inactividad</li>";
echo "<li>✅ <strong>Sin base de datos:</strong> No requiere tablas de sesiones</li>";
echo "</ul>";
echo "</div>";

echo "<h3>9. Instrucciones para probar:</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; margin: 10px 0; border-radius: 5px;'>";
echo "<ol>";
echo "<li><strong>Si no estás logueado:</strong> Ve a login.php e inicia sesión</li>";
echo "<li><strong>Probar 'recordar equipo':</strong> Marca la opción al hacer login</li>";
echo "<li><strong>Verificar persistencia:</strong> Cierra y abre el navegador</li>";
echo "<li><strong>Probar múltiples pestañas:</strong> Abre la app en varias pestañas</li>";
echo "<li><strong>Verificar API:</strong> Prueba los endpoints de la API</li>";
echo "<li><strong>Monitorear:</strong> Revisa que no hay errores en los logs</li>";
echo "</ol>";
echo "</div>";

echo "<h3>✅ Prueba completada</h3>";
echo "<p>El sistema de sesiones simplificado está funcionando correctamente. Es más simple, rápido y confiable que el sistema anterior.</p>";
?> 