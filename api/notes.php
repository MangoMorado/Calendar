<?php
/**
 * API para la Libreta de Notas
 * Maneja peticiones AJAX relacionadas con las notas
 */

// Incluir archivos de configuración, funciones y autenticación
require_once '../config/database.php';
require_once '../models/NoteModel.php';
require_once '../includes/auth.php';
require_once '../includes/api/jwt.php';

// Configurar headers CORS y JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // En producción, especificar los dominios permitidos
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificar autenticación usando JWT
try {
    $payload = requireJWTAuth();
    $currentUserId = $payload['user_id'] ?? null;
    $userRole = $payload['role'] ?? '';
    
    if (!$currentUserId) {
        apiResponse(false, 'Usuario no identificado', null, 401);
    }
} catch (Exception $e) {
    apiResponse(false, 'Error de autenticación: ' . $e->getMessage(), null, 401);
}

// Crear instancia del modelo
$noteModel = new NoteModel($conn);

// Método GET para obtener notas
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Verificar si se solicita una nota específica
        if (isset($_GET['id'])) {
            $noteId = (int) $_GET['id'];
            
            // Obtener la nota
            $note = $noteModel->getNoteById($noteId, $currentUserId);
            
            if ($note) {
                // Verificar si el usuario puede editar la nota
                $canEdit = $noteModel->canEditNote($noteId, $currentUserId);
                
                apiResponse(true, 'Nota obtenida correctamente', [
                    'note' => $note,
                    'can_edit' => $canEdit
                ]);
            } else {
                apiResponse(false, 'Nota no encontrada o no tienes permiso para verla', null, 404);
            }
        } 
        // Obtener todas las notas del usuario
        else {
            $notes = $noteModel->getNotes($currentUserId);
            
            apiResponse(true, 'Notas obtenidas correctamente', [
                'notes' => $notes
            ]);
        }
    } catch (Exception $e) {
        apiResponse(false, 'Error al obtener notas: ' . $e->getMessage(), null, 500);
    }
}

// Método POST para crear una nota
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Obtener datos del cuerpo de la petición
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);
        
        // Si no hay datos JSON, usar datos de formulario tradicionales
        if (!$data) {
            $data = $_POST;
        }
        
        // Validar campos requeridos
        if (empty($data['title']) || empty($data['content'])) {
            apiResponse(false, 'Faltan campos requeridos: título y contenido', null, 400);
        }
        
        // Obtener datos
        $title = $data['title'];
        $content = $data['content'];
        $type = $data['type'] ?? 'nota';
        $visibility = $data['visibility'] ?? 'solo_yo';
        
        // Validar tipo y visibilidad
        if (!in_array($type, ['nota', 'sugerencia', 'otro'])) {
            $type = 'nota';
        }
        
        if (!in_array($visibility, ['solo_yo', 'todos'])) {
            $visibility = 'solo_yo';
        }
        
        // Crear la nota
        $noteId = $noteModel->createNote([
            'title' => $title,
            'content' => $content,
            'type' => $type,
            'visibility' => $visibility,
            'user_id' => $currentUserId
        ]);
        
        if ($noteId) {
            apiResponse(true, 'Nota creada correctamente', ['id' => $noteId]);
        } else {
            apiResponse(false, 'Error al crear la nota', null, 500);
        }
    } catch (Exception $e) {
        apiResponse(false, 'Error al procesar la solicitud: ' . $e->getMessage(), null, 500);
    }
}

// Método PUT para actualizar una nota
else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        // Obtener datos JSON
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);
        
        if (!$data) {
            apiResponse(false, 'Datos inválidos', null, 400);
        }
        
        // Verificar ID y campos requeridos
        if (!isset($data['id']) || empty($data['title']) || empty($data['content'])) {
            apiResponse(false, 'Faltan campos requeridos: id, título y contenido', null, 400);
        }
        
        $noteId = (int) $data['id'];
        
        // Verificar si el usuario puede editar la nota
        if (!$noteModel->canEditNote($noteId, $currentUserId)) {
            apiResponse(false, 'No tienes permiso para editar esta nota', null, 403);
        }
        
        // Obtener datos
        $title = $data['title'];
        $content = $data['content'];
        $type = $data['type'] ?? 'nota';
        $visibility = $data['visibility'] ?? 'solo_yo';
        
        // Validar tipo y visibilidad
        if (!in_array($type, ['nota', 'sugerencia', 'otro'])) {
            $type = 'nota';
        }
        
        if (!in_array($visibility, ['solo_yo', 'todos'])) {
            $visibility = 'solo_yo';
        }
        
        // Actualizar la nota
        $success = $noteModel->updateNote($noteId, [
            'title' => $title,
            'content' => $content,
            'type' => $type,
            'visibility' => $visibility
        ]);
        
        if ($success) {
            apiResponse(true, 'Nota actualizada correctamente');
        } else {
            apiResponse(false, 'Error al actualizar la nota', null, 500);
        }
    } catch (Exception $e) {
        apiResponse(false, 'Error al procesar la solicitud: ' . $e->getMessage(), null, 500);
    }
}

// Método DELETE para eliminar una nota
else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        // Verificar si hay ID en la URL
        $noteId = isset($_GET['id']) ? (int) $_GET['id'] : null;
        
        // Si no hay ID en la URL, intentar obtenerlo del cuerpo
        if (!$noteId) {
            $jsonData = file_get_contents('php://input');
            $data = json_decode($jsonData, true);
            $noteId = isset($data['id']) ? (int) $data['id'] : null;
        }
        
        if (!$noteId) {
            apiResponse(false, 'ID de nota no proporcionado', null, 400);
        }
        
        // Verificar si el usuario puede eliminar la nota
        if (!$noteModel->canEditNote($noteId, $currentUserId)) {
            apiResponse(false, 'No tienes permiso para eliminar esta nota', null, 403);
        }
        
        // Eliminar la nota
        $success = $noteModel->deleteNote($noteId);
        
        if ($success) {
            apiResponse(true, 'Nota eliminada correctamente');
        } else {
            apiResponse(false, 'Error al eliminar la nota', null, 500);
        }
    } catch (Exception $e) {
        apiResponse(false, 'Error al procesar la solicitud: ' . $e->getMessage(), null, 500);
    }
}

// Método no permitido
else {
    apiResponse(false, 'Método no permitido', null, 405);
}
?> 