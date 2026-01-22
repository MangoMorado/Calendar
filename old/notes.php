<?php
/**
 * Controlador principal para la Libreta de Notas
 */

// Incluir archivos necesarios
require_once 'config/database.php';
require_once 'models/NoteModel.php';
require_once 'controllers/NoteController.php';
require_once 'includes/auth.php';

// Verificar que el usuario esté autenticado
requireAuth();

// Obtener la acción a realizar
$action = isset($_GET['action']) ? $_GET['action'] : 'index';

// Inicializar controlador
$noteController = new NoteController($conn);

// Ejecutar acción correspondiente
switch ($action) {
    case 'index':
        // Mostrar listado de notas
        $noteController->index();
        break;

    case 'view':
        // Ver detalle de una nota
        if (! isset($_GET['id'])) {
            $_SESSION['error_message'] = 'ID de nota no especificado';
            header('Location: notes.php');
            exit;
        }

        $noteId = (int) $_GET['id'];
        $noteController->view($noteId);
        break;

    case 'create':
        // Mostrar formulario de creación
        $noteController->create();
        break;

    case 'store':
        // Procesar el formulario de creación
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error_message'] = 'Método no permitido';
            header('Location: notes.php');
            exit;
        }

        $noteData = [
            'title' => $_POST['title'] ?? '',
            'content' => $_POST['content'] ?? '',
            'type' => $_POST['type'] ?? 'nota',
            'visibility' => $_POST['visibility'] ?? 'solo_yo',
        ];

        $noteController->store($noteData);
        break;

    case 'edit':
        // Mostrar formulario de edición
        if (! isset($_GET['id'])) {
            $_SESSION['error_message'] = 'ID de nota no especificado';
            header('Location: notes.php');
            exit;
        }

        $noteId = (int) $_GET['id'];
        $noteController->edit($noteId);
        break;

    case 'update':
        // Procesar el formulario de edición
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error_message'] = 'Método no permitido';
            header('Location: notes.php');
            exit;
        }

        if (! isset($_POST['id'])) {
            $_SESSION['error_message'] = 'ID de nota no especificado';
            header('Location: notes.php');
            exit;
        }

        $noteId = (int) $_POST['id'];
        $noteData = [
            'id' => $noteId,
            'title' => $_POST['title'] ?? '',
            'content' => $_POST['content'] ?? '',
            'type' => $_POST['type'] ?? 'nota',
            'visibility' => $_POST['visibility'] ?? 'solo_yo',
        ];

        $noteController->update($noteId, $noteData);
        break;

    case 'delete':
        // Eliminar una nota
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error_message'] = 'Método no permitido';
            header('Location: notes.php');
            exit;
        }

        if (! isset($_POST['id'])) {
            $_SESSION['error_message'] = 'ID de nota no especificado';
            header('Location: notes.php');
            exit;
        }

        $noteId = (int) $_POST['id'];
        $noteController->delete($noteId);
        break;

    default:
        // Acción no reconocida
        $_SESSION['error_message'] = 'Acción no válida';
        header('Location: notes.php');
        exit;
}
?> 