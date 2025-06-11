<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir funciones de usuario y gestor de sesiones
require_once __DIR__ . '/user_functions.php';
require_once __DIR__ . '/session_manager.php';

/**
 * Verificar si el usuario está autenticado
 */
function isAuthenticated() {
    global $sessionManager;
    
    // Primero verificar si hay una sesión PHP tradicional
    if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
        return true;
    }
    
    // Si no, verificar sesión basada en cookies
    $session = $sessionManager->validateSession();
    if ($session) {
        // Actualizar sesión PHP con los datos del usuario
        $_SESSION['user'] = [
            'id' => $session['user_id'],
            'name' => $session['name'],
            'email' => $session['email'],
            'role' => $session['role']
        ];
        return true;
    }
    
    return false;
}

/**
 * Obtener información del usuario actual
 */
function getCurrentUser() {
    if (isAuthenticated()) {
        return $_SESSION['user'];
    }
    return null;
}

/**
 * Iniciar sesión de usuario
 */
function authenticateUser($user, $rememberMe = false) {
    global $sessionManager;
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Crear sesión en la base de datos
    $sessionId = $sessionManager->createSession($user['id'], $rememberMe);
    
    if ($sessionId) {
        // Almacenar datos del usuario en sesión PHP
        $_SESSION['user'] = $user;
        
        // Limpiar la URL de redirección si existe
        $redirect = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : 'index.php';
        unset($_SESSION['redirect_url']);
        return $redirect;
    }
    
    return false;
}

/**
 * Cerrar sesión de usuario
 */
function logoutUser() {
    global $sessionManager;
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Destruir sesión en la base de datos
    $sessionManager->destroySession();
    
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
function hasRole($requiredRole) {
    if (!isAuthenticated()) {
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
function requireAuth() {
    global $sessionManager;
    
    if (!isAuthenticated()) {
        // Verificar si se requiere login en cada visita
        if ($sessionManager->requiresLoginOnVisit()) {
            // Guardar la URL actual para redirigir después del login
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            header('Location: login.php');
            exit();
        } else {
            // Si no se requiere login en cada visita, verificar si hay sesión válida
            $session = $sessionManager->validateSession();
            if (!$session) {
                $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
                header('Location: login.php');
                exit();
            }
        }
    }
}

/**
 * Redirigir si no tiene el rol requerido
 */
function requireRole($role) {
    requireAuth();
    
    if (!hasRole($role)) {
        header('Location: unauthorized.php');
        exit();
    }
}

/**
 * Obtener sesiones activas del usuario actual
 */
function getUserActiveSessions() {
    global $sessionManager;
    
    if (!isAuthenticated()) {
        return [];
    }
    
    return $sessionManager->getUserSessions($_SESSION['user']['id']);
}

/**
 * Cerrar todas las sesiones del usuario actual
 */
function logoutAllSessions() {
    global $sessionManager;
    
    if (!isAuthenticated()) {
        return false;
    }
    
    return $sessionManager->clearAllUserSessions($_SESSION['user']['id']);
}

/**
 * Cerrar una sesión específica
 */
function logoutSpecificSession($sessionId) {
    global $sessionManager;
    
    return $sessionManager->destroySession($sessionId);
}
?> 