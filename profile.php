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
    }
}

// Formatear historial para mostrar
$historyLines = $userDetails['history'] ? explode("\n", $userDetails['history']) : [];
$recentHistory = array_slice($historyLines, -5); // Mostrar solo las últimas 5 entradas

// Definir título de la página
$pageTitle = 'Mi Perfil | Mundo Animal';

// Estilos adicionales específicos para esta página
$extraStyles = '
<style>
    .profile-container {
        max-width: 800px;
        margin: 40px auto;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        background-color: #fff;
    }
    
    .profile-header {
        display: flex;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e9eaf3;
    }
    
    .profile-icon {
        font-size: 2.5rem;
        color: #5D69F7;
        margin-right: 20px;
        background: #f0f4ff;
        width: 70px;
        height: 70px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }
    
    .profile-info h1 {
        margin-bottom: 5px;
        color: #2d3748;
    }
    
    .profile-info p {
        color: #718096;
    }
    
    .tabs {
        display: flex;
        border-bottom: 1px solid #e9eaf3;
        margin-bottom: 25px;
    }
    
    .tab {
        padding: 10px 20px;
        cursor: pointer;
        font-weight: 500;
        color: #718096;
        position: relative;
    }
    
    .tab.active {
        color: #5D69F7;
    }
    
    .tab.active:after {
        content: "";
        position: absolute;
        bottom: -1px;
        left: 0;
        width: 100%;
        height: 2px;
        background-color: #5D69F7;
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .history-list {
        list-style: none;
        padding: 0;
    }
    
    .history-item {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 15px;
        display: flex;
        background-color: #f8f9fa;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        transition: all 0.2s ease;
    }
    
    .history-item:hover {
        transform: translateX(5px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .history-icon {
        margin-right: 15px;
        display: flex;
        align-items: flex-start;
        padding-top: 3px;
    }
    
    .history-icon i {
        font-size: 1.2rem;
    }
    
    .history-content {
        flex: 1;
    }
    
    .history-main {
        font-weight: 500;
        margin-bottom: 8px;
        color: #333;
    }
    
    .history-details {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        font-size: 0.85rem;
        color: #666;
    }
    
    .history-time, .history-date, .history-extra, .history-id {
        display: flex;
        align-items: center;
    }
    
    .history-details i {
        font-size: 0.8rem;
        margin-right: 5px;
        opacity: 0.7;
    }
    
    .action-create {
        border-left: 3px solid #10B981;
    }
    
    .action-create .history-icon i {
        color: #10B981;
    }
    
    .action-update {
        border-left: 3px solid #6366F1;
    }
    
    .action-update .history-icon i {
        color: #6366F1;
    }
    
    .action-delete {
        border-left: 3px solid #EF4444;
    }
    
    .action-delete .history-icon i {
        color: #EF4444;
    }
    
    .action-login, .action-logout {
        border-left: 3px solid #F59E0B;
    }
    
    .action-login .history-icon i, .action-logout .history-icon i {
        color: #F59E0B;
    }
    
    .action-profile, .action-password {
        border-left: 3px solid #5D69F7;
    }
    
    .action-profile .history-icon i, .action-password .history-icon i {
        color: #5D69F7;
    }
    
    .message {
        padding: 10px 15px;
        margin-bottom: 20px;
        border-radius: 6px;
    }
    
    .message.error {
        background-color: #FEEFEF;
        color: #EF4444;
        border: 1px solid #FCDEDE;
    }
    
    .message.success {
        background-color: #EFF8F6;
        color: #10B981;
        border: 1px solid #D1ECEA;
    }
    
    .history-more {
        margin-top: 20px;
        text-align: center;
    }
    
    .history-more .btn {
        padding: 8px 15px;
        background-color: transparent;
        border: 1px solid var(--secondary-color);
        color: var(--secondary-color);
        transition: all 0.2s ease;
        border-radius: var(--radius);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .history-more .btn:hover {
        background-color: var(--secondary-color);
        color: white;
    }
    
    .history-more .btn i {
        font-size: 1rem;
    }
</style>
';

// Script para manejo de pestañas
$extraScripts = '
<script>
    // Cambiar entre pestañas
    document.addEventListener("DOMContentLoaded", function() {
        const tabs = document.querySelectorAll(".tab");
        
        tabs.forEach(tab => {
            tab.addEventListener("click", function() {
                // Desactivar todas las pestañas y contenidos
                document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));
                document.querySelectorAll(".tab-content").forEach(c => c.classList.remove("active"));
                
                // Activar la pestaña actual y su contenido
                this.classList.add("active");
                const tabId = this.getAttribute("data-tab");
                document.getElementById(`${tabId}-tab`).classList.add("active");
            });
        });
    });
</script>
';

// Incluir el header
include 'includes/header.php';
?>
    
<main class="container profile-container">
    <div class="profile-header">
        <div class="profile-icon">
            <i class="bi bi-person"></i>
        </div>
        <div class="profile-info">
            <h1><?php echo htmlspecialchars($userDetails['name']); ?></h1>
            <p><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($userDetails['email']); ?></p>
            <p><i class="bi bi-person-badge"></i> <?php echo $userDetails['role'] === 'admin' ? 'Administrador' : 'Usuario'; ?></p>
            <p><i class="bi bi-calendar-check"></i> Miembro desde <?php echo date('d/m/Y', strtotime($userDetails['created_at'])); ?></p>
        </div>
    </div>
    
    <?php if (!empty($message)) : ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <div class="tabs">
        <div class="tab active" data-tab="profile">Información Personal</div>
        <div class="tab" data-tab="password">Cambiar Contraseña</div>
        <div class="tab" data-tab="history">Historial de Actividad</div>
    </div>
    
    <div class="tab-content active" id="profile-tab">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" novalidate>
            <input type="hidden" name="action" value="update_profile">
            
            <div class="form-group">
                <label for="name"><i class="bi bi-person"></i> Nombre Completo</label>
                <input type="text" id="name" name="name" class="form-control" required 
                       value="<?php echo htmlspecialchars($userDetails['name']); ?>">
            </div>
            
            <div class="form-group">
                <label for="email"><i class="bi bi-envelope"></i> Correo Electrónico</label>
                <input type="email" id="email" name="email" class="form-control" required 
                       value="<?php echo htmlspecialchars($userDetails['email']); ?>">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-lg"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
    
    <div class="tab-content" id="password-tab">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" novalidate>
            <input type="hidden" name="action" value="change_password">
            
            <div class="form-group">
                <label for="current_password"><i class="bi bi-key"></i> Contraseña Actual</label>
                <input type="password" id="current_password" name="current_password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="new_password"><i class="bi bi-lock"></i> Nueva Contraseña</label>
                <input type="password" id="new_password" name="new_password" class="form-control" required 
                       placeholder="Mínimo 6 caracteres">
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
    
    <div class="tab-content" id="history-tab">
        <h3><i class="bi bi-clock-history"></i> Mi Actividad Reciente</h3>
        
        <?php if (empty($recentHistory)) : ?>
            <p>No hay actividad reciente registrada.</p>
        <?php else : ?>
            <ul class="history-list">
                <?php foreach ($recentHistory as $entry) : 
                    // Determinar el icono basado en el tipo de actividad
                    $icon = 'bi-activity';
                    $actionClass = '';
                    
                    if (strpos($entry, 'Creó una cita') !== false) {
                        $icon = 'bi-calendar-plus';
                        $actionClass = 'action-create';
                    } elseif (strpos($entry, 'Actualizó una cita') !== false) {
                        $icon = 'bi-calendar-check';
                        $actionClass = 'action-update';
                    } elseif (strpos($entry, 'Eliminó una cita') !== false) {
                        $icon = 'bi-calendar-x';
                        $actionClass = 'action-delete';
                    } elseif (strpos($entry, 'Inicio de sesión') !== false) {
                        $icon = 'bi-box-arrow-in-right';
                        $actionClass = 'action-login';
                    } elseif (strpos($entry, 'Cierre de sesión') !== false) {
                        $icon = 'bi-box-arrow-right';
                        $actionClass = 'action-logout';
                    } elseif (strpos($entry, 'Actualización de perfil') !== false) {
                        $icon = 'bi-person-gear';
                        $actionClass = 'action-profile';
                    } elseif (strpos($entry, 'Cambio de contraseña') !== false) {
                        $icon = 'bi-key';
                        $actionClass = 'action-password';
                    }
                    
                    // Extraer y formatear timestamp
                    $timestamp = '';
                    if (preg_match('/\[(.*?)\]/', $entry, $matches)) {
                        $timestamp = $matches[1];
                        $formattedTimestamp = date('d/m/Y H:i', strtotime($timestamp));
                    }
                    
                    // Extraer la acción principal sin el timestamp
                    $mainAction = preg_replace('/\[.*?\]\s*/', '', $entry);
                    
                    // Extraer ID si existe
                    $idInfo = '';
                    if (preg_match('/\(ID: (\d+)\)/', $mainAction, $matches)) {
                        $idInfo = $matches[0];
                        $mainAction = str_replace($idInfo, '', $mainAction);
                    }
                    
                    // Extraer información de fecha si existe
                    $dateInfo = '';
                    if (preg_match('/Fecha: (\d{2}\/\d{2}\/\d{4}\s\d{2}:\d{2})/', $entry, $matches)) {
                        $dateInfo = $matches[0];
                    }
                    
                    // Extraer información extra si existe
                    $extraInfo = '';
                    if (preg_match('/- (.+?)(?=$|\s-\s)/', $entry, $matches)) {
                        // Verificar que no sea la fecha (que ya extrajimos)
                        if (strpos($matches[0], 'Fecha:') === false) {
                            $extraInfo = $matches[1];
                        }
                    }
                ?>
                    <li class="history-item <?php echo $actionClass; ?>">
                        <div class="history-icon">
                            <i class="bi <?php echo $icon; ?>"></i>
                        </div>
                        <div class="history-content">
                            <div class="history-main">
                                <?php echo htmlspecialchars(trim($mainAction)); ?>
                            </div>
                            <div class="history-details">
                                <span class="history-time">
                                    <i class="bi bi-clock"></i> <?php echo htmlspecialchars($formattedTimestamp ?? ''); ?>
                                </span>
                                <?php if (!empty($dateInfo)) : ?>
                                    <span class="history-date">
                                        <i class="bi bi-calendar-event"></i> <?php echo htmlspecialchars($dateInfo); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($extraInfo)) : ?>
                                    <span class="history-extra">
                                        <i class="bi bi-info-circle"></i> <?php echo htmlspecialchars($extraInfo); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($idInfo)) : ?>
                                    <span class="history-id">
                                        <i class="bi bi-hash"></i> <?php echo htmlspecialchars($idInfo); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <?php if ($currentUser['role'] === 'admin') : ?>
                <div class="history-more">
                    <a href="historial.php" class="btn btn-outline">
                        <i class="bi bi-clock-history"></i> Ver Historial Completo
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<?php
// Incluir el footer
include 'includes/footer.php';
?> 