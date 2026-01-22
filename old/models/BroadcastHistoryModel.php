<?php
/**
 * Modelo para gestionar el historial de difusiones
 */
class BroadcastHistoryModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Crear un nuevo registro de difusión
     */
    public function createBroadcast($data)
    {
        $sql = 'INSERT INTO broadcast_history 
                (list_id, message, image_path, total_contacts, user_id, status) 
                VALUES (?, ?, ?, ?, ?, ?)';

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'issiis',
            $data['list_id'],
            $data['message'],
            $data['image_path'],
            $data['total_contacts'],
            $data['user_id'],
            $data['status']
        );

        if (mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($this->conn);
        }

        return false;
    }

    /**
     * Actualizar el estado de una difusión
     */
    public function updateBroadcastStatus($broadcastId, $status, $sentSuccessfully = null, $sentFailed = null)
    {
        $sql = "UPDATE broadcast_history 
                SET status = ?, 
                    sent_successfully = COALESCE(?, sent_successfully),
                    sent_failed = COALESCE(?, sent_failed),
                    completed_at = CASE WHEN ? IN ('completed', 'failed') THEN NOW() ELSE completed_at END
                WHERE id = ?";

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'siisi', $status, $sentSuccessfully, $sentFailed, $status, $broadcastId);

        return mysqli_stmt_execute($stmt);
    }

    /**
     * Obtener historial de difusiones de un usuario
     */
    public function getBroadcastHistory($userId, $limit = 50, $offset = 0)
    {
        $sql = 'SELECT 
                    bh.*,
                    bl.name as list_name,
                    u.name as user_name
                FROM broadcast_history bh
                LEFT JOIN broadcast_lists bl ON bh.list_id = bl.id
                LEFT JOIN users u ON bh.user_id = u.id
                WHERE bh.user_id = ?
                ORDER BY bh.started_at DESC
                LIMIT ? OFFSET ?';

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'iii', $userId, $limit, $offset);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $history = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $history[] = $row;
        }

        return $history;
    }

    /**
     * Obtener una difusión específica por ID
     */
    public function getBroadcastById($broadcastId, $userId = null)
    {
        $sql = 'SELECT 
                    bh.*,
                    bl.name as list_name,
                    u.name as user_name
                FROM broadcast_history bh
                LEFT JOIN broadcast_lists bl ON bh.list_id = bl.id
                LEFT JOIN users u ON bh.user_id = u.id
                WHERE bh.id = ?';

        $params = [$broadcastId];
        $types = 'i';

        if ($userId) {
            $sql .= ' AND bh.user_id = ?';
            $params[] = $userId;
            $types .= 'i';
        }

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        return mysqli_fetch_assoc($result);
    }

    /**
     * Agregar detalle de envío a un contacto
     */
    public function addBroadcastDetail($data)
    {
        $sql = 'INSERT INTO broadcast_details 
                (broadcast_id, contact_id, contact_number, status, error_message, sent_at) 
                VALUES (?, ?, ?, ?, ?, ?)';

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'iissss',
            $data['broadcast_id'],
            $data['contact_id'],
            $data['contact_number'],
            $data['status'],
            $data['error_message'],
            $data['sent_at']
        );

        return mysqli_stmt_execute($stmt);
    }

    /**
     * Actualizar detalle de envío
     */
    public function updateBroadcastDetail($detailId, $status, $errorMessage = null, $sentAt = null)
    {
        $sql = 'UPDATE broadcast_details 
                SET status = ?, 
                    error_message = ?, 
                    sent_at = ?
                WHERE id = ?';

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'sssi', $status, $errorMessage, $sentAt, $detailId);

        return mysqli_stmt_execute($stmt);
    }

    /**
     * Obtener detalles de una difusión
     */
    public function getBroadcastDetails($broadcastId)
    {
        $sql = 'SELECT 
                    bd.*,
                    c.pushName,
                    c.number
                FROM broadcast_details bd
                LEFT JOIN contacts c ON bd.contact_id = c.id
                WHERE bd.broadcast_id = ?
                ORDER BY bd.sent_at DESC';

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $broadcastId);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $details = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $details[] = $row;
        }

        return $details;
    }

    /**
     * Obtener estadísticas de difusiones
     */
    public function getBroadcastStats($userId)
    {
        $sql = "SELECT 
                    COUNT(*) as total_broadcasts,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed,
                    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
                    SUM(sent_successfully) as total_sent,
                    SUM(sent_failed) as total_failed
                FROM broadcast_history 
                WHERE user_id = ?";

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        return mysqli_fetch_assoc($result);
    }

    /**
     * Obtener difusiones recientes (últimas 24 horas)
     */
    public function getRecentBroadcasts($userId, $hours = 24)
    {
        $sql = 'SELECT 
                    bh.*,
                    bl.name as list_name
                FROM broadcast_history bh
                LEFT JOIN broadcast_lists bl ON bh.list_id = bl.id
                WHERE bh.user_id = ? 
                AND bh.started_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                ORDER BY bh.started_at DESC';

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ii', $userId, $hours);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $broadcasts = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $broadcasts[] = $row;
        }

        return $broadcasts;
    }

    /**
     * Eliminar difusiones antiguas (más de 30 días)
     */
    public function cleanupOldBroadcasts($days = 30)
    {
        $sql = 'DELETE FROM broadcast_history 
                WHERE started_at < DATE_SUB(NOW(), INTERVAL ? DAY)';

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $days);

        return mysqli_stmt_execute($stmt);
    }

    /**
     * Verificar si el usuario puede acceder a la difusión
     */
    public function canAccessBroadcast($broadcastId, $userId)
    {
        $sql = 'SELECT COUNT(*) as count FROM broadcast_history WHERE id = ? AND user_id = ?';

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ii', $broadcastId, $userId);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        return $row['count'] > 0;
    }
}
?> 