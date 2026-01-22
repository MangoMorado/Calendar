<?php
// Incluir configuración de sesiones ANTES de iniciar la sesión
require_once __DIR__.'/session_config.php';

// Configurar sesión por defecto antes de iniciarla
configureDefaultSession();

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir funciones de usuario
require_once __DIR__.'/user_functions.php';

/**
 * Verificar si el usuario está autenticado
 */
function isAuthenticated()
{
    // Verificar si hay una sesión PHP con datos de usuario
    return isset($_SESSION['user']) && ! empty($_SESSION['user']) && isset($_SESSION['user']['id']);
}

/**
 * Obtener información del usuario actual
 */
function getCurrentUser()
{
    if (isAuthenticated()) {
        return $_SESSION['user'];
    }

    return null;
}

/**
 * Iniciar sesión de usuario
 */
function authenticateUser($user, $rememberMe = false)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Almacenar datos del usuario en sesión PHP
    $_SESSION['user'] = $user;

    // Si se marca "recordar equipo", extender la duración de la sesión
    if ($rememberMe) {
        // Marcar en la sesión que está en modo "recordar equipo"
        $_SESSION['remember_me'] = true;

        // Regenerar ID de sesión para mayor seguridad
        session_regenerate_id(true);
    }

    // Limpiar la URL de redirección si existe
    $redirect = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : 'index.php';
    unset($_SESSION['redirect_url']);

    return $redirect;
}

/**
 * Cerrar sesión de usuario
 */
function logoutUser()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Limpiar datos de usuario de la sesión
    unset($_SESSION['user']);

    // Destruir la sesión PHP
    session_destroy();

    // Iniciar una nueva sesión para mensajes
    session_start();
    $_SESSION['message'] = 'Has cerrado sesión exitosamente';

    header('Location: login.php');
    exit();
}

/**
 * Verificar si el usuario tiene el rol requerido
 */
function hasRole($requiredRole)
{
    if (! isAuthenticated()) {
        return false;
    }

    if ($requiredRole === 'admin') {
        return $_SESSION['user']['role'] === 'admin';
    }

    // Los administradores pueden acceder a todo
    if ($_SESSION['user']['role'] === 'admin') {
        return true;
    }

    return $_SESSION['user']['role'] === $requiredRole;
}

/**
 * Redirigir si no está autenticado
 */
function requireAuth()
{
    if (! isAuthenticated()) {
        // Guardar la URL actual para redirigir después del login
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit();
    }
}

/**
 * Redirigir si no tiene el rol requerido
 */
function requireRole($role)
{
    requireAuth();

    if (! hasRole($role)) {
        header('Location: unauthorized.php');
        exit();
    }
}

/**
 * Verificar si la sesión ha expirado por inactividad
 */
function checkSessionTimeout()
{
    // Tiempo máximo de inactividad (en segundos) - 8 horas por defecto
    $maxInactivity = SESSION_INACTIVITY_TIMEOUT;

    if (isset($_SESSION['last_activity'])) {
        $inactiveTime = time() - $_SESSION['last_activity'];

        if ($inactiveTime > $maxInactivity) {
            // Sesión expirada por inactividad
            logoutUser();

            return false;
        }
    }

    // Actualizar tiempo de última actividad
    $_SESSION['last_activity'] = time();

    return true;
}

/**
 * Configurar sesión para "recordar equipo" (debe llamarse ANTES de session_start)
 */
function setRememberMe($enabled = true)
{
    if (session_status() !== PHP_SESSION_NONE) {
        // Si la sesión ya está activa, no podemos cambiar la configuración
        // Solo marcamos en la sesión
        if ($enabled) {
            $_SESSION['remember_me'] = true;
        } else {
            unset($_SESSION['remember_me']);
        }

        return;
    }

    if ($enabled) {
        configureRememberMeSession();
    } else {
        configureDefaultSession();
    }
}

/**
 * Verificar si la sesión actual está en modo "recordar equipo"
 */
function isRememberMeActive()
{
    return isset($_SESSION['remember_me']) && $_SESSION['remember_me'] === true;
}

/**
 * Obtener información de la sesión actual
 */
function getSessionInfo()
{
    if (! isAuthenticated()) {
        return null;
    }

    $info = [
        'user_id' => $_SESSION['user']['id'],
        'user_name' => $_SESSION['user']['name'],
        'user_email' => $_SESSION['user']['email'],
        'user_role' => $_SESSION['user']['role'],
        'session_id' => session_id(),
        'remember_me' => isRememberMeActive(),
        'last_activity' => isset($_SESSION['last_activity']) ? $_SESSION['last_activity'] : null,
        'session_started' => isset($_SESSION['session_started']) ? $_SESSION['session_started'] : null,
    ];

    return $info;
}

/**
 * Obtener sesiones activas del usuario actual (versión simplificada)
 * Como ahora usamos solo sesiones PHP nativas, esta función devuelve la sesión actual
 */
function getUserActiveSessions()
{
    if (! isAuthenticated()) {
        return [];
    }

    // Obtener información del dispositivo actual
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $deviceInfo = 'Desconocido';

    if (preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent)) {
        $deviceInfo = 'Móvil';
    } elseif (preg_match('/Windows/i', $userAgent)) {
        $deviceInfo = 'Windows';
    } elseif (preg_match('/Mac/i', $userAgent)) {
        $deviceInfo = 'Mac';
    } elseif (preg_match('/Linux/i', $userAgent)) {
        $deviceInfo = 'Linux';
    }

    // Obtener IP actual
    $ipAddress = '127.0.0.1';
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    $ipAddress = $ip;
                    break 2;
                }
            }
        }
    }
    if ($ipAddress === '127.0.0.1') {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    // Crear array con la sesión actual
    $currentSession = [
        'session_id' => session_id(),
        'ip_address' => $ipAddress,
        'user_agent' => $userAgent,
        'device_info' => $deviceInfo,
        'remember_me' => isRememberMeActive() ? 1 : 0,
        'expires_at' => isRememberMeActive() ?
            date('Y-m-d H:i:s', time() + SESSION_REMEMBER_LIFETIME) :
            date('Y-m-d H:i:s', time() + SESSION_DEFAULT_LIFETIME),
        'created_at' => isset($_SESSION['session_started']) ?
            date('Y-m-d H:i:s', $_SESSION['session_started']) :
            date('Y-m-d H:i:s'),
        'last_activity' => isset($_SESSION['last_activity']) ?
            date('Y-m-d H:i:s', $_SESSION['last_activity']) :
            date('Y-m-d H:i:s'),
        'is_active' => 1,
    ];

    return [$currentSession];
}

/**
 * Cerrar todas las sesiones del usuario actual (versión simplificada)
 * Como ahora usamos solo sesiones PHP nativas, esto simplemente cierra la sesión actual
 */
function logoutAllSessions()
{
    if (! isAuthenticated()) {
        return false;
    }

    logoutUser();

    return true;
}

/**
 * Cerrar una sesión específica (versión simplificada)
 * Como ahora usamos solo sesiones PHP nativas, esto cierra la sesión actual si coincide
 */
function logoutSpecificSession($sessionId)
{
    if (! isAuthenticated()) {
        return false;
    }

    // Si la sesión ID coincide con la actual, cerrar sesión
    if ($sessionId === session_id()) {
        logoutUser();

        return true;
    }

    return false;
}

/**
 * Inicializar sesión con configuración básica
 */
function initializeSession()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Marcar cuando se inició la sesión
    if (! isset($_SESSION['session_started'])) {
        $_SESSION['session_started'] = time();
    }

    // Actualizar última actividad
    $_SESSION['last_activity'] = time();
}

// Inicializar sesión al cargar este archivo
initializeSession();
?> 