-- Crear base de datos y seleccionarla
CREATE DATABASE IF NOT EXISTS calendar_app;
USE calendar_app;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS users (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL DEFAULT '57',
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    history TEXT,
    color VARCHAR(7) DEFAULT '#3788d8',
    calendar_visible TINYINT(1) NOT NULL DEFAULT 1,
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expiry DATETIME DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de citas
CREATE TABLE IF NOT EXISTS appointments (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    calendar_type ENUM('estetico', 'veterinario', 'general') DEFAULT 'estetico',
    all_day TINYINT(1) NOT NULL DEFAULT 0,
    user_id INT(11) DEFAULT NULL,
    color VARCHAR(7) DEFAULT '#3788d8',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabla de notas
CREATE TABLE IF NOT EXISTS notes (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    type ENUM('nota', 'sugerencia', 'otro') NOT NULL DEFAULT 'nota',
    visibility ENUM('solo_yo', 'todos') NOT NULL DEFAULT 'solo_yo',
    user_id INT(11) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabla de configuraciones generales
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(255) NOT NULL PRIMARY KEY,
    setting_value TEXT NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Clave de configuración para n8n: flujo de notificaciones (si no existe)
INSERT INTO settings (setting_key, setting_value)
SELECT * FROM (SELECT 'selected_notifications_workflow', '') AS tmp
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE setting_key = 'selected_notifications_workflow');

-- Semilla para hora de envío de notificaciones (por defecto 09:00)
INSERT INTO settings (setting_key, setting_value)
SELECT * FROM (SELECT 'notifications_send_time', '09:00') AS tmp
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE setting_key = 'notifications_send_time');

-- Tabla de sesiones de usuario
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    session_id VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    device_info VARCHAR(255),
    remember_me TINYINT(1) NOT NULL DEFAULT 0,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at),
    INDEX idx_is_active (is_active)
);

-- Tabla de configuración de sesiones
CREATE TABLE IF NOT EXISTS session_settings (
    setting_key VARCHAR(255) NOT NULL PRIMARY KEY,
    setting_value TEXT NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertar configuración por defecto de sesiones si no existe
INSERT INTO session_settings (setting_key, setting_value)
SELECT * FROM (SELECT 'session_timeout', '3600') AS tmp
WHERE NOT EXISTS (SELECT 1 FROM session_settings WHERE setting_key = 'session_timeout')
UNION ALL
SELECT * FROM (SELECT 'remember_me_timeout', '604800') AS tmp
WHERE NOT EXISTS (SELECT 1 FROM session_settings WHERE setting_key = 'remember_me_timeout')
UNION ALL
SELECT * FROM (SELECT 'max_sessions_per_user', '5') AS tmp
WHERE NOT EXISTS (SELECT 1 FROM session_settings WHERE setting_key = 'max_sessions_per_user')
UNION ALL
SELECT * FROM (SELECT 'require_login_on_visit', '1') AS tmp
WHERE NOT EXISTS (SELECT 1 FROM session_settings WHERE setting_key = 'require_login_on_visit')
UNION ALL
SELECT * FROM (SELECT 'session_cleanup_interval', '86400') AS tmp
WHERE NOT EXISTS (SELECT 1 FROM session_settings WHERE setting_key = 'session_cleanup_interval');

-- Tabla de contactos para difusiones Evolution API
CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    number VARCHAR(50) NOT NULL UNIQUE,
    pushName VARCHAR(255) DEFAULT NULL,
    send BOOLEAN NOT NULL DEFAULT FALSE
); 