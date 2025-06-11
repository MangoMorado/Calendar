<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

echo "<h2>Autenticación de Usuario Específico</h2>";

// Usuarios disponibles con sesiones permanentes
$userIds = [1, 4, 5, 6];

echo "<h3>1. Usuarios disponibles con sesiones permanentes:</h3>";
$users = "SELECT id, name, email, role FROM users WHERE id IN (" . implode(',', $userIds) . ") ORDER BY id";
$result = mysqli_query($conn, $users);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Obtener el ID del usuario a autenticar (por defecto el primero)
$userIdToAuth = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 1;

echo "<h3>2. Autenticando usuario ID: $userIdToAuth</h3>";

// Verificar que el usuario existe
$userSql = "SELECT id, name, email, role FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($stmt, "i", $userIdToAuth);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($user = mysqli_fetch_assoc($result)) {
    echo "✅ Usuario encontrado: " . htmlspecialchars($user['name']) . "<br>";
    
    // Iniciar sesión PHP
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Crear datos de usuario para la sesión
    $userData = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role']
    ];
    
    // Establecer sesión PHP
    $_SESSION['user'] = $userData;
    
    echo "✅ Sesión PHP establecida<br>";
    
    // Verificar autenticación
    if (isAuthenticated()) {
        $currentUser = getCurrentUser();
        echo "✅ Usuario autenticado correctamente: " . htmlspecialchars($currentUser['name']) . "<br>";
        echo "Email: " . htmlspecialchars($currentUser['email']) . "<br>";
        echo "Rol: " . htmlspecialchars($currentUser['role']) . "<br>";
    } else {
        echo "❌ Usuario NO autenticado<br>";
    }
    
    // Verificar cookies
    echo "<h3>3. Verificando cookies:</h3>";
    if (isset($_COOKIE[session_name()])) {
        echo "✅ Cookie de sesión PHP: " . htmlspecialchars($_COOKIE[session_name()]) . "<br>";
    } else {
        echo "❌ No hay cookie de sesión PHP<br>";
    }
    
    if (isset($_COOKIE['session_id'])) {
        echo "✅ Cookie session_id: " . htmlspecialchars($_COOKIE['session_id']) . "<br>";
    } else {
        echo "❌ No hay cookie session_id<br>";
    }
    
    // Probar el endpoint
    echo "<h3>4. Probando endpoint import_contacts.php:</h3>";
    ob_start();
    include 'api/import_contacts.php';
    $apiOutput = ob_get_clean();
    
    echo "Respuesta del API:<br>";
    echo "<pre>" . htmlspecialchars($apiOutput) . "</pre>";
    
    // Verificar código de respuesta
    $httpCode = http_response_code();
    echo "Código de respuesta HTTP: $httpCode<br>";
    
    if ($httpCode == 200) {
        echo "✅ El endpoint funciona correctamente<br>";
    } elseif ($httpCode == 401) {
        echo "❌ Error 401 - Usuario no autenticado<br>";
    } elseif ($httpCode == 400) {
        echo "❌ Error 400 - Problema con la configuración<br>";
    } else {
        echo "⚠️ Código de respuesta inesperado: $httpCode<br>";
    }
    
} else {
    echo "❌ Usuario con ID $userIdToAuth no encontrado<br>";
}

echo "<h3>5. Instrucciones:</h3>";
echo "Para autenticar a un usuario específico, usa:<br>";
echo "?user_id=1 para Sergio Veloza<br>";
echo "?user_id=4 para FABIO CÉSAR BENJUMEA RUEDA<br>";
echo "?user_id=5 para Alejandra Noguera<br>";
echo "?user_id=6 para Armando arroyo<br>";

echo "<h3>6. Próximos pasos:</h3>";
echo "1. Si el endpoint funciona (código 200), el problema está resuelto<br>";
echo "2. Si obtienes error 401, ejecuta primero: php create_permanent_sessions.php<br>";
echo "3. Si obtienes error 400, verifica la configuración de Evolution API<br>";
?> 