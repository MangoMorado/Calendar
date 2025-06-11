<?php
/**
 * Script de Limpieza Automática de Sesiones
 * Este script debe ejecutarse periódicamente como cron job para limpiar sesiones expiradas
 * 
 * Ejemplo de cron job (cada hora):
 * 0 * * * * /usr/bin/php /ruta/a/tu/proyecto/cron/cleanup_sessions.php
 * 
 * Ejemplo de cron job (cada día a las 2 AM):
 * 0 2 * * * /usr/bin/php /ruta/a/tu/proyecto/cron/cleanup_sessions.php
 */

// Configurar zona horaria desde la base de datos
$timezone = 'America/Bogota';
require_once __DIR__ . '/../config/database.php';
$sql = "SELECT setting_value FROM settings WHERE setting_key = 'timezone' LIMIT 1";
$result = mysqli_query($conn, $sql);
if ($result && $row = mysqli_fetch_assoc($result)) {
    $timezone = $row['setting_value'];
}
date_default_timezone_set($timezone);

// Incluir archivos necesarios
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session_manager.php';

// Inicializar el gestor de sesiones
$sessionManager = new SessionManager($conn);

// Log de inicio
$startTime = microtime(true);
$logMessage = "[" . date('Y-m-d H:i:s') . "] Iniciando limpieza de sesiones...\n";
echo $logMessage;

// Obtener estadísticas antes de la limpieza
$sql = "SELECT 
            COUNT(*) as total_sessions,
            COUNT(CASE WHEN expires_at <= NOW() THEN 1 END) as expired_sessions,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_sessions
        FROM user_sessions";
$result = mysqli_query($conn, $sql);
$statsBefore = mysqli_fetch_assoc($result);

$logMessage = "[" . date('Y-m-d H:i:s') . "] Estadísticas antes de la limpieza:\n";
$logMessage .= "  - Sesiones totales: " . $statsBefore['total_sessions'] . "\n";
$logMessage .= "  - Sesiones expiradas: " . $statsBefore['expired_sessions'] . "\n";
$logMessage .= "  - Sesiones activas: " . $statsBefore['active_sessions'] . "\n";
echo $logMessage;

// Ejecutar limpieza de sesiones expiradas
$cleanupResult = $sessionManager->cleanupExpiredSessions();

if ($cleanupResult) {
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Limpieza de sesiones expiradas completada.\n";
} else {
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Error durante la limpieza de sesiones expiradas.\n";
}
echo $logMessage;

// Obtener estadísticas después de la limpieza
$result = mysqli_query($conn, $sql);
$statsAfter = mysqli_fetch_assoc($result);

$logMessage = "[" . date('Y-m-d H:i:s') . "] Estadísticas después de la limpieza:\n";
$logMessage .= "  - Sesiones totales: " . $statsAfter['total_sessions'] . "\n";
$logMessage .= "  - Sesiones expiradas: " . $statsAfter['expired_sessions'] . "\n";
$logMessage .= "  - Sesiones activas: " . $statsAfter['active_sessions'] . "\n";
echo $logMessage;

// Calcular sesiones limpiadas
$sessionsCleaned = $statsBefore['expired_sessions'] - $statsAfter['expired_sessions'];
$logMessage = "[" . date('Y-m-d H:i:s') . "] Sesiones limpiadas: $sessionsCleaned\n";
echo $logMessage;

// Limpieza adicional: eliminar sesiones inactivas muy antiguas (más de 30 días)
$sql = "UPDATE user_sessions SET is_active = 0 
        WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 DAY) 
        AND is_active = 1";
$result = mysqli_query($conn, $sql);

if ($result) {
    $oldSessionsCleaned = mysqli_affected_rows($conn);
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Sesiones antiguas limpiadas: $oldSessionsCleaned\n";
    echo $logMessage;
}

// Actualizar timestamp de última limpieza
$sessionManager->updateSetting('last_cleanup', time());

// Calcular tiempo de ejecución
$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 4);

$logMessage = "[" . date('Y-m-d H:i:s') . "] Limpieza completada en {$executionTime} segundos.\n";
$logMessage .= "[" . date('Y-m-d H:i:s') . "] ----------------------------------------\n";
echo $logMessage;

// Opcional: enviar notificación por email si hay muchas sesiones limpiadas
if ($sessionsCleaned > 100) {
    $subject = "Limpieza de Sesiones - Mundo Animal";
    $message = "Se han limpiado $sessionsCleaned sesiones expiradas en el sistema.\n";
    $message .= "Fecha: " . date('Y-m-d H:i:s') . "\n";
    $message .= "Tiempo de ejecución: {$executionTime} segundos\n";
    
    // Descomentar y configurar si quieres enviar emails
    // mail('admin@mundanimal.com', $subject, $message);
}

exit(0);
?> 