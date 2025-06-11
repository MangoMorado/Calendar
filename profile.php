<?php
// Incluir archivos necesarios
require_once 'includes/auth.php';

// Verificar que el usuario esté autenticado
requireAuth();

// Obtener información del usuario actual
$currentUser = getCurrentUser();
$userId = $currentUser['id'];
$userDetails = getUserById($userId);

$message = '';
$messageType = '';

// Procesar el formulario de actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Actualizar información de perfil
        if ($action === 'update_profile' && isset($_POST['name']) && isset($_POST['email'])) {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            
            // Validar que los campos no estén vacíos
            if (empty($name) || empty($email)) {
                $message = 'Por favor, completa todos los campos';
                $messageType = 'error';
            } 
            // Validar formato de correo electrónico
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = 'Por favor, introduce un correo electrónico válido';
                $messageType = 'error';
            }
            else {
                // Verificar si el correo ya está en uso por otro usuario
                $existingUser = getUserByEmail($email);
                if ($existingUser && $existingUser['id'] != $userId) {
                    $message = 'El correo electrónico ya está en uso por otro usuario';
                    $messageType = 'error';
                } else {
                    // Actualizar información en la base de datos
                    global $conn;
                    $sql = "UPDATE users SET name = ?, email = ? WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "ssi", $name, $email, $userId);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        // Actualizar historial
                        updateUserHistory($userId, 'Actualización de perfil');
                        
                        // Actualizar sesión
                        $_SESSION['user']['name'] = $name;
                        $_SESSION['user']['email'] = $email;
                        
                        $message = 'Perfil actualizado con éxito';
                        $messageType = 'success';
                        
                        // Refrescar los detalles del usuario
                        $userDetails = getUserById($userId);
                    } else {
                        $message = 'Error al actualizar el perfil: ' . mysqli_error($conn);
                        $messageType = 'error';
                    }
                }
            }
        }
        
        // Cambiar contraseña
        elseif ($action === 'change_password' && isset($_POST['current_password']) && isset($_POST['new_password']) && isset($_POST['confirm_password'])) {
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            // Validar que los campos no estén vacíos
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $message = 'Por favor, completa todos los campos';
                $messageType = 'error';
            }
            // Validar que las contraseñas nuevas coincidan
            elseif ($newPassword !== $confirmPassword) {
                $message = 'Las contraseñas nuevas no coinciden';
                $messageType = 'error';
            }
            // Validar longitud de la contraseña
            elseif (strlen($newPassword) < 6) {
                $message = 'La contraseña debe tener al menos 6 caracteres';
                $messageType = 'error';
            }
            else {
                // Verificar la contraseña actual
                global $conn;
                $sql = "SELECT password FROM users WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $userId);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $user = mysqli_fetch_assoc($result);
                
                if (password_verify($currentPassword, $user['password'])) {
                    // Cambiar la contraseña
                    $result = changePassword($userId, $newPassword);
                    
                    if ($result['success']) {
                        $message = 'Contraseña actualizada con éxito';
                        $messageType = 'success';
                    } else {
                        $message = $result['message'];
                        $messageType = 'error';
                    }
                } else {
                    $message = 'La contraseña actual es incorrecta';
                    $messageType = 'error';
                }
            }
        }
        
        // Procesar acciones de sesiones
        elseif ($action === 'logout_session' && isset($_POST['session_id'])) {
            $sessionId = $_POST['session_id'];
            if (logoutSpecificSession($sessionId)) {
                $message = 'Sesión cerrada exitosamente';
                $messageType = 'success';
            } else {
                $message = 'Error al cerrar la sesión';
                $messageType = 'error';
            }
        }
        
        elseif ($action === 'logout_all') {
            if (logoutAllSessions()) {
                $message = 'Todas las sesiones han sido cerradas';
                $messageType = 'success';
                // Redirigir al login después de cerrar todas las sesiones
                header('Location: login.php');
                exit();
            } else {
                $message = 'Error al cerrar todas las sesiones';
                $messageType = 'error';
            }
        }
    }
}

// Obtener sesiones activas
$activeSessions = getUserActiveSessions();
$currentSessionId = $_COOKIE['session_id'] ?? null;

// Formatear historial para mostrar
$historyLines = $userDetails['history'] ? explode("\n", $userDetails['history']) : [];
$recentHistory = array_slice($historyLines, -5); // Mostrar solo las últimas 5 entradas

// Definir título de la página
$pageTitle = 'Mi Perfil | Mundo Animal';

// Incluir el header
include 'includes/header.php';
?>

<main class="container">
    <div class="profile-panel" style="display: flex; gap: 2.5rem; align-items: flex-start;">
        <!-- Columna Izquierda: Contenido dinámico de tabs -->
        <div class="profile-content-panel tab-content" id="profileTabsContent" style="flex: 0 0 80%; max-width: 80%; min-width: 320px;">
            <!-- Tab Información Personal -->
            <div class="tab-pane fade show active" id="profile-tab" role="tabpanel">
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" novalidate>
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-group">
                        <label for="name"><i class="bi bi-person"></i> Nombre Completo</label>
                        <input type="text" id="name" name="name" class="form-control" required value="<?php echo htmlspecialchars($userDetails['name']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="email"><i class="bi bi-envelope"></i> Correo Electrónico</label>
                        <input type="email" id="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($userDetails['email']); ?>">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
            <!-- Tab Cambiar Contraseña -->
            <div class="tab-pane fade" id="password-tab" role="tabpanel">
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" novalidate>
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label for="current_password"><i class="bi bi-key"></i> Contraseña Actual</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password"><i class="bi bi-lock"></i> Nueva Contraseña</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required placeholder="Mínimo 6 caracteres">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password"><i class="bi bi-shield-lock"></i> Confirmar Nueva Contraseña</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg"></i> Cambiar Contraseña
                        </button>
                    </div>
                </form>
            </div>
            <!-- Tab Historial de Actividad -->
            <div class="tab-pane fade" id="history-tab" role="tabpanel">
                <h5 class="mb-3"><i class="bi bi-clock-history"></i> Mi Actividad Reciente</h5>
                <?php if (empty($recentHistory)) : ?>
                    <div class="text-center py-5">
                        <i class="bi bi-info-circle fs-1 text-muted"></i>
                        <p class="text-muted mt-2">No hay actividad reciente registrada.</p>
                    </div>
                <?php else : ?>
                    <div class="activity-list">
                        <?php foreach ($recentHistory as $entry) : 
                            $icon = 'bi-activity';
                            $actionType = 'action-update';
                            if (strpos($entry, 'Creó una cita') !== false) {
                                $icon = 'bi-calendar-plus';
                                $actionType = 'action-create';
                            } elseif (strpos($entry, 'Actualizó una cita') !== false) {
                                $icon = 'bi-calendar-check';
                                $actionType = 'action-update';
                            } elseif (strpos($entry, 'Eliminó una cita') !== false) {
                                $icon = 'bi-calendar-x';
                                $actionType = 'action-delete';
                            } elseif (strpos($entry, 'Inicio de sesión') !== false) {
                                $icon = 'bi-box-arrow-in-right';
                                $actionType = 'action-create';
                            } elseif (strpos($entry, 'Cierre de sesión') !== false) {
                                $icon = 'bi-box-arrow-right';
                                $actionType = 'action-delete';
                            } elseif (strpos($entry, 'Actualización de perfil') !== false) {
                                $icon = 'bi-person-gear';
                                $actionType = 'action-update';
                            } elseif (strpos($entry, 'Cambio de contraseña') !== false) {
                                $icon = 'bi-key';
                                $actionType = 'action-update';
                            }
                            $timestamp = '';
                            if (preg_match('/\[(.*?)\]/', $entry, $matches)) {
                                if (isset($matches[1])) {
                                    $timestamp = $matches[1];
                                    $formattedTimestamp = date('d/m/Y H:i', strtotime($timestamp));
                                } else {
                                    $formattedTimestamp = '';
                                }
                            } else {
                                $formattedTimestamp = '';
                            }
                            $mainAction = preg_replace('/\[.*?\]\s*/', '', $entry);
                        ?>
                        <div class="activity-item <?php echo $actionType; ?>">
                            <div class="activity-icon">
                                <i class="bi <?php echo $icon; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-main"><?php echo htmlspecialchars(trim($mainAction)); ?></div>
                                <?php if (!empty($formattedTimestamp)) : ?>
                                    <div class="activity-time"><i class="bi bi-clock"></i> <?php echo htmlspecialchars($formattedTimestamp); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Tab Sesiones Activas -->
            <div class="tab-pane fade" id="sessions-tab" role="tabpanel">
                <h5 class="mb-3"><i class="bi bi-device-hdd"></i> Mis Sesiones Activas</h5>
                <p class="text-muted mb-4">Gestiona las sesiones activas en tus equipos</p>
                <?php if (empty($activeSessions)) : ?>
                    <div class="text-center py-5">
                        <i class="bi bi-info-circle fs-1 text-muted"></i>
                        <p class="text-muted mt-2">No hay sesiones activas</p>
                    </div>
                <?php else : ?>
                    <div class="activity-list">
                        <?php foreach ($activeSessions as $session) : 
                            $isCurrentSession = ($session['session_id'] === $currentSessionId);
                            $deviceIcon = getDeviceIcon($session['device_info']);
                        ?>
                        <div class="activity-item <?php echo $isCurrentSession ? 'action-create' : 'action-update'; ?>">
                            <div class="activity-icon">
                                <i class="bi bi-<?php echo $deviceIcon; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-main">
                                    <?php echo htmlspecialchars($session['device_info']); ?>
                                    <?php if ($isCurrentSession) : ?>
                                        <span class="role-badge admin ms-2">Sesión actual</span>
                                    <?php endif; ?>
                                    <?php if ($session['remember_me']) : ?>
                                        <span class="role-badge user ms-2">Recordar equipo</span>
                                    <?php endif; ?>
                                </div>
                                <div class="activity-time">
                                    <i class="bi bi-geo-alt"></i> IP: <?php echo htmlspecialchars($session['ip_address']); ?> | 
                                    <i class="bi bi-clock"></i> Última actividad: <?php echo formatLastActivity($session['last_activity']); ?> | 
                                    <i class="bi bi-calendar"></i> Expira: <?php echo formatExpiration($session['expires_at']); ?>
                                </div>
                                <?php if (!$isCurrentSession) : ?>
                                    <div class="mt-2">
                                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" novalidate style="display: inline;">
                                            <input type="hidden" name="action" value="logout_session">
                                            <input type="hidden" name="session_id" value="<?php echo htmlspecialchars($session['session_id']); ?>">
                                            <button type="submit" class="btn btn-outline btn-sm" onclick="return confirm('¿Estás seguro de que deseas cerrar esta sesión?')">
                                                <i class="bi bi-x-circle"></i> Cerrar Sesión
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-4">
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" novalidate>
                            <input type="hidden" name="action" value="logout_all">
                            <button type="submit" class="btn btn-outline" onclick="return confirm('¿Estás seguro de que deseas cerrar TODAS las sesiones? Esto te cerrará sesión en todos los equipos.')">
                                <i class="bi bi-power"></i> Cerrar Todas las Sesiones
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Columna Derecha: Header, Tabs y Profile Card -->
        <div style="flex: 1 1 0; min-width: 220px; max-width: 260px; display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Header de usuario -->
            <div class="users-header">
                <h1><i class="bi bi-person-circle"></i> Mi Perfil</h1>
            </div>
            <!-- Tabs -->
            <nav class="profile-tabs-vertical nav flex-column nav-pills me-3" id="profileTabs" role="tablist" aria-orientation="vertical">
                <button class="nav-link active" id="profile-tab-btn" data-bs-toggle="pill" data-bs-target="#profile-tab" type="button" role="tab">
                    <i class="bi bi-person"></i> Información Personal
                </button>
                <button class="nav-link" id="password-tab-btn" data-bs-toggle="pill" data-bs-target="#password-tab" type="button" role="tab">
                    <i class="bi bi-key"></i> Cambiar Contraseña
                </button>
                <button class="nav-link" id="history-tab-btn" data-bs-toggle="pill" data-bs-target="#history-tab" type="button" role="tab">
                    <i class="bi bi-clock-history"></i> Historial de Actividad
                </button>
                <button class="nav-link" id="sessions-tab-btn" data-bs-toggle="pill" data-bs-target="#sessions-tab" type="button" role="tab">
                    <i class="bi bi-device-hdd"></i> Sesiones Activas
                </button>
            </nav>
            <!-- Profile Card -->
            <div class="profile-user-card">
                <div class="btn-icon mb-3 mx-auto">
                    <i class="bi bi-person fs-1"></i>
                </div>
                <h4 class="font-bold mb-1"><?php echo htmlspecialchars($userDetails['name']); ?></h4>
                <div class="text-muted mb-1"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($userDetails['email']); ?></div>
                <div class="mb-2">
                    <span class="role-badge <?php echo $userDetails['role'] === 'admin' ? 'admin' : 'user'; ?>">
                        <?php echo $userDetails['role'] === 'admin' ? 'ADMINISTRADOR' : 'USUARIO'; ?>
                    </span>
                </div>
                <div class="text-muted small"><i class="bi bi-calendar-check"></i> Miembro desde<br><?php echo date('d/m/Y', strtotime($userDetails['created_at'])); ?></div>
            </div>
        </div>
    </div>
</main>

<?php
// Funciones auxiliares para sesiones
function getDeviceIcon($deviceInfo) {
    switch (strtolower($deviceInfo)) {
        case 'móvil':
        case 'mobile':
            return 'phone';
        case 'windows':
            return 'laptop';
        case 'mac':
            return 'laptop';
        case 'linux':
            return 'laptop';
        default:
            return 'device-hdd';
    }
}

function formatLastActivity($lastActivity) {
    $timestamp = strtotime($lastActivity);
    $now = time();
    $diff = $now - $timestamp;
    if ($diff < 60) {
        return 'Hace un momento';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return "Hace $minutes minuto" . ($minutes > 1 ? 's' : '');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "Hace $hours hora" . ($hours > 1 ? 's' : '');
    } else {
        $days = floor($diff / 86400);
        return "Hace $days día" . ($days > 1 ? 's' : '');
    }
}

function formatExpiration($expiresAt) {
    $timestamp = strtotime($expiresAt);
    $now = time();
    $diff = $timestamp - $now;
    if ($diff < 0) {
        return 'Expirada';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return "En $minutes minuto" . ($minutes > 1 ? 's' : '');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "En $hours hora" . ($hours > 1 ? 's' : '');
    } else {
        $days = floor($diff / 86400);
        return "En $days día" . ($days > 1 ? 's' : '');
    }
}

// Incluir el footer
include 'includes/footer.php';
?>

<!-- Script para tabs de perfil -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.profile-tabs-vertical .nav-link');
    const tabPanes = document.querySelectorAll('.profile-content-panel .tab-pane');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            // Quitar clase activa de todos los botones
            tabButtons.forEach(b => b.classList.remove('active'));
            // Ocultar todos los paneles
            tabPanes.forEach(pane => pane.style.display = 'none');
            // Activar el botón actual
            this.classList.add('active');
            // Mostrar el panel correspondiente
            const target = this.getAttribute('data-bs-target');
            const pane = document.querySelector(target);
            if (pane) {
                pane.style.display = 'block';
            }
        });
    });
    // Inicializar: mostrar solo el tab activo
    tabPanes.forEach(pane => pane.style.display = 'none');
    const activeBtn = document.querySelector('.profile-tabs-vertical .nav-link.active');
    if (activeBtn) {
        const target = activeBtn.getAttribute('data-bs-target');
        const pane = document.querySelector(target);
        if (pane) {
            pane.style.display = 'block';
        }
    }
});
</script> 