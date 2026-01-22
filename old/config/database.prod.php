<?php

// Configuración de la base de datos PRODUCCIÓN
// Este archivo será usado por GitHub Actions durante el despliegue

// Obtener configuración de variables de entorno (establecidas por GitHub Actions)
$dbServer = getenv('DB_HOST_PROD') ?: 'localhost';
$dbUsername = getenv('DB_USER_PROD') ?: 'root';
$dbPassword = getenv('DB_PASS_PROD') ?: '';
$dbName = getenv('DB_NAME_PROD') ?: 'calendar_app';

// Definir constantes
define('DB_SERVER', $dbServer);
define('DB_USERNAME', $dbUsername);
define('DB_PASSWORD', $dbPassword);
define('DB_NAME', $dbName);

// Conexión a la base de datos
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Verificar conexión
if (! $conn) {
    error_log('Error en la conexión de producción: '.mysqli_connect_error());
    exit('Error en la conexión de producción: '.mysqli_connect_error());
}

// Seleccionar la base de datos
if (! mysqli_select_db($conn, DB_NAME)) {
    error_log('Error al seleccionar base de datos de producción: '.mysqli_error($conn));
    exit('Error al seleccionar base de datos de producción: '.mysqli_error($conn));
}

// Log de conexión exitosa (solo en desarrollo)
if (getenv('ENVIRONMENT') === 'development') {
    error_log('Conexión a base de datos de producción establecida correctamente');
}
