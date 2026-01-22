<?php
require_once __DIR__.'/../config/database.php';

echo '<h1>Eliminación de Tablas de Difusiones</h1>';

// Verificar que el usuario esté autenticado y sea administrador
require_once __DIR__.'/../includes/auth.php';
requireAuth();

$currentUser = getCurrentUser();
if ($currentUser['role'] !== 'admin') {
    echo "<p style='color: red;'>❌ Solo los administradores pueden ejecutar este script.</p>";
    exit;
}

// Tablas a eliminar (en orden para evitar problemas de foreign keys)
$tablesToDrop = [
    'broadcast_details',
    'broadcast_history',
    'broadcast_list_contacts',
    'broadcast_lists',
    'contacts',
];

echo '<h2>Eliminando tablas de difusiones...</h2>';

$successCount = 0;
$errorCount = 0;

foreach ($tablesToDrop as $table) {
    $sql = "DROP TABLE IF EXISTS $table";
    if (mysqli_query($conn, $sql)) {
        echo "<p style='color: green;'>✅ Tabla '$table' eliminada correctamente</p>";
        $successCount++;
    } else {
        echo "<p style='color: red;'>❌ Error al eliminar tabla '$table': ".mysqli_error($conn).'</p>';
        $errorCount++;
    }
}

// Eliminar configuraciones relacionadas con Evolution API
$settingsToRemove = [
    'evolution_api_url',
    'evolution_api_key',
    'selected_evolution_instance',
    'evolution_instance_name',
];

echo '<h2>Eliminando configuraciones de Evolution API...</h2>';

foreach ($settingsToRemove as $setting) {
    $sql = 'DELETE FROM settings WHERE setting_key = ?';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $setting);

    if (mysqli_stmt_execute($stmt)) {
        echo "<p style='color: green;'>✅ Configuración '$setting' eliminada</p>";
        $successCount++;
    } else {
        echo "<p style='color: red;'>❌ Error al eliminar configuración '$setting': ".mysqli_error($conn).'</p>';
        $errorCount++;
    }
}

echo '<h2>Resumen</h2>';
echo "<p>✅ Operaciones exitosas: $successCount</p>";
echo "<p>❌ Errores: $errorCount</p>";

if ($errorCount === 0) {
    echo "<p style='color: green; font-weight: bold;'>🎉 Todas las tablas y configuraciones de difusiones han sido eliminadas correctamente.</p>";
} else {
    echo "<p style='color: orange; font-weight: bold;'>⚠️ Se completaron algunas operaciones con errores. Revisa los mensajes anteriores.</p>";
}

echo "<p><a href='../index.php'>← Volver al inicio</a></p>";
?> 