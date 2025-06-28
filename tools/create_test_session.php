<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

echo "<h2>Creación de Sesión de Prueba</h2>";

// Verificar si hay usuarios en el sistema
echo "<h3>1. Verificando usuarios disponibles:</h3>";
$users = "SELECT id, name, email, role FROM users ORDER BY name";
$result = mysqli_query($conn, $users);

if (mysqli_num_rows($result) == 0) {
    echo "❌ No hay usuarios en el sistema. Necesitas crear al menos un usuario.<br>";
    exit;
}

$user = mysqli_fetch_assoc($result);
echo "✅ Usuario encontrado: " . htmlspecialchars($user['name']) . " (" . htmlspecialchars($user['email']) . ")<br>";

// Crear una sesión de prueba
echo "<h3>2. Creando sesión de prueba:</h3>";

// Iniciar sesión PHP si no está iniciada
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

// Intentar autenticar al usuario
$redirect = authenticateUser($userData, true); // true = remember me

if ($redirect) {
    echo "✅ Sesión creada exitosamente<br>";
    echo "Redirección: " . htmlspecialchars($redirect) . "<br>";
} else {
    echo "❌ Error al crear la sesión<br>";
}

// Verificar que la autenticación funciona
echo "<h3>3. Verificando autenticación:</h3>";
if (isAuthenticated()) {
    $currentUser = getCurrentUser();
    echo "✅ Usuario autenticado correctamente<br>";
    echo "ID: " . $currentUser['id'] . "<br>";
    echo "Nombre: " . htmlspecialchars($currentUser['name']) . "<br>";
    echo "Email: " . htmlspecialchars($currentUser['email']) . "<br>";
    echo "Rol: " . htmlspecialchars($currentUser['role']) . "<br>";
} else {
    echo "❌ Usuario NO autenticado<br>";
}

// Verificar cookies
echo "<h3>4. Verificando cookies:</h3>";
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

// Verificar sesiones en base de datos
echo "<h3>5. Verificando sesiones en base de datos:</h3>";
$activeSessions = "SELECT us.*, u.name, u.email 
                  FROM user_sessions us 
                  JOIN users u ON us.user_id = u.id 
                  WHERE us.user_id = ? AND us.is_active = 1 
                  ORDER BY us.last_activity DESC";
$stmt = mysqli_prepare($conn, $activeSessions);
mysqli_stmt_bind_param($stmt, "i", $user['id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    echo "✅ Sesiones activas en base de datos:<br>";
    while ($session = mysqli_fetch_assoc($result)) {
        echo "- Session ID: " . htmlspecialchars(substr($session['session_id'], 0, 16)) . "...<br>";
        echo "- IP: " . htmlspecialchars($session['ip_address']) . "<br>";
        echo "- Última actividad: " . htmlspecialchars($session['last_activity']) . "<br>";
        echo "- Expira: " . htmlspecialchars($session['expires_at']) . "<br>";
        echo "- Recordar: " . ($session['remember_me'] ? 'Sí' : 'No') . "<br>";
    }
} else {
    echo "❌ No hay sesiones activas en la base de datos<br>";
}

// Probar el endpoint de import_contacts
echo "<h3>6. Probando endpoint import_contacts:</h3>";
ob_start();
include __DIR__ . '/../api/import_contacts.php';
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

echo "<h3>7. Instrucciones para el navegador:</h3>";
echo "1. Ve a tu aplicación web en el navegador<br>";
echo "2. Inicia sesión con el usuario: " . htmlspecialchars($user['email']) . "<br>";
echo "3. Intenta importar contactos desde la interfaz web<br>";
echo "4. Si funciona, el problema estaba en las cookies/sesión<br>";
echo "5. Si no funciona, ejecuta este script desde el navegador para comparar<br>";
?> 