<?php
require_once 'config/database.php';

echo '<h2>Diagnóstico de Sesiones</h2>';

// Verificar configuración de sesiones
echo '<h3>1. Configuración de sesiones:</h3>';
$sessionSettings = 'SELECT setting_key, setting_value FROM session_settings ORDER BY setting_key';
$result = mysqli_query($conn, $sessionSettings);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo '<tr><th>Configuración</th><th>Valor</th></tr>';
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<tr><td>'.htmlspecialchars($row['setting_key']).'</td><td>'.htmlspecialchars($row['setting_value']).'</td></tr>';
    }
    echo '</table>';
} else {
    echo 'No hay configuraciones de sesión<br>';
}

// Verificar sesiones activas
echo '<h3>2. Sesiones activas en la base de datos:</h3>';
$activeSessions = 'SELECT us.*, u.name, u.email 
                  FROM user_sessions us 
                  JOIN users u ON us.user_id = u.id 
                  WHERE us.is_active = 1 
                  ORDER BY us.last_activity DESC';
$result = mysqli_query($conn, $activeSessions);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo '<tr><th>Usuario</th><th>IP</th><th>Última Actividad</th><th>Expira</th><th>Recordar</th></tr>';
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<td>'.htmlspecialchars($row['name']).' ('.htmlspecialchars($row['email']).')</td>';
        echo '<td>'.htmlspecialchars($row['ip_address']).'</td>';
        echo '<td>'.htmlspecialchars($row['last_activity']).'</td>';
        echo '<td>'.htmlspecialchars($row['expires_at']).'</td>';
        echo '<td>'.($row['remember_me'] ? 'Sí' : 'No').'</td>';
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo 'No hay sesiones activas<br>';
}

// Verificar configuración de PHP
echo '<h3>3. Configuración de PHP para sesiones:</h3>';
echo 'session.save_handler: '.ini_get('session.save_handler').'<br>';
echo 'session.save_path: '.ini_get('session.save_path').'<br>';
echo 'session.use_cookies: '.ini_get('session.use_cookies').'<br>';
echo 'session.cookie_lifetime: '.ini_get('session.cookie_lifetime').'<br>';
echo 'session.gc_maxlifetime: '.ini_get('session.gc_maxlifetime').'<br>';

// Verificar estado actual de la sesión
echo '<h3>4. Estado actual de la sesión PHP:</h3>';
if (session_status() == PHP_SESSION_NONE) {
    echo 'No hay sesión iniciada<br>';
} elseif (session_status() == PHP_SESSION_ACTIVE) {
    echo 'Sesión activa<br>';
    if (isset($_SESSION['user'])) {
        echo 'Usuario en sesión: '.htmlspecialchars($_SESSION['user']['name']).'<br>';
    } else {
        echo 'No hay datos de usuario en la sesión<br>';
    }
} else {
    echo 'Estado de sesión: '.session_status().'<br>';
}

// Verificar cookies
echo '<h3>5. Cookies de sesión:</h3>';
if (isset($_COOKIE[session_name()])) {
    echo 'Cookie de sesión encontrada: '.htmlspecialchars($_COOKIE[session_name()]).'<br>';
} else {
    echo 'No se encontró cookie de sesión<br>';
}

// Verificar headers enviados
echo '<h3>6. Headers enviados:</h3>';
if (headers_sent($file, $line)) {
    echo "Headers ya fueron enviados en $file:$line<br>";
} else {
    echo 'Headers no han sido enviados aún<br>';
}

echo '<h3>7. Recomendaciones:</h3>';
echo '1. Asegúrate de estar logueado en el sistema web<br>';
echo '2. Verifica que no haya problemas de cookies en el navegador<br>';
echo '3. Intenta acceder desde la interfaz web en lugar de llamadas directas a la API<br>';
echo '4. Si el problema persiste, verifica la configuración de sesiones en session_settings<br>';
?> 