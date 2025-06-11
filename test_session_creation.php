<?php
// Script de prueba para verificar la creación de sesiones temporales
require_once 'config/database.php';
require_once 'includes/auth.php';

echo "<h2>Prueba de Creación de Sesiones Temporales</h2>";

// Verificar que se ejecuta desde navegador
if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
    echo "⚠️ Este script debe ejecutarse desde el navegador.<br>";
    echo "Ve a: https://mundoanimal.mangomorado.com/test_session_creation.php";
    echo "</div>";
    exit;
}

// Verificar autenticación
if (!isAuthenticated()) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
    echo "❌ No estás autenticado. Por favor inicia sesión primero.";
    echo "</div>";
    exit;
}

$currentUser = getCurrentUser();
echo "<div style='background: #e8f5e8; padding: 10px; border: 1px solid #4caf50; margin: 10px 0;'>";
echo "✅ Usuario autenticado: " . htmlspecialchars($currentUser['name']) . " (ID: " . $currentUser['id'] . ")<br>";
echo "</div>";

// Procesar creación de sesión si se solicita
if (isset($_GET['create']) && $_GET['create'] === '1') {
    echo "<h3>Creando nueva sesión temporal...</h3>";
    
    // Función para crear sesión temporal (copiada de profile.php)
    function createTemporarySession($userId, $durationSeconds) {
        global $conn;
        
        try {
            // Generar ID de sesión único
            $sessionId = bin2hex(random_bytes(32));
            
            // Calcular tiempo de expiración
            $expiresAt = date('Y-m-d H:i:s', time() + $durationSeconds);
            
            // Obtener información del dispositivo
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // Determinar tipo de dispositivo
            $deviceInfo = 'Desconocido';
            if (preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent)) {
                $deviceInfo = 'Móvil';
            } elseif (preg_match('/Windows/i', $userAgent)) {
                $deviceInfo = 'Windows';
            } elseif (preg_match('/Mac/i', $userAgent)) {
                $deviceInfo = 'Mac';
            } elseif (preg_match('/Linux/i', $userAgent)) {
                $deviceInfo = 'Linux';
            }
            
            // Insertar nueva sesión
            $sql = "INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, device_info, remember_me, expires_at, is_active) 
                    VALUES (?, ?, ?, ?, ?, 0, ?, 1)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "isssss", $userId, $sessionId, $ipAddress, $userAgent, $deviceInfo, $expiresAt);
            
            if (mysqli_stmt_execute($stmt)) {
                // Establecer cookie de sesión
                $cookieExpires = time() + $durationSeconds;
                setcookie('session_id', $sessionId, $cookieExpires, '/', '', isset($_SERVER['HTTPS']), true);
                
                // Actualizar historial del usuario
                updateUserHistory($userId, "Creó sesión temporal de " . gmdate("H:i:s", $durationSeconds));
                
                return [
                    'success' => true,
                    'session_id' => $sessionId,
                    'expires_at' => $expiresAt,
                    'duration' => $durationSeconds
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al insertar sesión en la base de datos: ' . mysqli_error($conn)
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error inesperado: ' . $e->getMessage()
            ];
        }
    }
    
    $sessionResult = createTemporarySession($currentUser['id'], 3600); // 1 hora
    
    if ($sessionResult['success']) {
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; margin: 10px 0; border-radius: 5px;'>";
        echo "<h4 style='color: #155724; margin-top: 0;'>✅ Sesión creada exitosamente</h4>";
        echo "<p><strong>Session ID:</strong> " . htmlspecialchars(substr($sessionResult['session_id'], 0, 16)) . "...</p>";
        echo "<p><strong>Expira:</strong> " . htmlspecialchars($sessionResult['expires_at']) . "</p>";
        echo "<p><strong>Duración:</strong> " . gmdate("H:i:s", $sessionResult['duration']) . "</p>";
        echo "<p><strong>Cookie establecida:</strong> Sí</p>";
        echo "</div>";
        
        // Verificar que la cookie se estableció
        if (isset($_COOKIE['session_id'])) {
            echo "<div style='background: #d1ecf1; padding: 10px; border: 1px solid #bee5eb; margin: 10px 0; border-radius: 5px;'>";
            echo "✅ Cookie de sesión verificada: " . htmlspecialchars(substr($_COOKIE['session_id'], 0, 16)) . "...";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; margin: 10px 0; border-radius: 5px;'>";
            echo "⚠️ Cookie de sesión no encontrada";
            echo "</div>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; margin: 10px 0; border-radius: 5px;'>";
        echo "<h4 style='color: #721c24; margin-top: 0;'>❌ Error al crear sesión</h4>";
        echo "<p>" . htmlspecialchars($sessionResult['message']) . "</p>";
        echo "</div>";
    }
}

// Mostrar sesiones actuales
echo "<h3>Sesiones actuales del usuario:</h3>";
$activeSessions = getUserActiveSessions();

if (empty($activeSessions)) {
    echo "<div style='color: orange; padding: 10px; border: 1px solid orange; margin: 10px 0;'>";
    echo "No hay sesiones activas";
    echo "</div>";
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f8f9fa;'>";
    echo "<th style='padding: 8px;'>Dispositivo</th>";
    echo "<th style='padding: 8px;'>IP</th>";
    echo "<th style='padding: 8px;'>Última Actividad</th>";
    echo "<th style='padding: 8px;'>Expira</th>";
    echo "<th style='padding: 8px;'>Recordar</th>";
    echo "<th style='padding: 8px;'>Estado</th>";
    echo "</tr>";
    
    foreach ($activeSessions as $session) {
        $isCurrentSession = ($session['session_id'] === ($_COOKIE['session_id'] ?? ''));
        $rowStyle = $isCurrentSession ? 'background: #e8f5e8;' : '';
        
        echo "<tr style='$rowStyle'>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($session['device_info']) . 
             ($isCurrentSession ? ' <strong>(Actual)</strong>' : '') . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($session['ip_address']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($session['last_activity']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($session['expires_at']) . "</td>";
        echo "<td style='padding: 8px;'>" . ($session['remember_me'] ? 'Sí' : 'No') . "</td>";
        echo "<td style='padding: 8px;'>" . ($session['is_active'] ? 'Activa' : 'Inactiva') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Enlaces de prueba
echo "<h3>Acciones de prueba:</h3>";
echo "<div style='margin: 10px 0;'>";
echo "<a href='?create=1' style='margin: 5px; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>";
echo "🔄 Crear Nueva Sesión de 1 Hora";
echo "</a>";
echo "<a href='test_browser_sessions.php' style='margin: 5px; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>";
echo "🧪 Probar Endpoint API";
echo "</a>";
echo "<a href='profile.php' style='margin: 5px; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>";
echo "👤 Ir al Perfil";
echo "</a>";
echo "</div>";

echo "<h3>Instrucciones:</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; margin: 10px 0; border-radius: 5px;'>";
echo "<ol>";
echo "<li><strong>Crear sesión:</strong> Haz clic en 'Crear Nueva Sesión de 1 Hora'</li>";
echo "<li><strong>Verificar cookie:</strong> La cookie se establecerá automáticamente</li>";
echo "<li><strong>Probar API:</strong> Usa 'Probar Endpoint API' para verificar que funciona</li>";
echo "<li><strong>Usar en perfil:</strong> También puedes crear sesiones desde el tab de sesiones en tu perfil</li>";
echo "</ol>";
echo "</div>";

echo "<h3>Información técnica:</h3>";
echo "<div style='background: #e9ecef; padding: 15px; border: 1px solid #dee2e6; margin: 10px 0; border-radius: 5px;'>";
echo "<p><strong>Cookie name:</strong> session_id</p>";
echo "<p><strong>Duración:</strong> 1 hora (3600 segundos)</p>";
echo "<p><strong>Scope:</strong> / (todo el dominio)</p>";
echo "<p><strong>Secure:</strong> " . (isset($_SERVER['HTTPS']) ? 'Sí' : 'No') . "</p>";
echo "<p><strong>HttpOnly:</strong> Sí</p>";
echo "</div>";
?> 