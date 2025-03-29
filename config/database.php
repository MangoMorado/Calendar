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
        reset_token VARCHAR(255) DEFAULT NULL,
        reset_token_expiry DATETIME DEFAULT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (!mysqli_query($conn, $sql)) {
        echo "Error al crear tabla de usuarios: " . mysqli_error($conn);
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
        echo "Error al crear tabla de citas: " . mysqli_error($conn);
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
        echo "Error al crear tabla de configuraciones: " . mysqli_error($conn);
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
        echo "Error al crear tabla de notas: " . mysqli_error($conn);
    }
} else {
    echo "Error al crear base de datos: " . mysqli_error($conn);
}
?> 