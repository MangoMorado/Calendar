<?php
// Incluir la configuración de la base de datos
require_once __DIR__ . '/../config/database.php';

/**
 * Obtener todas las citas para un rango de fechas
 */
function getAppointments($startDate, $endDate, $calendarType = null) {
    global $conn;
    
    $sql = "SELECT * FROM appointments 
            WHERE start_time >= ? AND start_time <= ?";
    
    // Si se especifica un tipo de calendario, filtrar por ese tipo
    // Para el calendario general, obtener todas las citas
    if ($calendarType && $calendarType !== 'general') {
        $sql .= " AND calendar_type = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sss", $startDate, $endDate, $calendarType);
    } else {
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $startDate, $endDate);
    }
    
    mysqli_stmt_execute($stmt);
    
    $result = mysqli_stmt_get_result($stmt);
    $appointments = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $appointments[] = $row;
    }
    
    return $appointments;
}

/**
 * Obtener una cita por su ID
 */
function getAppointmentById($id) {
    global $conn;
    
    $sql = "SELECT * FROM appointments WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_fetch_assoc($result);
}

/**
 * Crear una nueva cita
 */
function createAppointment($title, $description, $startTime, $endTime, $calendarType = 'general') {
    global $conn;
    
    $sql = "INSERT INTO appointments (title, description, start_time, end_time, calendar_type) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssss", $title, $description, $startTime, $endTime, $calendarType);
    
    if (mysqli_stmt_execute($stmt)) {
        return mysqli_insert_id($conn);
    } else {
        return false;
    }
}

/**
 * Actualizar una cita existente
 */
function updateAppointment($id, $title, $description, $startTime, $endTime, $calendarType = null) {
    global $conn;
    
    // Si no se proporciona un tipo de calendario, mantener el existente
    if ($calendarType === null) {
        $sql = "UPDATE appointments 
                SET title = ?, description = ?, start_time = ?, end_time = ? 
                WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssi", $title, $description, $startTime, $endTime, $id);
    } else {
        $sql = "UPDATE appointments 
                SET title = ?, description = ?, start_time = ?, end_time = ?, calendar_type = ? 
                WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssssi", $title, $description, $startTime, $endTime, $calendarType, $id);
    }
    
    return mysqli_stmt_execute($stmt);
}

/**
 * Eliminar una cita
 */
function deleteAppointment($id) {
    global $conn;
    
    $sql = "DELETE FROM appointments WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    return mysqli_stmt_execute($stmt);
}

/**
 * Obtener las fechas de inicio y fin de la semana actual
 */
function getCurrentWeekDates() {
    $today = date('Y-m-d');
    $dayOfWeek = date('N', strtotime($today));
    
    // Calcular el primer día de la semana (lunes)
    $monday = date('Y-m-d', strtotime("-" . ($dayOfWeek - 1) . " days", strtotime($today)));
    
    // Calcular el último día de la semana (domingo)
    $sunday = date('Y-m-d', strtotime("+" . (7 - $dayOfWeek) . " days", strtotime($today)));
    
    return [
        'start' => $monday,
        'end' => $sunday
    ];
}

/**
 * Formatear fecha y hora para mostrar
 */
function formatDateTime($dateTime) {
    return date('d/m/Y H:i', strtotime($dateTime));
}

/**
 * Formatear solo la hora
 */
function formatTime($dateTime) {
    return date('H:i', strtotime($dateTime));
}

/**
 * Obtener todos los tipos de calendario disponibles
 */
function getCalendarTypes() {
    return [
        'general' => 'Calendario General',
        'estetico' => 'Calendario Estético',
        'veterinario' => 'Calendario Veterinario'
    ];
}

/**
 * Obtener el nombre del calendario según su tipo
 */
function getCalendarName($type) {
    $types = getCalendarTypes();
    return isset($types[$type]) ? $types[$type] : 'Calendario';
}
?> 