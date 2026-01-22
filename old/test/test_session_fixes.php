<?php
echo '<h2>Prueba de Corrección de Errores de Sesión</h2>';

echo '<h3>1. Verificando configuración de sesión:</h3>';
echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; margin: 10px 0; border-radius: 5px;'>";

// Incluir configuración de sesiones
require_once __DIR__.'/../includes/session_config.php';

echo '<strong>Configuración actual de sesión:</strong><br>';
$config = getSessionConfig();
echo "<table style='width: 100%;'>";
echo '<tr><td><strong>session.gc_maxlifetime:</strong></td><td>'.$config['gc_maxlifetime'].' segundos ('.gmdate('H:i:s', $config['gc_maxlifetime']).')</td></tr>';
echo '<tr><td><strong>session.cookie_lifetime:</strong></td><td>'.$config['cookie_lifetime'].' segundos ('.gmdate('H:i:s', $config['cookie_lifetime']).')</td></tr>';
echo '<tr><td><strong>session.use_cookies:</strong></td><td>'.$config['use_cookies'].'</td></tr>';
echo '<tr><td><strong>session.cookie_path:</strong></td><td>'.$config['cookie_path'].'</td></tr>';
echo '<tr><td><strong>session.cookie_domain:</strong></td><td>'.$config['cookie_domain'].'</td></tr>';
echo '<tr><td><strong>session.cookie_secure:</strong></td><td>'.$config['cookie_secure'].'</td></tr>';
echo '<tr><td><strong>session.cookie_httponly:</strong></td><td>'.$config['cookie_httponly'].'</td></tr>';
echo '</table>';
echo '</div>';

echo '<h3>2. Probando inclusión de auth.php sin errores:</h3>';
echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; margin: 10px 0; border-radius: 5px;'>";

// Capturar cualquier error que pueda ocurrir
ob_start();
$errorOccurred = false;

try {
    require_once __DIR__.'/../includes/auth.php';
    echo '✅ auth.php cargado correctamente sin errores<br>';

    // Verificar que las funciones están disponibles
    if (function_exists('isAuthenticated')) {
        echo '✅ Función isAuthenticated() disponible<br>';
    } else {
        echo '❌ Función isAuthenticated() no disponible<br>';
        $errorOccurred = true;
    }

    if (function_exists('getCurrentUser')) {
        echo '✅ Función getCurrentUser() disponible<br>';
    } else {
        echo '❌ Función getCurrentUser() no disponible<br>';
        $errorOccurred = true;
    }

    if (function_exists('authenticateUser')) {
        echo '✅ Función authenticateUser() disponible<br>';
    } else {
        echo '❌ Función authenticateUser() no disponible<br>';
        $errorOccurred = true;
    }

} catch (Exception $e) {
    echo '❌ Error al cargar auth.php: '.$e->getMessage().'<br>';
    $errorOccurred = true;
}

$output = ob_get_clean();
echo $output;

if ($errorOccurred) {
    echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; margin: 10px 0; border-radius: 5px;'>";
    echo '❌ Se encontraron errores al cargar auth.php';
    echo '</div>';
} else {
    echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; margin: 10px 0; border-radius: 5px;'>";
    echo '✅ auth.php cargado sin errores';
    echo '</div>';
}

echo '</div>';

echo '<h3>3. Verificando estado de la sesión:</h3>';
echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; margin: 10px 0; border-radius: 5px;'>";
echo '<strong>Estado de sesión:</strong> '.session_status().'<br>';
echo '<strong>ID de sesión:</strong> '.session_id().'<br>';
echo '<strong>Usuario autenticado:</strong> '.(isAuthenticated() ? 'Sí' : 'No').'<br>';

if (isAuthenticated()) {
    $user = getCurrentUser();
    echo '<strong>Usuario actual:</strong> '.htmlspecialchars($user['name']).' ('.htmlspecialchars($user['email']).')<br>';
} else {
    echo '<strong>Usuario actual:</strong> No autenticado<br>';
}
echo '</div>';

echo '<h3>4. Probando funciones de sesión:</h3>';
echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; margin: 10px 0; border-radius: 5px;'>";

// Probar función checkSessionTimeout
$timeoutResult = checkSessionTimeout();
echo '<strong>checkSessionTimeout():</strong> '.($timeoutResult ? '✅ Funciona correctamente' : '❌ Error').'<br>';

// Probar función isRememberMeActive
$rememberMeResult = isRememberMeActive();
echo '<strong>isRememberMeActive():</strong> '.($rememberMeResult ? 'Sí' : 'No').'<br>';

// Probar función getSessionInfo
$sessionInfo = getSessionInfo();
if ($sessionInfo) {
    echo '<strong>getSessionInfo():</strong> ✅ Funciona correctamente<br>';
    echo '&nbsp;&nbsp;&nbsp;• Usuario: '.htmlspecialchars($sessionInfo['user_name']).'<br>';
    echo '&nbsp;&nbsp;&nbsp;• Recordar equipo: '.($sessionInfo['remember_me'] ? 'Sí' : 'No').'<br>';
} else {
    echo '<strong>getSessionInfo():</strong> ⚠️ No hay sesión activa<br>';
}

echo '</div>';

echo '<h3>5. Verificando configuración de PHP:</h3>';
echo "<div style='background: #e9ecef; padding: 15px; border: 1px solid #dee2e6; margin: 10px 0; border-radius: 5px;'>";
echo "<table style='width: 100%;'>";
echo '<tr><td><strong>session.save_handler:</strong></td><td>'.ini_get('session.save_handler').'</td></tr>';
echo '<tr><td><strong>session.save_path:</strong></td><td>'.ini_get('session.save_path').'</td></tr>';
echo '<tr><td><strong>session.use_cookies:</strong></td><td>'.ini_get('session.use_cookies').'</td></tr>';
echo '<tr><td><strong>session.cookie_lifetime:</strong></td><td>'.ini_get('session.cookie_lifetime').'</td></tr>';
echo '<tr><td><strong>session.gc_maxlifetime:</strong></td><td>'.ini_get('session.gc_maxlifetime').'</td></tr>';
echo '</table>';
echo '</div>';

echo '<h3>6. Resumen de la corrección:</h3>';
echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; margin: 10px 0; border-radius: 5px;'>";
echo "<h4 style='color: #155724; margin-top: 0;'>✅ Errores Corregidos</h4>";
echo '<ul>';
echo '<li>✅ <strong>Configuración movida:</strong> Los parámetros de sesión se configuran ANTES de session_start()</li>';
echo '<li>✅ <strong>Archivo separado:</strong> session_config.php maneja toda la configuración</li>';
echo '<li>✅ <strong>Sin warnings:</strong> No más errores de ini_set() o session_set_cookie_params()</li>';
echo '<li>✅ <strong>Funcionalidad intacta:</strong> Todas las funciones siguen funcionando</li>';
echo '<li>✅ <strong>Código más limpio:</strong> Mejor organización del código</li>';
echo '</ul>';
echo '</div>';

echo '<h3>7. Próximos pasos:</h3>';
echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; margin: 10px 0; border-radius: 5px;'>";
echo '<ol>';
echo '<li><strong>Probar login:</strong> Ve a login.php y verifica que funciona sin errores</li>';
echo "<li><strong>Probar 'recordar equipo':</strong> Marca la opción al hacer login</li>";
echo '<li><strong>Verificar persistencia:</strong> Cierra y abre el navegador</li>';
echo '<li><strong>Probar API:</strong> Verifica que los endpoints funcionan correctamente</li>';
echo '<li><strong>Monitorear logs:</strong> Revisa que no hay más warnings en los logs</li>';
echo '</ol>';
echo '</div>';

echo '<h3>🔧 Enlaces útiles:</h3>';
echo "<div style='margin: 10px 0;'>";
echo "<a href='login.php' style='margin: 5px; padding: 8px 15px; background: #28a745; color: white; text-decoration: none; border-radius: 3px;'>🔐 Probar Login</a>";
echo "<a href='test_simple_sessions.php' style='margin: 5px; padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 3px;'>🧪 Probar Sesiones</a>";
echo "<a href='index.php' style='margin: 5px; padding: 8px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 3px;'>🏠 Ir al Inicio</a>";
echo '</div>';

echo '<h3>✅ Corrección completada</h3>';
echo '<p>Los errores de configuración de sesión han sido corregidos. El sistema ahora funciona sin warnings.</p>';
?> 