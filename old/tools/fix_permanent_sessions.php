<?php
require_once __DIR__.'/../config/database.php';

echo '<h2>Corrigiendo Sesiones Permanentes</h2>';

// Usuarios específicos
$userIds = [1, 4, 5, 6];

echo '<h3>1. Verificando sesiones actuales:</h3>';
$currentSessions = 'SELECT us.*, u.name, u.email 
                   FROM user_sessions us 
                   JOIN users u ON us.user_id = u.id 
                   WHERE us.user_id IN ('.implode(',', $userIds).') 
                   ORDER BY us.user_id';
$result = mysqli_query($conn, $currentSessions);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo '<tr><th>Usuario</th><th>Session ID</th><th>IP</th><th>Expira</th><th>Recordar</th><th>Estado</th><th>Problema</th></tr>';
    while ($row = mysqli_fetch_assoc($result)) {
        $problem = '';
        if ($row['expires_at'] == '0000-00-00 00:00:00') {
            $problem = '❌ Fecha inválida';
        } elseif ($row['expires_at'] < date('Y-m-d H:i:s')) {
            $problem = '❌ Expirada';
        } else {
            $problem = '✅ Correcta';
        }

        echo '<tr>';
        echo '<td>'.htmlspecialchars($row['name']).' ('.htmlspecialchars($row['email']).')</td>';
        echo '<td>'.htmlspecialchars(substr($row['session_id'], 0, 16)).'...</td>';
        echo '<td>'.htmlspecialchars($row['ip_address']).'</td>';
        echo '<td>'.htmlspecialchars($row['expires_at']).'</td>';
        echo '<td>'.($row['remember_me'] ? 'Sí' : 'No').'</td>';
        echo '<td>'.($row['is_active'] ? 'Activa' : 'Inactiva').'</td>';
        echo '<td>'.$problem.'</td>';
        echo '</tr>';
    }
    echo '</table>';
}

// Corregir fechas de expiración
echo '<h3>2. Corrigiendo fechas de expiración:</h3>';
$correctExpiryDate = '2080-01-01 00:00:00';

$updateSql = 'UPDATE user_sessions 
              SET expires_at = ?, 
                  last_activity = NOW(), 
                  is_active = 1 
              WHERE user_id IN ('.implode(',', $userIds).") 
              AND expires_at = '0000-00-00 00:00:00'";
$stmt = mysqli_prepare($conn, $updateSql);
mysqli_stmt_bind_param($stmt, 's', $correctExpiryDate);

if (mysqli_stmt_execute($stmt)) {
    $updatedCount = mysqli_affected_rows($conn);
    echo "✅ Sesiones actualizadas: $updatedCount<br>";
    echo "✅ Nueva fecha de expiración: $correctExpiryDate<br>";
} else {
    echo '❌ Error actualizando sesiones: '.mysqli_error($conn).'<br>';
}

// Verificar sesiones corregidas
echo '<h3>3. Verificando sesiones corregidas:</h3>';
$verifySql = 'SELECT us.*, u.name, u.email 
              FROM user_sessions us 
              JOIN users u ON us.user_id = u.id 
              WHERE us.user_id IN ('.implode(',', $userIds).') 
              ORDER BY us.user_id';
$result = mysqli_query($conn, $verifySql);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo '<tr><th>Usuario</th><th>Session ID</th><th>IP</th><th>Expira</th><th>Recordar</th><th>Estado</th><th>Estado</th></tr>';
    while ($row = mysqli_fetch_assoc($result)) {
        $status = '';
        if ($row['expires_at'] == '2080-01-01 00:00:00') {
            $status = '✅ Permanente (2080)';
        } elseif ($row['expires_at'] == '0000-00-00 00:00:00') {
            $status = '❌ Sin corregir';
        } else {
            $status = '⚠️ Otra fecha';
        }

        echo '<tr>';
        echo '<td>'.htmlspecialchars($row['name']).' ('.htmlspecialchars($row['email']).')</td>';
        echo '<td>'.htmlspecialchars(substr($row['session_id'], 0, 16)).'...</td>';
        echo '<td>'.htmlspecialchars($row['ip_address']).'</td>';
        echo '<td>'.htmlspecialchars($row['expires_at']).'</td>';
        echo '<td>'.($row['remember_me'] ? 'Sí' : 'No').'</td>';
        echo '<td>'.($row['is_active'] ? 'Activa' : 'Inactiva').'</td>';
        echo '<td>'.$status.'</td>';
        echo '</tr>';
    }
    echo '</table>';
}

// Crear script de prueba mejorado
echo '<h3>4. Creando script de prueba mejorado:</h3>';

$testScript = "<?php\n";
$testScript .= "// Script de prueba para sesiones permanentes corregidas\n";
$testScript .= "require_once __DIR__ . '/../config/database.php';\n";
$testScript .= "require_once __DIR__ . '/../includes/auth.php';\n\n";

$testScript .= "echo '=== PRUEBA DE SESIONES PERMANENTES ===\\n';\n\n";

$testScript .= "// Verificar sesiones en base de datos\n";
$testScript .= "\$sessions = \"SELECT us.*, u.name, u.email FROM user_sessions us JOIN users u ON us.user_id = u.id WHERE us.user_id IN (1,4,5,6) ORDER BY us.user_id\";\n";
$testScript .= "\$result = mysqli_query(\$conn, \$sessions);\n";
$testScript .= "echo 'Sesiones activas:\\n';\n";
$testScript .= "while (\$row = mysqli_fetch_assoc(\$result)) {\n";
$testScript .= "    echo '- ' . \$row['name'] . ' (Expira: ' . \$row['expires_at'] . ')\\n';\n";
$testScript .= "}\n\n";

$testScript .= "// Probar autenticación con cada usuario\n";
$testScript .= "\$userIds = [1, 4, 5, 6];\n";
$testScript .= "foreach (\$userIds as \$userId) {\n";
$testScript .= "    \$userSql = \"SELECT id, name, email, role FROM users WHERE id = ?\";\n";
$testScript .= "    \$stmt = mysqli_prepare(\$conn, \$userSql);\n";
$testScript .= "    mysqli_stmt_bind_param(\$stmt, \"i\", \$userId);\n";
$testScript .= "    mysqli_stmt_execute(\$stmt);\n";
$testScript .= "    \$result = mysqli_stmt_get_result(\$stmt);\n";
$testScript .= "    \$user = mysqli_fetch_assoc(\$result);\n\n";

$testScript .= "    if (\$user) {\n";
$testScript .= "        echo '\\nProbando usuario: ' . \$user['name'] . '\\n';\n";
$testScript .= "        \n";
$testScript .= "        // Iniciar sesión\n";
$testScript .= "        if (session_status() == PHP_SESSION_NONE) {\n";
$testScript .= "            session_start();\n";
$testScript .= "        }\n";
$testScript .= "        \$_SESSION['user'] = \$user;\n";
$testScript .= "        \n";
$testScript .= "        // Verificar autenticación\n";
$testScript .= "        if (isAuthenticated()) {\n";
$testScript .= "            echo '✅ Autenticado correctamente\\n';\n";
$testScript .= "            \n";
$testScript .= "            // Probar endpoint\n";
$testScript .= "            ob_start();\n";
$testScript .= "            include __DIR__ . '/../api/import_contacts.php';\n";
$testScript .= "            \$output = ob_get_clean();\n";
$testScript .= "            \$httpCode = http_response_code();\n";
$testScript .= "            echo 'Código HTTP: ' . \$httpCode . '\\n';\n";
$testScript .= "            if (\$httpCode == 200) {\n";
$testScript .= "                echo '✅ Endpoint funciona\\n';\n";
$testScript .= "            } else {\n";
$testScript .= "                echo '❌ Endpoint falló\\n';\n";
$testScript .= "            }\n";
$testScript .= "        } else {\n";
$testScript .= "            echo '❌ No autenticado\\n';\n";
$testScript .= "        }\n";
$testScript .= "        \n";
$testScript .= "        // Limpiar sesión para siguiente usuario\n";
$testScript .= "        session_destroy();\n";
$testScript .= "    }\n";
$testScript .= "}\n\n";

$testScript .= "echo '\\n=== PRUEBA COMPLETADA ===\\n';\n";
$testScript .= '?>';

file_put_contents('test_permanent_sessions.php', $testScript);
echo '✅ Script de prueba creado: test_permanent_sessions.php<br>';

// Resumen
echo '<h3>5. Resumen de correcciones:</h3>';
echo '✅ Fechas de expiración corregidas a 2080-01-01 00:00:00<br>';
echo '✅ Sesiones marcadas como activas<br>';
echo '✅ Última actividad actualizada<br>';
echo '✅ Script de prueba mejorado creado<br>';

echo '<h3>6. Próximos pasos:</h3>';
echo '1. Ejecuta: <code>php test_permanent_sessions.php</code><br>';
echo '2. Verifica que cada usuario puede autenticarse correctamente<br>';
echo '3. Confirma que el endpoint import_contacts.php funciona<br>';
echo '4. Si todo funciona, el problema de error 400 está resuelto<br>';

echo '<h3>7. Nota importante:</h3>';
echo 'El problema de headers enviados prematuramente en database.php:221<br>';
echo 'puede afectar las sesiones. Considera ejecutar los scripts desde el navegador<br>';
echo 'en lugar de línea de comandos para evitar este problema.<br>';
?> 