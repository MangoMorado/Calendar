<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

echo "<h2>Creación de Sesiones Permanentes (Expiran en 2080)</h2>";

// Usuarios específicos para crear sesiones permanentes
$userIds = [1, 4, 5, 6];

echo "<h3>1. Verificando usuarios existentes:</h3>";
$users = "SELECT id, name, email, role FROM users WHERE id IN (" . implode(',', $userIds) . ") ORDER BY id";
$result = mysqli_query($conn, $users);

$existingUsers = [];
if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        $existingUsers[] = $row;
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
        echo "<td>✅ Usuario encontrado</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ No se encontraron usuarios con los IDs especificados<br>";
    exit;
}

// Limpiar sesiones existentes de estos usuarios
echo "<h3>2. Limpiando sesiones existentes de estos usuarios:</h3>";
$cleanupSql = "DELETE FROM user_sessions WHERE user_id IN (" . implode(',', $userIds) . ")";
$result = mysqli_query($conn, $cleanupSql);
$deletedCount = mysqli_affected_rows($conn);
echo "✅ Sesiones eliminadas: $deletedCount<br>";

// Crear sesiones permanentes
echo "<h3>3. Creando sesiones permanentes:</h3>";

$createdSessions = [];
$errors = [];

foreach ($existingUsers as $user) {
    // Generar ID de sesión único
    $sessionId = bin2hex(random_bytes(32));
    
    // Fecha de expiración: 1 de enero de 2080
    $expiresAt = '2080-01-01 00:00:00';
    
    // Obtener información del dispositivo (simulada para línea de comandos)
    $ipAddress = '127.0.0.1'; // IP local para desarrollo
    $userAgent = 'Permanent Session Script';
    $deviceInfo = 'Desarrollo';
    $rememberMe = 1; // Marcar como "recordar equipo"
    
    // Insertar sesión permanente
    $sql = "INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, device_info, remember_me, expires_at, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "issssis", $user['id'], $sessionId, $ipAddress, $userAgent, $deviceInfo, $rememberMe, $expiresAt);
    
    if (mysqli_stmt_execute($stmt)) {
        $createdSessions[] = [
            'user' => $user,
            'session_id' => $sessionId,
            'expires_at' => $expiresAt
        ];
        echo "✅ Sesión creada para: " . htmlspecialchars($user['name']) . " (ID: " . $user['id'] . ")<br>";
    } else {
        $errors[] = "Error creando sesión para " . $user['name'] . ": " . mysqli_error($conn);
        echo "❌ Error creando sesión para: " . htmlspecialchars($user['name']) . "<br>";
    }
}

// Verificar sesiones creadas
echo "<h3>4. Verificando sesiones creadas:</h3>";
$verifySql = "SELECT us.*, u.name, u.email 
              FROM user_sessions us 
              JOIN users u ON us.user_id = u.id 
              WHERE us.user_id IN (" . implode(',', $userIds) . ") 
              ORDER BY us.user_id";
$result = mysqli_query($conn, $verifySql);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Usuario</th><th>Session ID</th><th>IP</th><th>Expira</th><th>Recordar</th><th>Estado</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['name']) . " (" . htmlspecialchars($row['email']) . ")</td>";
        echo "<td>" . htmlspecialchars(substr($row['session_id'], 0, 16)) . "...</td>";
        echo "<td>" . htmlspecialchars($row['ip_address']) . "</td>";
        echo "<td>" . htmlspecialchars($row['expires_at']) . "</td>";
        echo "<td>" . ($row['remember_me'] ? 'Sí' : 'No') . "</td>";
        echo "<td>" . ($row['is_active'] ? 'Activa' : 'Inactiva') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ No se encontraron sesiones creadas<br>";
}

// Crear script de autenticación automática
echo "<h3>5. Creando script de autenticación automática:</h3>";

$authScript = "<?php\n";
$authScript .= "// Script de autenticación automática para sesiones permanentes\n";
$authScript .= "// Ejecutar este script para autenticar automáticamente a los usuarios\n\n";
$authScript .= "require_once __DIR__ . '/../config/database.php';\n";
$authScript .= "require_once __DIR__ . '/../includes/auth.php';\n\n";

foreach ($createdSessions as $session) {
    $authScript .= "// Autenticar usuario: " . $session['user']['name'] . "\n";
    $authScript .= "\$userData" . $session['user']['id'] . " = [\n";
    $authScript .= "    'id' => " . $session['user']['id'] . ",\n";
    $authScript .= "    'name' => '" . addslashes($session['user']['name']) . "',\n";
    $authScript .= "    'email' => '" . addslashes($session['user']['email']) . "',\n";
    $authScript .= "    'role' => '" . addslashes($session['user']['role']) . "'\n";
    $authScript .= "];\n\n";
    
    $authScript .= "if (session_status() == PHP_SESSION_NONE) {\n";
    $authScript .= "    session_start();\n";
    $authScript .= "}\n\n";
    
    $authScript .= "\$_SESSION['user'] = \$userData" . $session['user']['id'] . ";\n";
    $authScript .= "echo '✅ Usuario autenticado: " . addslashes($session['user']['name']) . "\\n';\n\n";
}

$authScript .= "echo '✅ Todos los usuarios están autenticados\\n';\n";
$authScript .= "echo 'Ahora puedes probar el endpoint api/import_contacts.php\\n';\n";
$authScript .= "?>";

file_put_contents('auto_auth_permanent.php', $authScript);
echo "✅ Script de autenticación automática creado: auto_auth_permanent.php<br>";

// Resumen final
echo "<h3>6. Resumen de la operación:</h3>";
echo "✅ Usuarios procesados: " . count($existingUsers) . "<br>";
echo "✅ Sesiones permanentes creadas: " . count($createdSessions) . "<br>";
echo "✅ Sesiones eliminadas: $deletedCount<br>";
if (count($errors) > 0) {
    echo "❌ Errores encontrados: " . count($errors) . "<br>";
} else {
    echo "✅ No se encontraron errores<br>";
}

echo "<h3>7. Instrucciones de uso:</h3>";
echo "1. Las sesiones permanentes están creadas y expiran en 2080<br>";
echo "2. Para autenticar automáticamente, ejecuta: <code>php auto_auth_permanent.php</code><br>";
echo "3. Después de autenticar, puedes probar el endpoint import_contacts.php<br>";
echo "4. Estas sesiones son permanentes y no expirarán hasta 2080<br>";

echo "<h3>8. Usuarios con sesiones permanentes:</h3>";
foreach ($createdSessions as $session) {
    echo "- " . htmlspecialchars($session['user']['name']) . " (ID: " . $session['user']['id'] . ")<br>";
}

echo "<h3>9. Próximos pasos:</h3>";
echo "1. Ejecuta: <code>php auto_auth_permanent.php</code><br>";
echo "2. Luego ejecuta: <code>php test_endpoint_fixed.php</code><br>";
echo "3. Verifica que el endpoint funciona correctamente<br>";
?> 