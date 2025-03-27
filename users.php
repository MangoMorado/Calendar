<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'controllers/UserController.php';

// Verificar si el usuario está autenticado
requireAuth();

// Obtener el usuario actual
$currentUser = getCurrentUser();

// Crear instancia del controlador
$controller = new UserController($conn, $currentUser);

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