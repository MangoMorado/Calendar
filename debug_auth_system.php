<?php
require_once 'config/database.php';

echo "<h2>Diagnóstico Completo del Sistema de Autenticación</h2>";

// 1. Verificar configuración de sesiones PHP
echo "<h3>1. Configuración de Sesiones PHP:</h3>";
echo "session.save_handler: " . ini_get('session.save_handler') . "<br>";
echo "session.save_path: " . ini_get('session.save_path') . "<br>";
echo "session.use_cookies: " . ini_get('session.use_cookies') . "<br>";
echo "session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . "<br>";
echo "session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . "<br>";
echo "session.cookie_path: " . ini_get('session.cookie_path') . "<br>";
echo "session.cookie_domain: " . ini_get('session.cookie_domain') . "<br>";

// 2. Verificar estado actual de la sesión
echo "<h3>2. Estado Actual de la Sesión PHP:</h3>";
if (session_status() == PHP_SESSION_NONE) {
    echo "No hay sesión iniciada<br>";
    session_start();
    echo "✅ Sesión iniciada<br>";
} elseif (session_status() == PHP_SESSION_ACTIVE) {
    echo "✅ Sesión ya está activa<br>";
} else {
    echo "Estado de sesión: " . session_status() . "<br>";
}

// 3. Verificar cookies de sesión
echo "<h3>3. Cookies de Sesión:</h3>";
if (isset($_COOKIE[session_name()])) {
    echo "✅ Cookie de sesión PHP encontrada: " . htmlspecialchars($_COOKIE[session_name()]) . "<br>";
} else {
    echo "❌ No se encontró cookie de sesión PHP<br>";
}

if (isset($_COOKIE['session_id'])) {
    echo "✅ Cookie session_id encontrada: " . htmlspecialchars($_COOKIE['session_id']) . "<br>";
} else {
    echo "❌ No se encontró cookie session_id<br>";
}

// 4. Verificar datos de sesión PHP
echo "<h3>4. Datos de Sesión PHP:</h3>";
if (isset($_SESSION['user'])) {
    echo "✅ Datos de usuario en sesión PHP:<br>";
    echo "ID: " . $_SESSION['user']['id'] . "<br>";
    echo "Nombre: " . $_SESSION['user']['name'] . "<br>";
    echo "Email: " . $_SESSION['user']['email'] . "<br>";
    echo "Rol: " . $_SESSION['user']['role'] . "<br>";
} else {
    echo "❌ No hay datos de usuario en sesión PHP<br>";
}

// 5. Verificar configuración de sesiones en base de datos
echo "<h3>5. Configuración de Sesiones en Base de Datos:</h3>";
$sessionSettings = "SELECT setting_key, setting_value FROM session_settings ORDER BY setting_key";
$result = mysqli_query($conn, $sessionSettings);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Configuración</th><th>Valor</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr><td>" . htmlspecialchars($row['setting_key']) . "</td><td>" . htmlspecialchars($row['setting_value']) . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "No hay configuraciones de sesión<br>";
}

// 6. Verificar sesiones activas en base de datos
echo "<h3>6. Sesiones Activas en Base de Datos:</h3>";
$activeSessions = "SELECT us.*, u.name, u.email 
                  FROM user_sessions us 
                  JOIN users u ON us.user_id = u.id 
                  WHERE us.is_active = 1 
                  ORDER BY us.last_activity DESC";
$result = mysqli_query($conn, $activeSessions);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Usuario</th><th>Session ID</th><th>IP</th><th>Última Actividad</th><th>Expira</th><th>Recordar</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['name']) . " (" . htmlspecialchars($row['email']) . ")</td>";
        echo "<td>" . htmlspecialchars(substr($row['session_id'], 0, 16)) . "...</td>";
        echo "<td>" . htmlspecialchars($row['ip_address']) . "</td>";
        echo "<td>" . htmlspecialchars($row['last_activity']) . "</td>";
        echo "<td>" . htmlspecialchars($row['expires_at']) . "</td>";
        echo "<td>" . ($row['remember_me'] ? 'Sí' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No hay sesiones activas en la base de datos<br>";
}

// 7. Probar el sistema de autenticación
echo "<h3>7. Prueba del Sistema de Autenticación:</h3>";
require_once 'includes/auth.php';

if (isAuthenticated()) {
    $user = getCurrentUser();
    echo "✅ isAuthenticated() devuelve TRUE<br>";
    echo "Usuario autenticado: " . htmlspecialchars($user['name']) . "<br>";
} else {
    echo "❌ isAuthenticated() devuelve FALSE<br>";
}

// 8. Verificar usuarios disponibles
echo "<h3>8. Usuarios Disponibles en el Sistema:</h3>";
$users = "SELECT id, name, email, role FROM users ORDER BY name";
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
} else {
    echo "No hay usuarios en el sistema<br>";
}

// 9. Análisis del problema
echo "<h3>9. Análisis del Problema:</h3>";
echo "El problema de autenticación puede deberse a:<br>";
echo "1. <strong>Falta de cookies de sesión</strong> - El navegador no está enviando las cookies<br>";
echo "2. <strong>Sesión expirada</strong> - La sesión en la base de datos ha expirado<br>";
echo "3. <strong>Configuración incorrecta</strong> - Los parámetros de sesión están mal configurados<br>";
echo "4. <strong>Problema de dominio</strong> - Las cookies no se están enviando al dominio correcto<br>";

// 10. Recomendaciones
echo "<h3>10. Recomendaciones:</h3>";
echo "1. <strong>Verificar que estés logueado en el navegador</strong><br>";
echo "2. <strong>Limpiar cookies del navegador</strong> e iniciar sesión nuevamente<br>";
echo "3. <strong>Verificar que accedas desde el mismo dominio</strong> donde tienes la sesión<br>";
echo "4. <strong>Revisar la configuración de sesiones</strong> en session_settings<br>";
echo "5. <strong>Verificar que no haya problemas de HTTPS/HTTP</strong><br>";

echo "<h3>11. Próximos Pasos:</h3>";
echo "1. Ejecuta este script desde el navegador (no desde línea de comandos)<br>";
echo "2. Compara los resultados con los obtenidos desde línea de comandos<br>";
echo "3. Si hay diferencias, el problema está en el entorno de ejecución<br>";
?> 