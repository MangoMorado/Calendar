<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

echo "<h2>Prueba de Sesiones Después de la Corrección</h2>";

// Verificar que no hay headers enviados antes de iniciar sesión
echo "<h3>1. Verificación de headers:</h3>";
if (headers_sent($file, $line)) {
    echo "❌ Headers ya fueron enviados en $file:$line<br>";
} else {
    echo "✅ Headers no han sido enviados aún<br>";
}

// Intentar iniciar sesión
echo "<h3>2. Estado de la sesión PHP:</h3>";
if (session_status() == PHP_SESSION_NONE) {
    echo "No hay sesión iniciada - intentando iniciar...<br>";
    session_start();
    echo "✅ Sesión iniciada correctamente<br>";
} elseif (session_status() == PHP_SESSION_ACTIVE) {
    echo "✅ Sesión ya está activa<br>";
} else {
    echo "Estado de sesión: " . session_status() . "<br>";
}

// Verificar cookies
echo "<h3>3. Cookies de sesión:</h3>";
if (isset($_COOKIE[session_name()])) {
    echo "✅ Cookie de sesión encontrada: " . htmlspecialchars($_COOKIE[session_name()]) . "<br>";
} else {
    echo "No se encontró cookie de sesión<br>";
}

// Verificar autenticación
echo "<h3>4. Verificación de autenticación:</h3>";
if (isAuthenticated()) {
    $user = getCurrentUser();
    echo "✅ Usuario autenticado: " . htmlspecialchars($user['name']) . "<br>";
    echo "Email: " . htmlspecialchars($user['email']) . "<br>";
    echo "Rol: " . htmlspecialchars($user['role']) . "<br>";
} else {
    echo "❌ Usuario NO autenticado<br>";
    echo "Esto es normal si no has iniciado sesión en el navegador<br>";
}

// Verificar configuración de sesiones
echo "<h3>5. Configuración de sesiones actualizada:</h3>";
$sessionSettings = "SELECT setting_key, setting_value FROM session_settings ORDER BY setting_key";
$result = mysqli_query($conn, $sessionSettings);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Configuración</th><th>Valor</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr><td>" . htmlspecialchars($row['setting_key']) . "</td><td>" . htmlspecialchars($row['setting_value']) . "</td></tr>";
    }
    echo "</table>";
}

echo "<h3>6. Prueba de API:</h3>";
echo "Ahora puedes probar el endpoint api/import_contacts.php desde la interfaz web.<br>";
echo "Asegúrate de estar logueado en el navegador antes de intentar importar contactos.<br>";

echo "<h3>✅ Prueba completada</h3>";
echo "<p>Si todo está correcto, el error 400 debería estar resuelto.</p>";
?> 