<?php
// Configuración de la base de datos - DETECCIÓN AUTOMÁTICA DE ENTORNO

// Detectar si estamos en producción o desarrollo
$isProduction = false;

// Verificar si estamos en producción (por variables de entorno o archivo específico)
if (getenv('ENVIRONMENT') === 'production' ||
    getenv('DB_HOST_PROD') ||
    file_exists(__DIR__.'/database.prod.php')) {
    $isProduction = true;
}

// Cargar configuración según el entorno
if ($isProduction) {
    // En producción, usar configuración de producción
    require_once __DIR__.'/database.prod.php';
} else {
    // En desarrollo local, usar configuración local
    require_once __DIR__.'/database.local.php';
}

// La variable $conn ya está definida por el archivo incluido
// No se necesita crear tablas aquí, ya se hace en database.local.php
?> 