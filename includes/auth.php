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
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
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
    $_SESSION['user'] = $user;
}

/**
 * Cerrar sesión de usuario
 */
function logoutUser() {
    if (isAuthenticated()) {
        updateUserHistory($_SESSION['user']['id'], 'Cierre de sesión');
        unset($_SESSION['user']);
    }
    
    // Destruir la sesión por completo para mayor seguridad
    session_unset();
    session_destroy();
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
        // Guardar la URL solicitada para redirigir después del login
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        
        header('Location: login.php');
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