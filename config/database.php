<?php
// Configuración de la base de datos
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'calendar_app');

// Conexión a la base de datos
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Verificar conexión
if (!$conn) {
    error_log("Error en la conexión: " . mysqli_connect_error());
    die("Error en la conexión: " . mysqli_connect_error());
}

// Crear la base de datos si no existe
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
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
    
    if (!mysqli_query($conn, $sql)) {
        error_log("Error al crear tabla de usuarios: " . mysqli_error($conn));
    }

    // Verificar si la tabla users ya tiene la columna calendar_visible
    $checkCalendarVisibleColumn = "SHOW COLUMNS FROM users LIKE 'calendar_visible'";
    $calendarVisibleResult = mysqli_query($conn, $checkCalendarVisibleColumn);
    if (mysqli_num_rows($calendarVisibleResult) == 0) {
        $alterCalendarVisibleSql = "ALTER TABLE users ADD COLUMN calendar_visible TINYINT(1) NOT NULL DEFAULT 1";
        $result = mysqli_query($conn, $alterCalendarVisibleSql);
        if ($result) {
            error_log("Columna calendar_visible añadida a la tabla users correctamente");
        } else {
            error_log("Error al añadir la columna calendar_visible: " . mysqli_error($conn));
        }
    } else {
        error_log("La columna calendar_visible ya existe en la tabla users");
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
    
    if (!mysqli_query($conn, $sql)) {
        error_log("Error al crear tabla de citas: " . mysqli_error($conn));
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
        $alterAllDaySql = "ALTER TABLE appointments ADD COLUMN all_day TINYINT(1) NOT NULL DEFAULT 0";
        mysqli_query($conn, $alterAllDaySql);
    }
    
    // Verificar si la tabla appointments ya tiene la columna user_id
    $checkUserIdColumn = "SHOW COLUMNS FROM appointments LIKE 'user_id'";
    $userIdResult = mysqli_query($conn, $checkUserIdColumn);
    if (mysqli_num_rows($userIdResult) == 0) {
        $alterUserIdSql = "ALTER TABLE appointments ADD COLUMN user_id INT(11) DEFAULT NULL, ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL";
        mysqli_query($conn, $alterUserIdSql);
    }
    
    // Agregar verificación para la columna color
    $checkColorColumn = "SHOW COLUMNS FROM appointments LIKE 'color'";
    $colorResult = mysqli_query($conn, $checkColorColumn);
    if (mysqli_num_rows($colorResult) == 0) {
        $alterColorSql = "ALTER TABLE appointments ADD COLUMN color VARCHAR(7) DEFAULT '#3788d8'";
        mysqli_query($conn, $alterColorSql);
    }
    
    // Crear tabla de configuraciones si no existe
    $sql = "CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(255) NOT NULL PRIMARY KEY,
        setting_value TEXT NOT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (!mysqli_query($conn, $sql)) {
        error_log("Error al crear tabla de configuraciones: " . mysqli_error($conn));
    }
    
    // Crear tabla de notas si no existe
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
    
    if (!mysqli_query($conn, $sql)) {
        error_log("Error al crear tabla de notas: " . mysqli_error($conn));
    }
    
    // Crear tabla de sesiones si no existe
    $sql = "CREATE TABLE IF NOT EXISTS user_sessions (
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
    )";
    
    if (!mysqli_query($conn, $sql)) {
        error_log("Error al crear tabla de sesiones: " . mysqli_error($conn));
    }
    
    // Crear tabla de configuración de sesiones si no existe
    $sql = "CREATE TABLE IF NOT EXISTS session_settings (
        setting_key VARCHAR(255) NOT NULL PRIMARY KEY,
        setting_value TEXT NOT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (!mysqli_query($conn, $sql)) {
        error_log("Error al crear tabla de configuración de sesiones: " . mysqli_error($conn));
    }
    
    // Insertar configuración por defecto de sesiones si no existe
    $defaultSettings = [
        'session_timeout' => '3600', // 1 hora en segundos
        'remember_me_timeout' => '604800', // 7 días en segundos
        'max_sessions_per_user' => '5',
        'require_login_on_visit' => '1', // 1 = sí, 0 = no
        'session_cleanup_interval' => '86400' // 24 horas en segundos
    ];
    
    foreach ($defaultSettings as $key => $value) {
        $checkSql = "SELECT COUNT(*) as count FROM session_settings WHERE setting_key = ?";
        $checkStmt = mysqli_prepare($conn, $checkSql);
        mysqli_stmt_bind_param($checkStmt, "s", $key);
        mysqli_stmt_execute($checkStmt);
        $result = mysqli_stmt_get_result($checkStmt);
        $row = mysqli_fetch_assoc($result);
        
        if ($row['count'] == 0) {
            $insertSql = "INSERT INTO session_settings (setting_key, setting_value) VALUES (?, ?)";
            $insertStmt = mysqli_prepare($conn, $insertSql);
            mysqli_stmt_bind_param($insertStmt, "ss", $key, $value);
            mysqli_stmt_execute($insertStmt);
        }
    }

    // Crear tabla de contactos para difusiones Evolution API
    $sql = "CREATE TABLE IF NOT EXISTS contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        number VARCHAR(50) NOT NULL UNIQUE,
        pushName VARCHAR(255) DEFAULT NULL,
        user_id INT(11) DEFAULT NULL,
        send BOOLEAN NOT NULL DEFAULT FALSE,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_number (number)
    )";
    if (!mysqli_query($conn, $sql)) {
        error_log("Error al crear tabla de contactos: " . mysqli_error($conn));
    }

    // Crear tabla de listas de difusión
    $sql = "CREATE TABLE IF NOT EXISTS broadcast_lists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        user_id INT(11) NOT NULL,
        is_active BOOLEAN NOT NULL DEFAULT TRUE,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_is_active (is_active)
    )";
    if (!mysqli_query($conn, $sql)) {
        error_log("Error al crear tabla de listas de difusión: " . mysqli_error($conn));
    }

    // Crear tabla de contactos en listas de difusión (relación muchos a muchos)
    $sql = "CREATE TABLE IF NOT EXISTS broadcast_list_contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        list_id INT NOT NULL,
        contact_id INT NOT NULL,
        added_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (list_id) REFERENCES broadcast_lists(id) ON DELETE CASCADE,
        FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
        UNIQUE KEY unique_list_contact (list_id, contact_id),
        INDEX idx_list_id (list_id),
        INDEX idx_contact_id (contact_id)
    )";
    if (!mysqli_query($conn, $sql)) {
        error_log("Error al crear tabla de contactos en listas: " . mysqli_error($conn));
    }

    // Crear tabla de historial de difusiones enviadas
    $sql = "CREATE TABLE IF NOT EXISTS broadcast_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        list_id INT,
        message TEXT NOT NULL,
        image_path VARCHAR(500),
        total_contacts INT NOT NULL DEFAULT 0,
        sent_successfully INT NOT NULL DEFAULT 0,
        sent_failed INT NOT NULL DEFAULT 0,
        user_id INT(11) NOT NULL,
        status ENUM('pending', 'in_progress', 'completed', 'failed') NOT NULL DEFAULT 'pending',
        started_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        FOREIGN KEY (list_id) REFERENCES broadcast_lists(id) ON DELETE SET NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_status (status),
        INDEX idx_started_at (started_at)
    )";
    if (!mysqli_query($conn, $sql)) {
        error_log("Error al crear tabla de historial de difusiones: " . mysqli_error($conn));
    }

    // Crear tabla de detalles de envío de difusiones
    $sql = "CREATE TABLE IF NOT EXISTS broadcast_details (
        id INT AUTO_INCREMENT PRIMARY KEY,
        broadcast_id INT NOT NULL,
        contact_id INT NOT NULL,
        contact_number VARCHAR(50) NOT NULL,
        status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
        error_message TEXT,
        sent_at TIMESTAMP NULL,
        FOREIGN KEY (broadcast_id) REFERENCES broadcast_history(id) ON DELETE CASCADE,
        FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
        INDEX idx_broadcast_id (broadcast_id),
        INDEX idx_contact_id (contact_id),
        INDEX idx_status (status)
    )";
    if (!mysqli_query($conn, $sql)) {
        error_log("Error al crear tabla de detalles de difusiones: " . mysqli_error($conn));
    }
} else {
    error_log("Error al crear base de datos: " . mysqli_error($conn));
}
?> 