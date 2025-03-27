<?php
/**
 * API para la Libreta de Notas
 * Maneja peticiones AJAX relacionadas con las notas
 */

// Incluir archivos de configuración, funciones y autenticación
require_once '../config/database.php';
require_once '../models/NoteModel.php';
require_once '../includes/auth.php';

// Verificar que el usuario esté autenticado
requireAuth();

// Establecer headers para JSON
header('Content-Type: application/json');

// Obtener el usuario actual
$currentUser = getCurrentUser();

// Crear instancia del modelo
$noteModel = new NoteModel($conn);

// Manejar solicitudes GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Verificar la acción solicitada
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    // Obtener una nota específica
    if ($action === 'get_note' && isset($_GET['id'])) {
        $noteId = (int) $_GET['id'];
        $userId = $currentUser['id'];
        
        // Obtener la nota
        $note = $noteModel->getNoteById($noteId, $userId);
        
        if ($note) {
            // Verificar si el usuario puede editar la nota
            $canEdit = $noteModel->canEditNote($noteId, $userId);
            
            echo json_encode([
                'success' => true,
                'note' => $note,
                'can_edit' => $canEdit
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Nota no encontrada o no tienes permiso para verla'
            ]);
        }
        exit;
    } 
    // Obtener todas las notas del usuario
    elseif ($action === 'get_notes') {
        $userId = $currentUser['id'];
        $notes = $noteModel->getNotes($userId);
        
        echo json_encode([
            'success' => true,
            'notes' => $notes
        ]);
        exit;
    }
    
    // Acción no reconocida
    echo json_encode([
        'success' => false,
        'message' => 'Acción no reconocida'
    ]);
    exit;
}

// Manejar solicitudes POST (para futuras ampliaciones como AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar la acción solicitada
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Acción no reconocida
    echo json_encode([
        'success' => false,
        'message' => 'Acción no reconocida o no implementada'
    ]);
    exit;
}

// Método no permitido
echo json_encode([
    'success' => false,
    'message' => 'Método no permitido'
]);
?> 