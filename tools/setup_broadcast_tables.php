<?php
/**
 * Script para configurar las tablas de difusión
 * Ejecutar este script una vez para crear las tablas necesarias
 */

// Incluir configuración de base de datos
require_once __DIR__ . '/../config/database.php';

echo "<h2>Configurando tablas de difusión...</h2>";

// Leer el archivo SQL
$sqlFile = 'config/create_broadcast_tables.sql';
if (!file_exists($sqlFile)) {
    die("Error: No se encontró el archivo $sqlFile");
}

$sqlContent = file_get_contents($sqlFile);

// Dividir el contenido en consultas individuales
$queries = array_filter(array_map('trim', explode(';', $sqlContent)));

$successCount = 0;
$errorCount = 0;

foreach ($queries as $query) {
    if (empty($query) || strpos($query, '--') === 0) {
        continue; // Saltar comentarios y líneas vacías
    }
    
    echo "<p>Ejecutando: " . substr($query, 0, 50) . "...</p>";
    
    if (mysqli_query($conn, $query)) {
        echo "<p style='color: green;'>✓ Éxito</p>";
        $successCount++;
    } else {
        echo "<p style='color: red;'>✗ Error: " . mysqli_error($conn) . "</p>";
        $errorCount++;
    }
}

echo "<hr>";
echo "<h3>Resumen:</h3>";
echo "<p>Consultas exitosas: <strong style='color: green;'>$successCount</strong></p>";
echo "<p>Errores: <strong style='color: red;'>$errorCount</strong></p>";

if ($errorCount === 0) {
    echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h4>✅ Configuración completada exitosamente</h4>";
    echo "<p>Las tablas de difusión han sido creadas correctamente. Ya puedes usar el sistema de difusiones.</p>";
    echo "</div>";
} else {
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h4>⚠️ Configuración completada con errores</h4>";
    echo "<p>Algunas tablas no pudieron ser creadas. Revisa los errores arriba.</p>";
    echo "</div>";
}

// Verificar que las tablas existen
echo "<hr>";
echo "<h3>Verificando tablas creadas:</h3>";

$requiredTables = [
    'broadcast_lists',
    'broadcast_list_contacts', 
    'broadcast_history',
    'broadcast_details'
];

foreach ($requiredTables as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        echo "<p style='color: green;'>✓ Tabla '$table' existe</p>";
    } else {
        echo "<p style='color: red;'>✗ Tabla '$table' NO existe</p>";
    }
}

echo "<hr>";
echo "<p><a href='broadcast_lists.php'>Ir a Listas de Difusión</a></p>";
?> 