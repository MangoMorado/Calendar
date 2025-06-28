<?php
/**
 * Script para configurar las tablas de difusión
 * Ejecutar este script una vez para crear las tablas necesarias
 */

// Incluir configuración de base de datos
require_once __DIR__ . '/../config/database.php';

echo "Configurando tablas de difusión...\n";

// Leer el archivo SQL
$sqlFile = __DIR__ . '/../config/create_broadcast_tables.sql';
if (!file_exists($sqlFile)) {
    echo "Error: No se encontró el archivo SQL\n";
    exit(1);
}

$sqlContent = file_get_contents($sqlFile);

// Dividir el contenido en consultas individuales
$queries = array_filter(array_map('trim', explode(';', $sqlContent)));

$successCount = 0;
$errorCount = 0;

foreach ($queries as $query) {
    if (empty($query)) continue;
    
    echo "Ejecutando: " . substr($query, 0, 50) . "...\n";
    
    if (mysqli_query($conn, $query)) {
        echo "✅ Éxito\n";
        $successCount++;
    } else {
        echo "❌ Error: " . mysqli_error($conn) . "\n";
        $errorCount++;
    }
}

echo "\nResumen:\n";
echo "Consultas exitosas: $successCount\n";
echo "Errores: $errorCount\n";

if ($errorCount === 0) {
    echo "✅ Todas las tablas de difusión han sido creadas correctamente.\n";
} else {
    echo "⚠️  Algunas consultas fallaron. Revisa los errores arriba.\n";
}

// Verificar que las tablas existen
echo "\nVerificando tablas creadas:\n";

$requiredTables = [
    'broadcast_lists',
    'broadcast_list_contacts', 
    'broadcast_history',
    'broadcast_details'
];

foreach ($requiredTables as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        echo "✓ Tabla '$table' existe\n";
    } else {
        echo "✗ Tabla '$table' NO existe\n";
    }
}

echo "\n<a href='broadcast_lists.php'>Ir a Listas de Difusión</a>\n";
?> 