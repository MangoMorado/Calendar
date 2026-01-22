<?php
require_once __DIR__.'/../config/database.php';

echo "Verificando tablas de difusión...\n";

$tables = ['broadcast_lists', 'broadcast_history', 'broadcast_details'];

foreach ($tables as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        echo "✅ Tabla '$table' existe\n";
    } else {
        echo "❌ Tabla '$table' NO existe\n";
    }
}

echo "Verificación completada.\n";
?> 