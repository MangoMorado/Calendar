<?php

// Configuración de la base de datos LOCAL
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'calendar_app');

// Conexión a la base de datos
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Verificar conexión
if (! $conn) {
    error_log('Error en la conexión: '.mysqli_connect_error());
    exit('Error en la conexión: '.mysqli_connect_error());
}

// Crear la base de datos si no existe
$sql = 'CREATE DATABASE IF NOT EXISTS '.DB_NAME;
if (mysqli_query($conn, $sql)) {
    // Seleccionar la base de datos
    mysqli_select_db($conn, DB_NAME);

    // PRIMERO: Crear tabla de usuarios si no existe
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(255) NOT NULL,
        role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
        history TEXT,
        color VARCHAR(7) DEFAULT '#3788d8',
        calendar_visible TINYINT(1) NOT NULL DEFAULT 1,
        reset_token VARCHAR(255) DEFAULT NULL,
        reset_token_expiry DATETIME DEFAULT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";

    if (! mysqli_query($conn, $sql)) {
        error_log('Error al crear tabla de usuarios: '.mysqli_error($conn));
    }

    // Verificar si la tabla users ya tiene la columna calendar_visible
    $checkCalendarVisibleColumn = "SHOW COLUMNS FROM users LIKE 'calendar_visible'";
    $calendarVisibleResult = mysqli_query($conn, $checkCalendarVisibleColumn);
    if (mysqli_num_rows($calendarVisibleResult) == 0) {
        $alterCalendarVisibleSql = 'ALTER TABLE users ADD COLUMN calendar_visible TINYINT(1) NOT NULL DEFAULT 1';
        $result = mysqli_query($conn, $alterCalendarVisibleSql);
        if ($result) {
            error_log('Columna calendar_visible añadida a la tabla users correctamente');
        } else {
            error_log('Error al añadir la columna calendar_visible: '.mysqli_error($conn));
        }
    } else {
        error_log('La columna calendar_visible ya existe en la tabla users');
    }

    // Verificar si la tabla users ya tiene la columna color
    $checkUserColorColumn = "SHOW COLUMNS FROM users LIKE 'color'";
    $userColorResult = mysqli_query($conn, $checkUserColorColumn);
    if (mysqli_num_rows($userColorResult) == 0) {
        $alterUserColorSql = "ALTER TABLE users ADD COLUMN color VARCHAR(7) DEFAULT '#3788d8'";
        mysqli_query($conn, $alterUserColorSql);
    }

    // SEGUNDO: Crear tabla de citas si no existe
    $sql = "CREATE TABLE IF NOT EXISTS appointments (
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
    )";

    if (! mysqli_query($conn, $sql)) {
        error_log('Error al crear tabla de citas: '.mysqli_error($conn));
    }

    // Actualizar la tabla de citas existente si no tiene la columna calendar_type
    $checkColumn = "SHOW COLUMNS FROM appointments LIKE 'calendar_type'";
    $result = mysqli_query($conn, $checkColumn);
    if (mysqli_num_rows($result) == 0) {
        $alterSql = "ALTER TABLE appointments ADD COLUMN calendar_type ENUM('estetico', 'veterinario', 'general') DEFAULT 'estetico'";
        mysqli_query($conn, $alterSql);
    }

    // Actualizar la tabla de citas existente si no tiene la columna all_day
    $checkAllDayColumn = "SHOW COLUMNS FROM appointments LIKE 'all_day'";
    $allDayResult = mysqli_query($conn, $checkAllDayColumn);
    if (mysqli_num_rows($allDayResult) == 0) {
        $alterAllDaySql = 'ALTER TABLE appointments ADD COLUMN all_day TINYINT(1) NOT NULL DEFAULT 0';
        mysqli_query($conn, $alterAllDaySql);
    }

    // TERCERO: Crear tabla de notas si no existe
    $sql = "CREATE TABLE IF NOT EXISTS notes (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(100) NOT NULL,
        content TEXT NOT NULL,
        type ENUM('nota', 'sugerencia', 'otro') NOT NULL DEFAULT 'nota',
        visibility ENUM('solo_yo', 'todos') NOT NULL DEFAULT 'solo_yo',
        user_id INT(11) NOT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";

    if (! mysqli_query($conn, $sql)) {
        error_log('Error al crear tabla de notas: '.mysqli_error($conn));
    }

    // CUARTO: Crear tabla de configuraciones si no existe
    $sql = 'CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(255) NOT NULL PRIMARY KEY,
        setting_value TEXT NOT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )';

    if (! mysqli_query($conn, $sql)) {
        error_log('Error al crear tabla de configuraciones: '.mysqli_error($conn));
    }

    // QUINTO: Crear tabla de sesiones si no existe
    $sql = 'CREATE TABLE IF NOT EXISTS user_sessions (
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
    )';

    if (! mysqli_query($conn, $sql)) {
        error_log('Error al crear tabla de sesiones: '.mysqli_error($conn));
    }

    // SEXTO: Crear tabla de configuración de sesiones si no existe
    $sql = 'CREATE TABLE IF NOT EXISTS session_settings (
        setting_key VARCHAR(255) NOT NULL PRIMARY KEY,
        setting_value TEXT NOT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )';

    if (! mysqli_query($conn, $sql)) {
        error_log('Error al crear tabla de configuración de sesiones: '.mysqli_error($conn));
    }

    // Insertar configuración por defecto de sesiones si no existe
    $sql = "INSERT INTO session_settings (setting_key, setting_value)
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
            WHERE NOT EXISTS (SELECT 1 FROM session_settings WHERE setting_key = 'session_cleanup_interval')";

    mysqli_query($conn, $sql);

    // SÉPTIMO: Crear tabla de listas de difusión si no existe
    $sql = 'CREATE TABLE IF NOT EXISTS broadcast_lists (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )';

    if (! mysqli_query($conn, $sql)) {
        error_log('Error al crear tabla de listas de difusión: '.mysqli_error($conn));
    }

    // OCTAVO: Crear tabla de contactos si no existe
    $sql = 'CREATE TABLE IF NOT EXISTS contacts (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        email VARCHAR(255),
        notes TEXT,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_phone (phone)
    )';

    if (! mysqli_query($conn, $sql)) {
        error_log('Error al crear tabla de contactos: '.mysqli_error($conn));
    }

    // NOVENO: Crear tabla de relación contactos-listas si no existe
    $sql = 'CREATE TABLE IF NOT EXISTS broadcast_list_contacts (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        broadcast_list_id INT(11) NOT NULL,
        contact_id INT(11) NOT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (broadcast_list_id) REFERENCES broadcast_lists(id) ON DELETE CASCADE,
        FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
        UNIQUE KEY unique_list_contact (broadcast_list_id, contact_id)
    )';

    if (! mysqli_query($conn, $sql)) {
        error_log('Error al crear tabla de relación contactos-listas: '.mysqli_error($conn));
    }

    // DÉCIMO: Crear tabla de historial de difusiones si no existe
    $sql = "CREATE TABLE IF NOT EXISTS broadcast_history (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        broadcast_list_id INT(11) NOT NULL,
        message TEXT NOT NULL,
        image_path VARCHAR(500),
        status ENUM('pending', 'sending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
        total_contacts INT(11) NOT NULL DEFAULT 0,
        sent_contacts INT(11) NOT NULL DEFAULT 0,
        failed_contacts INT(11) NOT NULL DEFAULT 0,
        created_by INT(11) NOT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (broadcast_list_id) REFERENCES broadcast_lists(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    )";

    if (! mysqli_query($conn, $sql)) {
        error_log('Error al crear tabla de historial de difusiones: '.mysqli_error($conn));
    }

    // DÉCIMO PRIMERO: Crear tabla de detalles de envío si no existe
    $sql = "CREATE TABLE IF NOT EXISTS broadcast_details (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        broadcast_history_id INT(11) NOT NULL,
        contact_id INT(11) NOT NULL,
        status ENUM('pending', 'sent', 'delivered', 'failed') NOT NULL DEFAULT 'pending',
        error_message TEXT,
        sent_at TIMESTAMP NULL,
        delivered_at TIMESTAMP NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (broadcast_history_id) REFERENCES broadcast_history(id) ON DELETE CASCADE,
        FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE
    )";

    if (! mysqli_query($conn, $sql)) {
        error_log('Error al crear tabla de detalles de envío: '.mysqli_error($conn));
    }

    // Insertar usuario administrador por defecto si no existe
    $checkAdmin = "SELECT id FROM users WHERE email = 'admin@example.com'";
    $adminResult = mysqli_query($conn, $checkAdmin);
    if (mysqli_num_rows($adminResult) == 0) {
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $insertAdmin = "INSERT INTO users (email, password, name, role) VALUES ('admin@example.com', '$adminPassword', 'Administrador', 'admin')";
        mysqli_query($conn, $insertAdmin);
        error_log('Usuario administrador creado por defecto');
    }

    // Insertar usuario normal por defecto si no existe
    $checkUser = "SELECT id FROM users WHERE email = 'user@example.com'";
    $userResult = mysqli_query($conn, $checkUser);
    if (mysqli_num_rows($userResult) == 0) {
        $userPassword = password_hash('user123', PASSWORD_DEFAULT);
        $insertUser = "INSERT INTO users (email, password, name, role) VALUES ('user@example.com', '$userPassword', 'Usuario', 'user')";
        mysqli_query($conn, $insertUser);
        error_log('Usuario normal creado por defecto');
    }

} else {
    error_log('Error al crear la base de datos: '.mysqli_error($conn));
}
