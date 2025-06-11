<?php
// Script para probar sesiones permanentes desde el navegador
// Este script evita el problema de headers enviados prematuramente

echo "<h2>Prueba de Sesiones Permanentes desde Navegador</h2>";

// Verificar que se ejecuta desde navegador
if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
    echo "⚠️ Este script debe ejecutarse desde el navegador, no desde línea de comandos.<br>";
    echo "Ve a: https://mundoanimal.mangomorado.com/test_browser_sessions.php";
    echo "</div>";
    exit;
}

require_once 'config/database.php';
require_once 'includes/auth.php';

echo "<h3>1. Verificando sesiones permanentes en base de datos:</h3>";
$sessions = "SELECT us.*, u.name, u.email 
            FROM user_sessions us 
            JOIN users u ON us.user_id = u.id 
            WHERE us.user_id IN (1,4,5,6) 
            ORDER BY us.user_id";
$result = mysqli_query($conn, $sessions);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Usuario</th><th>Session ID</th><th>IP</th><th>Expira</th><th>Recordar</th><th>Estado</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        $status = '';
        if ($row['expires_at'] == '2080-01-01 00:00:00') {
            $status = '✅ Permanente (2080)';
        } elseif ($row['expires_at'] == '0000-00-00 00:00:00') {
            $status = '❌ Fecha inválida';
        } else {
            $status = '⚠️ Otra fecha';
        }
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['name']) . "<br><small>" . htmlspecialchars($row['email']) . "</small></td>";
        echo "<td>" . htmlspecialchars(substr($row['session_id'], 0, 16)) . "...</td>";
        echo "<td>" . htmlspecialchars($row['ip_address']) . "</td>";
        echo "<td>" . htmlspecialchars($row['expires_at']) . "</td>";
        echo "<td>" . ($row['remember_me'] ? 'Sí' : 'No') . "</td>";
        echo "<td>" . $status . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div style='color: orange; padding: 10px; border: 1px solid orange; margin: 10px 0;'>";
    echo "⚠️ No se encontraron sesiones permanentes. Ejecuta primero create_permanent_sessions.php";
    echo "</div>";
}

echo "<h3>2. Prueba de autenticación:</h3>";

// Obtener usuario a probar
$userIdToTest = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 1;

$userSql = "SELECT id, name, email, role FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($stmt, "i", $userIdToTest);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if ($user) {
    echo "<div style='background: #f0f8ff; padding: 15px; border: 1px solid #ccc; margin: 10px 0;'>";
    echo "<strong>Probando usuario:</strong> " . htmlspecialchars($user['name']) . " (ID: " . $user['id'] . ")<br>";
    echo "<strong>Email:</strong> " . htmlspecialchars($user['email']) . "<br>";
    echo "<strong>Rol:</strong> " . htmlspecialchars($user['role']) . "<br>";
    echo "</div>";
    
    // Iniciar sesión
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Establecer datos de usuario
    $_SESSION['user'] = $user;
    
    echo "<div style='background: #f0fff0; padding: 10px; border: 1px solid #90ee90; margin: 10px 0;'>";
    echo "✅ Sesión PHP establecida<br>";
    echo "</div>";
    
    // Verificar autenticación
    if (isAuthenticated()) {
        $currentUser = getCurrentUser();
        echo "<div style='background: #e8f5e8; padding: 10px; border: 1px solid #4caf50; margin: 10px 0;'>";
        echo "✅ Usuario autenticado correctamente<br>";
        echo "Nombre: " . htmlspecialchars($currentUser['name']) . "<br>";
        echo "Email: " . htmlspecialchars($currentUser['email']) . "<br>";
        echo "Rol: " . htmlspecialchars($currentUser['role']) . "<br>";
        echo "</div>";
        
        // Probar endpoint
        echo "<h3>3. Probando endpoint import_contacts.php:</h3>";
        
        // Simular petición POST
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        ob_start();
        include 'api/import_contacts.php';
        $apiOutput = ob_get_clean();
        
        $httpCode = http_response_code();
        
        echo "<div style='background: #fff8dc; padding: 15px; border: 1px solid #daa520; margin: 10px 0;'>";
        echo "<strong>Respuesta del endpoint:</strong><br>";
        echo "<pre style='background: #f5f5f5; padding: 10px; overflow-x: auto;'>" . htmlspecialchars($apiOutput) . "</pre>";
        echo "<strong>Código de respuesta HTTP:</strong> $httpCode<br>";
        
        if ($httpCode == 200) {
            echo "<div style='color: green; font-weight: bold;'>✅ El endpoint funciona correctamente</div>";
        } elseif ($httpCode == 401) {
            echo "<div style='color: red; font-weight: bold;'>❌ Error 401 - Usuario no autenticado</div>";
        } elseif ($httpCode == 400) {
            echo "<div style='color: red; font-weight: bold;'>❌ Error 400 - Problema con la configuración</div>";
        } else {
            echo "<div style='color: orange; font-weight: bold;'>⚠️ Código de respuesta inesperado: $httpCode</div>";
        }
        echo "</div>";
        
    } else {
        echo "<div style='background: #ffe6e6; padding: 10px; border: 1px solid #ff6b6b; margin: 10px 0;'>";
        echo "❌ Usuario NO autenticado<br>";
        echo "Esto puede deberse a problemas con las cookies o la configuración de sesiones.";
        echo "</div>";
    }
    
} else {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
    echo "❌ Usuario con ID $userIdToTest no encontrado";
    echo "</div>";
}

echo "<h3>4. Enlaces para probar otros usuarios:</h3>";
echo "<div style='margin: 10px 0;'>";
echo "<a href='?user_id=1' style='margin: 5px; padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 3px;'>Sergio Veloza (ID: 1)</a>";
echo "<a href='?user_id=4' style='margin: 5px; padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 3px;'>FABIO CÉSAR (ID: 4)</a>";
echo "<a href='?user_id=5' style='margin: 5px; padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 3px;'>Alejandra Noguera (ID: 5)</a>";
echo "<a href='?user_id=6' style='margin: 5px; padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 3px;'>Armando arroyo (ID: 6)</a>";
echo "</div>";

echo "<h3>5. Instrucciones:</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; margin: 10px 0;'>";
echo "1. <strong>Si obtienes código 200:</strong> El endpoint funciona correctamente<br>";
echo "2. <strong>Si obtienes código 401:</strong> Hay problemas de autenticación<br>";
echo "3. <strong>Si obtienes código 400:</strong> Hay problemas con la configuración de Evolution API<br>";
echo "4. <strong>Para probar otros usuarios:</strong> Usa los enlaces de arriba<br>";
echo "</div>";

echo "<h3>6. Solución al error 400:</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; margin: 10px 0;'>";
echo "Si el endpoint funciona aquí (código 200), entonces el problema original de error 400<br>";
echo "se debe a que no estabas autenticado en el navegador cuando intentaste importar contactos.<br>";
echo "La solución es simplemente iniciar sesión correctamente en la aplicación web.";
echo "</div>";
?> 