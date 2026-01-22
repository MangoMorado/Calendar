<?php
/**
 * Controlador para la Libreta de Notas
 * Gestiona las operaciones y lógica de negocio para notas
 */
require_once 'models/NoteModel.php';
require_once 'config/database.php';
require_once 'includes/auth.php';

class NoteController
{
    private $noteModel;

    private $currentUser;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $conn;
        $this->noteModel = new NoteModel($conn);
        $this->currentUser = getCurrentUser();

        // Verificar que el usuario esté autenticado
        requireAuth();
    }

    /**
     * Mostrar la página principal de notas
     */
    public function index()
    {
        $userId = $this->currentUser['id'];
        $notes = $this->noteModel->getNotes($userId);

        // Preparar datos para la vista
        $viewData = [
            'pageTitle' => 'Libreta de Notas | Mundo Animal',
            'currentUser' => $this->currentUser,
            'notes' => $notes,
        ];

        // Cargar la vista
        $this->loadView('notes/index', $viewData);
    }

    /**
     * Mostrar formulario para crear una nueva nota
     */
    public function create()
    {
        // Preparar datos para la vista
        $viewData = [
            'pageTitle' => 'Nueva Nota | Mundo Animal',
            'currentUser' => $this->currentUser,
            'formAction' => 'store',
        ];

        // Cargar la vista
        $this->loadView('notes/form', $viewData);
    }

    /**
     * Guardar una nueva nota
     */
    public function store()
    {
        // Verificar si se enviaron datos por POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('notes.php');

            return;
        }

        // Validar datos
        $errors = $this->validateNoteData($_POST);

        if (! empty($errors)) {
            // Si hay errores, volver al formulario con mensajes de error
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            $this->redirect('notes.php?action=create');

            return;
        }

        // Preparar datos para guardar
        $noteData = [
            'title' => $_POST['title'],
            'content' => $_POST['content'],
            'visibility' => $_POST['visibility'],
            'type' => $_POST['type'],
            'user_id' => $this->currentUser['id'],
        ];

        // Guardar la nota
        $noteId = $this->noteModel->createNote($noteData);

        if ($noteId) {
            $_SESSION['success_message'] = 'Nota creada correctamente';
            $this->redirect('notes.php');
        } else {
            $_SESSION['error_message'] = 'Error al crear la nota';
            $_SESSION['form_data'] = $_POST;
            $this->redirect('notes.php?action=create');
        }
    }

    /**
     * Mostrar formulario para editar una nota
     */
    public function edit($id)
    {
        $userId = $this->currentUser['id'];
        $note = $this->noteModel->getNoteById($id, $userId);

        if (! $note || ! $this->noteModel->canEditNote($id, $userId)) {
            $_SESSION['error_message'] = 'No tienes permiso para editar esta nota';
            $this->redirect('notes.php');

            return;
        }

        // Preparar datos para la vista
        $viewData = [
            'pageTitle' => 'Editar Nota | Mundo Animal',
            'currentUser' => $this->currentUser,
            'note' => $note,
            'formAction' => 'update',
        ];

        // Cargar la vista
        $this->loadView('notes/form', $viewData);
    }

    /**
     * Actualizar una nota existente
     */
    public function update()
    {
        // Verificar si se enviaron datos por POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
            $this->redirect('notes.php');

            return;
        }

        $id = $_POST['id'];
        $userId = $this->currentUser['id'];

        // Verificar permisos
        if (! $this->noteModel->canEditNote($id, $userId)) {
            $_SESSION['error_message'] = 'No tienes permiso para editar esta nota';
            $this->redirect('notes.php');

            return;
        }

        // Validar datos
        $errors = $this->validateNoteData($_POST);

        if (! empty($errors)) {
            // Si hay errores, volver al formulario con mensajes de error
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            $this->redirect("notes.php?action=edit&id={$id}");

            return;
        }

        // Preparar datos para actualizar
        $noteData = [
            'title' => $_POST['title'],
            'content' => $_POST['content'],
            'visibility' => $_POST['visibility'],
            'type' => $_POST['type'],
        ];

        // Actualizar la nota
        $success = $this->noteModel->updateNote($id, $noteData, $userId);

        if ($success) {
            $_SESSION['success_message'] = 'Nota actualizada correctamente';
            $this->redirect('notes.php');
        } else {
            $_SESSION['error_message'] = 'Error al actualizar la nota';
            $_SESSION['form_data'] = $_POST;
            $this->redirect("notes.php?action=edit&id={$id}");
        }
    }

    /**
     * Ver detalles de una nota
     */
    public function view($id)
    {
        $userId = $this->currentUser['id'];
        $note = $this->noteModel->getNoteById($id, $userId);

        if (! $note) {
            $_SESSION['error_message'] = 'Nota no encontrada o no tienes permiso para verla';
            $this->redirect('notes.php');

            return;
        }

        // Preparar datos para la vista
        $viewData = [
            'pageTitle' => "{$note['title']} | Mundo Animal",
            'currentUser' => $this->currentUser,
            'note' => $note,
            'canEdit' => $this->noteModel->canEditNote($id, $userId),
        ];

        // Cargar la vista
        $this->loadView('notes/view', $viewData);
    }

    /**
     * Eliminar una nota
     */
    public function delete($id)
    {
        $userId = $this->currentUser['id'];

        // Verificar permisos
        if (! $this->noteModel->canEditNote($id, $userId)) {
            $_SESSION['error_message'] = 'No tienes permiso para eliminar esta nota';
            $this->redirect('notes.php');

            return;
        }

        // Eliminar la nota
        $success = $this->noteModel->deleteNote($id, $userId);

        if ($success) {
            $_SESSION['success_message'] = 'Nota eliminada correctamente';
        } else {
            $_SESSION['error_message'] = 'Error al eliminar la nota';
        }

        $this->redirect('notes.php');
    }

    /**
     * Validar datos de la nota
     */
    private function validateNoteData($data)
    {
        $errors = [];

        // Validar título
        if (empty($data['title'])) {
            $errors['title'] = 'El título es obligatorio';
        } elseif (strlen($data['title']) > 100) {
            $errors['title'] = 'El título no debe exceder los 100 caracteres';
        }

        // Validar contenido
        if (empty($data['content'])) {
            $errors['content'] = 'El contenido es obligatorio';
        }

        // Validar visibilidad
        if (empty($data['visibility']) || ! in_array($data['visibility'], ['todos', 'solo_yo'])) {
            $errors['visibility'] = 'La visibilidad seleccionada no es válida';
        }

        // Validar tipo
        if (empty($data['type']) || ! in_array($data['type'], ['nota', 'sugerencia', 'otro'])) {
            $errors['type'] = 'El tipo seleccionado no es válido';
        }

        return $errors;
    }

    /**
     * Cargar una vista con datos
     */
    private function loadView($view, $data = [])
    {
        // Extraer variables para que estén disponibles en la vista
        extract($data);

        // Incluir el archivo de la vista
        require_once "views/{$view}.php";
    }

    /**
     * Redireccionar a una URL
     */
    private function redirect($url)
    {
        header("Location: {$url}");
        exit;
    }
}
?> 