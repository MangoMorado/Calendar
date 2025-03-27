<?php
class UserModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function getAllUsers() {
        $sql = "SELECT id, name, email, role, color, created_at FROM users ORDER BY created_at DESC";
        $result = mysqli_query($this->conn, $sql);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
    
    public function getUserById($id) {
        $sql = "SELECT id, name, email, role, color FROM users WHERE id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    public function createUser($name, $email, $password, $role = 'user', $color = '#0d6efd') {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (name, email, password, role, color) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $hashedPassword, $role, $color);
        return mysqli_stmt_execute($stmt);
    }
    
    public function updateUser($id, $name, $email, $role = null, $color = null) {
        $sql = "UPDATE users SET name = ?, email = ?";
        $params = [$name, $email];
        $types = "ss";
        
        if ($role !== null) {
            $sql .= ", role = ?";
            $params[] = $role;
            $types .= "s";
        }
        
        if ($color !== null) {
            $sql .= ", color = ?";
            $params[] = $color;
            $types .= "s";
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        $types .= "i";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        return mysqli_stmt_execute($stmt);
    }
    
    public function deleteUser($id) {
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }
    
    public function updatePassword($id, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $hashedPassword, $id);
        return mysqli_stmt_execute($stmt);
    }
    
    public function updateUserHistory($userId, $action, $details = null) {
        // Obtener historial actual
        $sql = "SELECT history FROM users WHERE id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        
        // Formatear la nueva entrada del historial con más detalle si se proporcionan
        $timestamp = date('Y-m-d H:i:s');
        $newEntry = "[$timestamp] $action";
        
        // Añadir detalles adicionales si se proporcionan
        if ($details !== null && is_array($details)) {
            if (isset($details['id'])) {
                $newEntry .= " (ID: {$details['id']})";
            }
            
            if (isset($details['date'])) {
                $formattedDate = date('d/m/Y H:i', strtotime($details['date']));
                $newEntry .= " - Fecha: $formattedDate";
            }
            
            if (isset($details['extra']) && !empty($details['extra'])) {
                $newEntry .= " - {$details['extra']}";
            }
        }
        
        // Actualizar historial
        $history = $user['history'] ? $user['history'] . "\n" . $newEntry : $newEntry;
        
        // Limitar el historial a las últimas 100 entradas para evitar un crecimiento excesivo
        $historyLines = explode("\n", $history);
        if (count($historyLines) > 100) {
            $historyLines = array_slice($historyLines, -100);
            $history = implode("\n", $historyLines);
        }
        
        $sql = "UPDATE users SET history = ? WHERE id = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $history, $userId);
        
        return mysqli_stmt_execute($stmt);
    }
} 