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
    
    // Crear tabla de citas si no existe
    $sql = "CREATE TABLE IF NOT EXISTS appointments (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        start_time DATETIME NOT NULL,
        end_time DATETIME NOT NULL,
        calendar_type ENUM('estetico', 'veterinario', 'general') DEFAULT 'estetico',
        all_day TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
    
    // Crear tabla de usuarios si no existe
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(255) NOT NULL,
        role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
        history TEXT,
        reset_token VARCHAR(255) DEFAULT NULL,
        reset_token_expiry DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (!mysqli_query($conn, $sql)) {
        echo "Error al crear tabla de usuarios: " . mysqli_error($conn);
    }
    
    // Crear tabla de configuraciones si no existe
    $sql = "CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(255) NOT NULL PRIMARY KEY,
        setting_value TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if (!mysqli_query($conn, $sql)) {
        echo "Error al crear tabla de notas: " . mysqli_error($conn);
    }
} else {
    echo "Error al crear base de datos: " . mysqli_error($conn);
}
?> 