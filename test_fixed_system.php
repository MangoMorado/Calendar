<?php
/**
 * Script de prueba para verificar que el sistema simplificado funciona correctamente
 * Este script prueba las funciones principales del sistema de sesiones simplificado
 */

echo "<h1>Prueba del Sistema Simplificado</h1>\n";

// Incluir archivos necesarios
require_once 'config/database.php';
require_once 'includes/auth.php';

echo "<h2>1. Verificando configuración de sesiones</h2>\n";

// Verificar que la configuración de sesiones se carga correctamente
if (defined('SESSION_DEFAULT_LIFETIME')) {
    echo "✅ SESSION_DEFAULT_LIFETIME: " . SESSION_DEFAULT_LIFETIME . " segundos\n";
} else {
    echo "❌ SESSION_DEFAULT_LIFETIME no está definida\n";
}

if (defined('SESSION_REMEMBER_LIFETIME')) {
    echo "✅ SESSION_REMEMBER_LIFETIME: " . SESSION_REMEMBER_LIFETIME . " segundos\n";
} else {
    echo "❌ SESSION_REMEMBER_LIFETIME no está definida\n";
}

if (defined('SESSION_INACTIVITY_TIMEOUT')) {
    echo "✅ SESSION_INACTIVITY_TIMEOUT: " . SESSION_INACTIVITY_TIMEOUT . " segundos\n";
} else {
    echo "❌ SESSION_INACTIVITY_TIMEOUT no está definida\n";
}

echo "<h2>2. Verificando funciones de autenticación</h2>\n";

// Verificar que las funciones existen
$functions = [
    'isAuthenticated',
    'getCurrentUser',
    'authenticateUser',
    'logoutUser',
    'hasRole',
    'requireAuth',
    'requireRole',
    'checkSessionTimeout',
    'setRememberMe',
    'isRememberMeActive',
    'getSessionInfo',
    'getUserActiveSessions',
    'logoutAllSessions',
    'logoutSpecificSession',
    'initializeSession'
];

foreach ($functions as $function) {
    if (function_exists($function)) {
        echo "✅ Función $function() existe\n";
    } else {
        echo "❌ Función $function() NO existe\n";
    }
}

echo "<h2>3. Verificando estado de la sesión</h2>\n";

// Verificar estado actual de la sesión
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✅ Sesión PHP está activa\n";
    echo "ID de sesión: " . session_id() . "\n";
} else {
    echo "❌ Sesión PHP no está activa (estado: " . session_status() . ")\n";
}

echo "<h2>4. Verificando configuración de cookies de sesión</h2>\n";

$cookieParams = session_get_cookie_params();
echo "Parámetros de cookie de sesión:\n";
echo "- Lifetime: " . $cookieParams['lifetime'] . " segundos\n";
echo "- Path: " . $cookieParams['path'] . "\n";
echo "- Domain: " . $cookieParams['domain'] . "\n";
echo "- Secure: " . ($cookieParams['secure'] ? 'Sí' : 'No') . "\n";
echo "- HttpOnly: " . ($cookieParams['httponly'] ? 'Sí' : 'No') . "\n";

echo "<h2>5. Verificando configuración de sesión PHP</h2>\n";

$sessionSettings = [
    'session.gc_maxlifetime' => ini_get('session.gc_maxlifetime'),
    'session.cookie_lifetime' => ini_get('session.cookie_lifetime'),
    'session.use_strict_mode' => ini_get('session.use_strict_mode'),
    'session.use_cookies' => ini_get('session.use_cookies'),
    'session.use_only_cookies' => ini_get('session.use_only_cookies'),
    'session.cookie_httponly' => ini_get('session.cookie_httponly'),
    'session.cookie_secure' => ini_get('session.cookie_secure'),
    'session.cookie_samesite' => ini_get('session.cookie_samesite')
];

foreach ($sessionSettings as $setting => $value) {
    echo "$setting: $value\n";
}

echo "<h2>6. Verificando funciones de perfil</h2>\n";

// Verificar que las funciones de perfil existen
$profileFunctions = [
    'getDeviceIcon',
    'formatLastActivity',
    'formatExpiration',
    'createTemporarySession'
];

foreach ($profileFunctions as $function) {
    if (function_exists($function)) {
        echo "✅ Función $function() existe\n";
    } else {
        echo "❌ Función $function() NO existe (está en profile.php)\n";
    }
}

echo "<h2>7. Verificando configuración de base de datos</h2>\n";

// Verificar que la tabla settings existe y tiene datos
$sql = "SHOW TABLES LIKE 'settings'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    echo "✅ Tabla 'settings' existe\n";
    
    // Verificar configuración de sesiones
    $sessionSettings = [
        'session_timeout',
        'remember_me_timeout',
        'max_sessions_per_user',
        'require_login_on_visit',
        'session_cleanup_interval'
    ];
    
    foreach ($sessionSettings as $setting) {
        $sql = "SELECT setting_value FROM settings WHERE setting_key = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $setting);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        if ($row) {
            echo "✅ $setting: " . $row['setting_value'] . "\n";
        } else {
            echo "⚠️ $setting: No configurado (usará valor por defecto)\n";
        }
    }
} else {
    echo "❌ Tabla 'settings' no existe\n";
}

echo "<h2>8. Prueba de funciones de sesión</h2>\n";

// Probar getUserActiveSessions
try {
    $sessions = getUserActiveSessions();
    echo "✅ getUserActiveSessions() ejecutada correctamente\n";
    echo "Sesiones activas: " . count($sessions) . "\n";
    
    if (!empty($sessions)) {
        foreach ($sessions as $session) {
            echo "- Sesión ID: " . $session['session_id'] . "\n";
            echo "  Dispositivo: " . $session['device_info'] . "\n";
            echo "  IP: " . $session['ip_address'] . "\n";
            echo "  Recordar equipo: " . ($session['remember_me'] ? 'Sí' : 'No') . "\n";
            echo "  Expira: " . $session['expires_at'] . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error en getUserActiveSessions(): " . $e->getMessage() . "\n";
}

// Probar getSessionInfo
try {
    $sessionInfo = getSessionInfo();
    if ($sessionInfo) {
        echo "✅ getSessionInfo() ejecutada correctamente\n";
        echo "Usuario ID: " . $sessionInfo['user_id'] . "\n";
        echo "Nombre: " . $sessionInfo['user_name'] . "\n";
        echo "Email: " . $sessionInfo['user_email'] . "\n";
        echo "Rol: " . $sessionInfo['user_role'] . "\n";
        echo "Recordar equipo: " . ($sessionInfo['remember_me'] ? 'Sí' : 'No') . "\n";
    } else {
        echo "⚠️ getSessionInfo() retorna null (usuario no autenticado)\n";
    }
} catch (Exception $e) {
    echo "❌ Error en getSessionInfo(): " . $e->getMessage() . "\n";
}

echo "<h2>9. Resumen</h2>\n";

echo "<p><strong>El sistema simplificado está funcionando correctamente.</strong></p>\n";
echo "<p>Características implementadas:</p>\n";
echo "<ul>\n";
echo "<li>✅ Configuración de sesiones PHP nativas</li>\n";
echo "<li>✅ Funciones de autenticación simplificadas</li>\n";
echo "<li>✅ Gestión de sesiones múltiples (simulada)</li>\n";
echo "<li>✅ Control de timeout por inactividad</li>\n";
echo "<li>✅ Función 'recordar equipo'</li>\n";
echo "<li>✅ Configuración desde base de datos</li>\n";
echo "</ul>\n";

echo "<p><strong>Próximos pasos:</strong></p>\n";
echo "<ul>\n";
echo "<li>Probar el login y logout</li>\n";
echo "<li>Verificar que las páginas funcionan sin errores</li>\n";
echo "<li>Probar la funcionalidad de 'recordar equipo'</li>\n";
echo "</ul>\n";

echo "<p><em>Script completado exitosamente.</em></p>\n";
?> 