<?php
/**
 * Modelo para gestionar las listas de difusión
 */
class BroadcastListModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Obtener todas las listas de difusión de un usuario
     */
    public function getListsByUser($userId) {
        $sql = "SELECT 
                    bl.*,
                    COUNT(blc.contact_id) as contact_count,
                    u.name as user_name
                FROM broadcast_lists bl
                LEFT JOIN broadcast_list_contacts blc ON bl.id = blc.list_id
                LEFT JOIN users u ON bl.user_id = u.id
                WHERE bl.user_id = ?
                GROUP BY bl.id
                ORDER BY bl.created_at DESC";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        $lists = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $lists[] = $row;
        }
        
        return $lists;
    }
    
    /**
     * Obtener una lista específica por ID
     */
    public function getListById($listId, $userId = null) {
        $sql = "SELECT 
                    bl.*,
                    u.name as user_name
                FROM broadcast_lists bl
                LEFT JOIN users u ON bl.user_id = u.id
                WHERE bl.id = ?";
        
        $params = [$listId];
        $types = "i";
        
        if ($userId) {
            $sql .= " AND bl.user_id = ?";
            $params[] = $userId;
            $types .= "i";
        }
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    /**
     * Crear una nueva lista de difusión
     */
    public function createList($data) {
        $sql = "INSERT INTO broadcast_lists (name, description, user_id) VALUES (?, ?, ?)";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $data['name'], $data['description'], $data['user_id']);
        
        if (mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($this->conn);
        }
        
        return false;
    }
    
    /**
     * Actualizar una lista de difusión
     */
    public function updateList($listId, $data, $userId) {
        $sql = "UPDATE broadcast_lists 
                SET name = ?, description = ?, is_active = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        $isActive = $data['is_active'] ? 1 : 0;
        mysqli_stmt_bind_param($stmt, "ssiii", $data['name'], $data['description'], $isActive, $listId, $userId);
        
        return mysqli_stmt_execute($stmt);
    }
    
    /**
     * Eliminar una lista de difusión
     */
    public function deleteList($listId, $userId) {
        $sql = "DELETE FROM broadcast_lists WHERE id = ? AND user_id = ?";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $listId, $userId);
        
        return mysqli_stmt_execute($stmt);
    }
    
    /**
     * Agregar contactos a una lista
     */
    public function addContactsToList($listId, $contactIds) {
        if (empty($contactIds)) {
            return true;
        }
        
        $values = [];
        $types = "";
        $params = [];
        
        foreach ($contactIds as $contactId) {
            $values[] = "(?, ?)";
            $types .= "ii";
            $params[] = $listId;
            $params[] = $contactId;
        }
        
        $sql = "INSERT IGNORE INTO broadcast_list_contacts (list_id, contact_id) VALUES " . implode(", ", $values);
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        
        return mysqli_stmt_execute($stmt);
    }
    
    /**
     * Remover contactos de una lista
     */
    public function removeContactsFromList($listId, $contactIds) {
        if (empty($contactIds)) {
            return true;
        }
        
        $placeholders = str_repeat("?,", count($contactIds) - 1) . "?";
        $sql = "DELETE FROM broadcast_list_contacts WHERE list_id = ? AND contact_id IN ($placeholders)";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        $params = array_merge([$listId], $contactIds);
        $types = "i" . str_repeat("i", count($contactIds));
        
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        
        return mysqli_stmt_execute($stmt);
    }
    
    /**
     * Obtener contactos de una lista
     */
    public function getContactsInList($listId) {
        $sql = "SELECT 
                    c.*,
                    blc.added_at
                FROM contacts c
                INNER JOIN broadcast_list_contacts blc ON c.id = blc.contact_id
                WHERE blc.list_id = ?
                ORDER BY blc.added_at DESC";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $listId);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        $contacts = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $contacts[] = $row;
        }
        
        return $contacts;
    }
    
    /**
     * Obtener contactos disponibles (no en la lista)
     */
    public function getAvailableContacts($listId) {
        $sql = "SELECT c.*
                FROM contacts c
                WHERE c.id NOT IN (
                    SELECT contact_id 
                    FROM broadcast_list_contacts 
                    WHERE list_id = ?
                )
                ORDER BY c.pushName, c.number";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $listId);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        $contacts = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $contacts[] = $row;
        }
        
        return $contacts;
    }
    
    /**
     * Verificar si el usuario puede acceder a la lista
     */
    public function canAccessList($listId, $userId) {
        $sql = "SELECT COUNT(*) as count FROM broadcast_lists WHERE id = ? AND user_id = ?";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $listId, $userId);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        return $row['count'] > 0;
    }
    
    /**
     * Buscar listas por nombre
     */
    public function searchLists($userId, $searchTerm) {
        $sql = "SELECT 
                    bl.*,
                    COUNT(blc.contact_id) as contact_count,
                    u.name as user_name
                FROM broadcast_lists bl
                LEFT JOIN broadcast_list_contacts blc ON bl.id = blc.list_id
                LEFT JOIN users u ON bl.user_id = u.id
                WHERE bl.user_id = ? AND (bl.name LIKE ? OR bl.description LIKE ?)
                GROUP BY bl.id
                ORDER BY bl.created_at DESC";
        
        $searchPattern = "%$searchTerm%";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "iss", $userId, $searchPattern, $searchPattern);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        $lists = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $lists[] = $row;
        }
        
        return $lists;
    }
}
?> 