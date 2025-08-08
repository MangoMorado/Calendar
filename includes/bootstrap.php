<?php
// Bootstrap común para API y páginas

// Conexión a base de datos
require_once __DIR__ . '/../config/database.php';

// Zona horaria desde settings
$timezone = 'America/Bogota';
if (isset($conn)) {
    $sqlTz = "SELECT setting_value FROM settings WHERE setting_key = 'timezone' LIMIT 1";
    if ($resTz = mysqli_query($conn, $sqlTz)) {
        if ($rowTz = mysqli_fetch_assoc($resTz)) {
            $timezone = $rowTz['setting_value'];
        }
    }
}
date_default_timezone_set($timezone);

// Helper para respuestas JSON unificadas en API (si no se usa apiResponse de JWT)
if (!function_exists('json_response')) {
    function json_response($success, $message, $data = null, $statusCode = 200) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode([
            'success' => (bool)$success,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }
}

// Para páginas: manejo de sesión centralizado (no inicia si ya existe)
if (php_sapi_name() !== 'cli') {
    require_once __DIR__ . '/session_config.php';
    if (session_status() === PHP_SESSION_NONE) {
        configureDefaultSession();
        session_start();
    }
}


