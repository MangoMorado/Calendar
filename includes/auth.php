<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir funciones de usuario
require_once __DIR__ . '/user_functions.php';

/**
 * Verificar si el usuario está autenticado
 */
function isAuthenticated() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user']) && !empty($_SESSION['user']);
}

/**
 * Obtener información del usuario actual
 */
function getCurrentUser() {
    return isAuthenticated() ? $_SESSION['user'] : null;
}

/**
 * Iniciar sesión de usuario
 */
function authenticateUser($user) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['user'] = $user;
    // Limpiar la URL de redirección si existe
    $redirect = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : 'index.php';
    unset($_SESSION['redirect_url']);
    return $redirect;
}

/**
 * Cerrar sesión de usuario
 */
function logoutUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Destruir la sesión
    session_destroy();
    // Iniciar una nueva sesión para mensajes
    session_start();
    $_SESSION['message'] = 'Has cerrado sesión exitosamente';
    header('Location: /login.php');
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
    if (!isAuthenticated()) {
        // Guardar la URL actual para redirigir después del login
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: /login.php');
        exit();
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
?> 