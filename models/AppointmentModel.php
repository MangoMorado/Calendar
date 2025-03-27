<?php
class AppointmentModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function getAllAppointments() {
        $sql = "SELECT a.*, u.name as user_name, u.color as user_color 
                FROM appointments a 
                LEFT JOIN users u ON a.user_id = u.id 
                ORDER BY a.start_time ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAppointmentById($id) {
        $sql = "SELECT a.*, u.name as user_name, u.color as user_color 
                FROM appointments a 
                LEFT JOIN users u ON a.user_id = u.id 
                WHERE a.id = :id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function createAppointment($title, $description, $startTime, $endTime, $calendarType, $userId = null) {
        $sql = "INSERT INTO appointments (title, description, start_time, end_time, calendar_type, user_id) 
                VALUES (:title, :description, :start_time, :end_time, :calendar_type, :user_id)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':start_time', $startTime, PDO::PARAM_STR);
        $stmt->bindParam(':end_time', $endTime, PDO::PARAM_STR);
        $stmt->bindParam(':calendar_type', $calendarType, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    public function updateAppointment($id, $title, $description, $startTime, $endTime, $calendarType = null, $userId = null) {
        $sql = "UPDATE appointments 
                SET title = :title, 
                    description = :description, 
                    start_time = :start_time, 
                    end_time = :end_time";
        
        $params = [
            ':id' => $id,
            ':title' => $title,
            ':description' => $description,
            ':start_time' => $startTime,
            ':end_time' => $endTime
        ];
        
        if ($calendarType !== null) {
            $sql .= ", calendar_type = :calendar_type";
            $params[':calendar_type'] = $calendarType;
        }
        
        if ($userId !== null) {
            $sql .= ", user_id = :user_id";
            $params[':user_id'] = $userId;
        }
        
        $sql .= " WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function deleteAppointment($id) {
        $sql = "DELETE FROM appointments WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
} 