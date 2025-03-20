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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (!mysqli_query($conn, $sql)) {
        echo "Error al crear tabla: " . mysqli_error($conn);
    }
} else {
    echo "Error al crear base de datos: " . mysqli_error($conn);
}
?> 