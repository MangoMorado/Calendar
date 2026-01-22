<?php
/**
 * Modelo para la Libreta de Notas
 * Gestiona las operaciones de base de datos para notas
 */
class NoteModel
{
    private $conn;

    /**
     * Constructor
     */
    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Obtener todas las notas que un usuario puede ver
     *
     * @param  int  $userId  ID del usuario actual
     * @return array Lista de notas
     */
    public function getNotes($userId)
    {
        // Consulta para obtener notas públicas (visibilidad = 'todos')
        // O notas privadas creadas por el usuario actual (visibilidad = 'solo_yo' AND user_id = $userId)
        $sql = "SELECT n.*, u.name as author_name 
                FROM notes n 
                LEFT JOIN users u ON n.user_id = u.id 
                WHERE n.visibility = 'todos' OR (n.visibility = 'solo_yo' AND n.user_id = ?) 
                ORDER BY n.created_at DESC";

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $notes = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $notes[] = $row;
        }

        return $notes;
    }

    /**
     * Obtener una nota específica por ID
     *
     * @param  int  $id  ID de la nota
     * @param  int  $userId  ID del usuario actual (para verificar permisos)
     * @return array|bool Datos de la nota o false si no se encuentra/no tiene permiso
     */
    public function getNoteById($id, $userId)
    {
        $sql = "SELECT n.*, u.name as author_name 
                FROM notes n 
                LEFT JOIN users u ON n.user_id = u.id 
                WHERE n.id = ? AND (n.visibility = 'todos' OR (n.visibility = 'solo_yo' AND n.user_id = ?))";

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ii', $id, $userId);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        return mysqli_fetch_assoc($result);
    }

    /**
     * Crear una nueva nota
     *
     * @param  array  $noteData  Datos de la nota (title, content, visibility, type, user_id)
     * @return int|bool ID de la nota creada o false si falla
     */
    public function createNote($noteData)
    {
        $sql = 'INSERT INTO notes (title, content, visibility, type, user_id, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())';

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param(
            $stmt,
            'ssssi',
            $noteData['title'],
            $noteData['content'],
            $noteData['visibility'],
            $noteData['type'],
            $noteData['user_id']
        );

        if (mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($this->conn);
        } else {
            return false;
        }
    }

    /**
     * Actualizar una nota existente
     *
     * @param  int  $id  ID de la nota
     * @param  array  $noteData  Datos actualizados de la nota
     * @param  int  $userId  ID del usuario actual (para verificar permisos)
     * @return bool True si se actualiza correctamente, false en caso contrario
     */
    public function updateNote($id, $noteData, $userId)
    {
        // Verificar que el usuario es el propietario de la nota o admin
        if (! $this->canEditNote($id, $userId)) {
            return false;
        }

        $sql = 'UPDATE notes 
                SET title = ?, content = ?, visibility = ?, type = ?, updated_at = NOW() 
                WHERE id = ?';

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param(
            $stmt,
            'ssssi',
            $noteData['title'],
            $noteData['content'],
            $noteData['visibility'],
            $noteData['type'],
            $id
        );

        return mysqli_stmt_execute($stmt);
    }

    /**
     * Eliminar una nota
     *
     * @param  int  $id  ID de la nota
     * @param  int  $userId  ID del usuario actual (para verificar permisos)
     * @return bool True si se elimina correctamente, false en caso contrario
     */
    public function deleteNote($id, $userId)
    {
        // Verificar que el usuario es el propietario de la nota o admin
        if (! $this->canEditNote($id, $userId)) {
            return false;
        }

        $sql = 'DELETE FROM notes WHERE id = ?';
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $id);

        return mysqli_stmt_execute($stmt);
    }

    /**
     * Verificar si un usuario puede editar/eliminar una nota
     *
     * @param  int  $noteId  ID de la nota
     * @param  int  $userId  ID del usuario
     * @return bool True si puede editar/eliminar, false en caso contrario
     */
    public function canEditNote($noteId, $userId)
    {
        // Obtener el rol del usuario desde la sesión
        $isAdmin = isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';

        // Si es admin, siempre puede editar
        if ($isAdmin) {
            return true;
        }

        // Si no es admin, verificar que sea el creador de la nota
        $sql = 'SELECT COUNT(*) as count FROM notes WHERE id = ? AND user_id = ?';
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ii', $noteId, $userId);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        return $row['count'] > 0;
    }
}
?> 