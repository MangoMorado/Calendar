<?php
/**
 * Configuración de Sesiones
 * Este archivo debe incluirse ANTES de cualquier session_start()
 */

// Configuración por defecto de sesiones
define('SESSION_DEFAULT_LIFETIME', 24 * 60 * 60); // 24 horas
define('SESSION_REMEMBER_LIFETIME', 30 * 24 * 60 * 60); // 30 días
define('SESSION_INACTIVITY_TIMEOUT', 8 * 60 * 60); // 8 horas

/**
 * Configurar sesión con parámetros por defecto
 */
function configureDefaultSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.gc_maxlifetime', SESSION_DEFAULT_LIFETIME);
        session_set_cookie_params(SESSION_DEFAULT_LIFETIME);
    }
}

/**
 * Configurar sesión para "recordar equipo"
 */
function configureRememberMeSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.gc_maxlifetime', SESSION_REMEMBER_LIFETIME);
        session_set_cookie_params(SESSION_REMEMBER_LIFETIME);
    }
}

/**
 * Configurar sesión con parámetros personalizados
 */
function configureSession($lifetime = SESSION_DEFAULT_LIFETIME) {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.gc_maxlifetime', $lifetime);
        session_set_cookie_params($lifetime);
    }
}

/**
 * Obtener configuración actual de sesión
 */
function getSessionConfig() {
    return [
        'gc_maxlifetime' => ini_get('session.gc_maxlifetime'),
        'cookie_lifetime' => ini_get('session.cookie_lifetime'),
        'use_cookies' => ini_get('session.use_cookies'),
        'cookie_path' => ini_get('session.cookie_path'),
        'cookie_domain' => ini_get('session.cookie_domain'),
        'cookie_secure' => ini_get('session.cookie_secure'),
        'cookie_httponly' => ini_get('session.cookie_httponly')
    ];
}

// Configurar sesión por defecto al cargar este archivo
configureDefaultSession();
?> 