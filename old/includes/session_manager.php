<?php
/**
 * Sistema de Gestión de Sesiones Avanzado
 * Maneja sesiones con identificación de equipo, tiempo de expiración y "recordar equipo"
 */

require_once __DIR__.'/../config/database.php';

class SessionManager
{
    private $conn;

    private $settings;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->loadSettings();
    }

    /**
     * Cargar configuración de sesiones
     */
    private function loadSettings()
    {
        $sql = 'SELECT setting_key, setting_value FROM session_settings';
        $result = mysqli_query($this->conn, $sql);
        $this->settings = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $this->settings[$row['setting_key']] = $row['setting_value'];
        }

        // Al construir SessionManager o antes de calcular fechas:
        $sql = "SELECT setting_value FROM settings WHERE setting_key = 'timezone' LIMIT 1";
        $result = mysqli_query($this->conn, $sql);
        if ($row = mysqli_fetch_assoc($result)) {
            date_default_timezone_set($row['setting_value']);
        } else {
            date_default_timezone_set('America/Bogota');
        }
    }

    /**
     * Obtener configuración de sesión
     */
    public function getSetting($key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Actualizar configuración de sesión
     */
    public function updateSetting($key, $value)
    {
        $sql = 'INSERT INTO session_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)';
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ss', $key, $value);
        $result = mysqli_stmt_execute($stmt);

        if ($result) {
            $this->settings[$key] = $value;
        }

        return $result;
    }

    /**
     * Obtener información del dispositivo
     */
    private function getDeviceInfo()
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
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

        return $deviceInfo;
    }

    /**
     * Obtener dirección IP real
     */
    private function getRealIP()
    {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Crear nueva sesión
     */
    public function createSession($userId, $rememberMe = false)
    {
        // Generar ID de sesión único
        $sessionId = bin2hex(random_bytes(32));

        // Calcular tiempo de expiración
        $timeout = $rememberMe ?
            (int) $this->getSetting('remember_me_timeout', 604800) :
            (int) $this->getSetting('session_timeout', 3600);

        // Manejar valores especiales
        if ($timeout === 0) {
            // Sin restricciones - sesión que nunca expira
            $expiresAt = '2099-12-31 23:59:59';
        } elseif ($timeout === -1) {
            // Siempre - para "recordar equipo"
            $expiresAt = '2099-12-31 23:59:59';
        } else {
            // Tiempo normal
            $expiresAt = date('Y-m-d H:i:s', time() + $timeout);
        }

        // Obtener información del dispositivo
        $ipAddress = $this->getRealIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $deviceInfo = $this->getDeviceInfo();

        // Limpiar sesiones expiradas del usuario
        $this->cleanupExpiredSessions($userId);

        // Verificar límite de sesiones activas
        $maxSessions = (int) $this->getSetting('max_sessions_per_user', 5);

        // Si maxSessions es 0, no hay límite
        if ($maxSessions > 0) {
            $activeSessions = $this->getActiveSessionsCount($userId);

            if ($activeSessions >= $maxSessions) {
                // Eliminar la sesión más antigua
                $this->removeOldestSession($userId);
            }
        }

        // Insertar nueva sesión
        $sql = 'INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, device_info, remember_me, expires_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?)';
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'issssis', $userId, $sessionId, $ipAddress, $userAgent, $deviceInfo, $rememberMe, $expiresAt);

        if (mysqli_stmt_execute($stmt)) {
            // Establecer cookie de sesión
            if ($timeout === 0 || $timeout === -1) {
                // Para sesiones sin restricciones o "siempre", cookie que expira en 10 años
                $cookieExpires = time() + (10 * 365 * 24 * 60 * 60);
            } else {
                $cookieExpires = $rememberMe ? time() + $timeout : 0;
            }
            setcookie('session_id', $sessionId, $cookieExpires, '/', '', isset($_SERVER['HTTPS']), true);

            return $sessionId;
        }

        return false;
    }

    /**
     * Validar sesión actual
     */
    public function validateSession($sessionId = null)
    {
        if (! $sessionId) {
            $sessionId = $_COOKIE['session_id'] ?? null;
        }

        if (! $sessionId) {
            return false;
        }

        // Buscar sesión en la base de datos
        $sql = 'SELECT us.*, u.name, u.email, u.role 
                FROM user_sessions us 
                JOIN users u ON us.user_id = u.id 
                WHERE us.session_id = ? AND us.is_active = 1 AND us.expires_at > NOW()';
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 's', $sessionId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($session = mysqli_fetch_assoc($result)) {
            // Actualizar última actividad
            $this->updateLastActivity($sessionId);

            // Verificar si la IP ha cambiado (opcional, para mayor seguridad)
            $currentIP = $this->getRealIP();
            if ($session['ip_address'] !== $currentIP) {
                // Log del cambio de IP (opcional)
                error_log("Cambio de IP detectado para sesión {$sessionId}: {$session['ip_address']} -> {$currentIP}");
            }

            return $session;
        }

        return false;
    }

    /**
     * Actualizar última actividad
     */
    private function updateLastActivity($sessionId)
    {
        $sql = 'UPDATE user_sessions SET last_activity = NOW() WHERE session_id = ?';
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 's', $sessionId);
        mysqli_stmt_execute($stmt);
    }

    /**
     * Cerrar sesión
     */
    public function destroySession($sessionId = null)
    {
        if (! $sessionId) {
            $sessionId = $_COOKIE['session_id'] ?? null;
        }

        if ($sessionId) {
            // Marcar sesión como inactiva
            $sql = 'UPDATE user_sessions SET is_active = 0 WHERE session_id = ?';
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, 's', $sessionId);
            mysqli_stmt_execute($stmt);
        }

        // Eliminar cookie
        setcookie('session_id', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);

        return true;
    }

    /**
     * Obtener sesiones activas del usuario
     */
    public function getUserSessions($userId)
    {
        $sql = 'SELECT * FROM user_sessions 
                WHERE user_id = ? AND is_active = 1 AND expires_at > NOW() 
                ORDER BY last_activity DESC';
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    /**
     * Obtener conteo de sesiones activas
     */
    private function getActiveSessionsCount($userId)
    {
        $sql = 'SELECT COUNT(*) as count FROM user_sessions 
                WHERE user_id = ? AND is_active = 1 AND expires_at > NOW()';
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        return (int) $row['count'];
    }

    /**
     * Eliminar sesión más antigua del usuario
     */
    private function removeOldestSession($userId)
    {
        $sql = 'UPDATE user_sessions SET is_active = 0 
                WHERE user_id = ? AND is_active = 1 
                ORDER BY last_activity ASC 
                LIMIT 1';
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
    }

    /**
     * Limpiar sesiones expiradas
     */
    public function cleanupExpiredSessions($userId = null)
    {
        $sql = 'UPDATE user_sessions SET is_active = 0 WHERE expires_at <= NOW()';

        if ($userId) {
            $sql .= ' AND user_id = ?';
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, 'i', $userId);
        } else {
            $stmt = mysqli_prepare($this->conn, $sql);
        }

        mysqli_stmt_execute($stmt);
    }

    /**
     * Limpiar todas las sesiones del usuario
     */
    public function clearAllUserSessions($userId)
    {
        $sql = 'UPDATE user_sessions SET is_active = 0 WHERE user_id = ?';
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $userId);

        return mysqli_stmt_execute($stmt);
    }

    /**
     * Verificar si se requiere login en cada visita
     */
    public function requiresLoginOnVisit()
    {
        return (bool) $this->getSetting('require_login_on_visit', 1);
    }

    /**
     * Ejecutar limpieza automática de sesiones
     */
    public function autoCleanup()
    {
        $lastCleanup = $this->getSetting('last_cleanup', 0);
        $cleanupInterval = (int) $this->getSetting('session_cleanup_interval', 86400);

        if (time() - $lastCleanup > $cleanupInterval) {
            $this->cleanupExpiredSessions();
            $this->updateSetting('last_cleanup', time());
        }
    }
}

// Inicializar el gestor de sesiones
$sessionManager = new SessionManager($conn);

// Ejecutar limpieza automática
$sessionManager->autoCleanup();
?> 