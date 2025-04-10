<?php
// Activar registro de errores
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/php_error.log');
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'controllers/UserController.php';

// Verificar si el usuario está autenticado
requireAuth();

// Obtener el usuario actual
$currentUser = getCurrentUser();

// Crear instancia del controlador
$controller = new UserController($conn, $currentUser);

// Procesar la actualización de visibilidad del calendario si se ha enviado
if (isset($_POST['toggle_calendar'])) {
    // Debug: registrar los datos del formulario
    error_log("POST data: " . print_r($_POST, true));
    
    $userId = $_POST['user_id'] ?? 0;
    $visible = isset($_POST['calendar_visible']) ? 1 : 0;
    
    // Imprimir datos para depuración
    error_log("Toggle calendar - User ID: $userId, Visible: $visible");
    
    if ($userId > 0) {
        // Actualizar la configuración de visibilidad del calendario
        $sql = "UPDATE users SET calendar_visible = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $visible, $userId);
        $result = mysqli_stmt_execute($stmt);
        
        if ($result) {
            error_log("Actualización exitosa de calendar_visible para usuario $userId a $visible");
        } else {
            error_log("Error al actualizar calendar_visible: " . mysqli_error($conn));
        }
        
        // Registrar la acción
        if ($currentUser['role'] === 'admin') {
            // Obtener información del usuario
            $userSql = "SELECT name FROM users WHERE id = ?";
            $userStmt = mysqli_prepare($conn, $userSql);
            mysqli_stmt_bind_param($userStmt, "i", $userId);
            mysqli_stmt_execute($userStmt);
            $userResult = mysqli_stmt_get_result($userStmt);
            $user = mysqli_fetch_assoc($userResult);
            
            // Registrar la acción en el historial
            $action = $visible ? "activó" : "desactivó";
            $controller->getModel()->updateUserHistory(
                $currentUser['id'], 
                "$action el calendario del usuario: '{$user['name']}'",
                ['id' => $userId]
            );
        }
        
        $_SESSION['success'] = "Visibilidad del calendario actualizada correctamente";
    }
    
    // Redirigir a la lista de usuarios
    header('Location: users.php');
    exit;
}

// Determinar la acción
$action = $_GET['action'] ?? 'index';

// Manejar la acción correspondiente
switch ($action) {
    case 'create':
        $controller->create();
        break;
        
    case 'edit':
        if (!isset($_GET['id'])) {
            header('Location: users.php');
            exit;
        }
        $controller->edit($_GET['id']);
        break;
        
    case 'delete':
        if (!isset($_GET['id'])) {
            header('Location: users.php');
            exit;
        }
        $controller->delete($_GET['id']);
        break;
        
    default:
        $controller->index();
} 