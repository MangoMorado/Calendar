<?php
// Incluir la configuración de la base de datos
require_once __DIR__ . '/../config/database.php';

/**
 * Obtener todas las citas para un rango de fechas
 */
function getAppointments($startDate, $endDate, $calendarType = null) {
    global $conn;
    
    // Cuando startDate y endDate son null, obtenemos todas las citas o filtramos solo por tipo
    if ($startDate === null || $endDate === null) {
        if ($calendarType && $calendarType !== 'general') {
            $sql = "SELECT * FROM appointments WHERE calendar_type = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $calendarType);
        } else {
            $sql = "SELECT * FROM appointments";
            $stmt = mysqli_prepare($conn, $sql);
        }
    } else {
        // Filtrar por rango de fechas
        $sql = "SELECT * FROM appointments WHERE start_time >= ? AND start_time <= ?";
        
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
function createAppointment($title, $description, $startTime, $endTime, $calendarType = 'general', $allDay = false) {
    global $conn;
    
    // Si es un evento de todo el día, ajustar las horas para asegurar que se muestre correctamente
    if ($allDay) {
        // Extraer solo la fecha del startTime y establecer hora a 00:00:00
        $dateParts = explode('T', $startTime);
        if (count($dateParts) > 1) {
            $startTime = $dateParts[0] . ' 00:00:00';
        }
        
        // Si el evento termina el mismo día, moverlo al día siguiente para FullCalendar
        $startDate = new DateTime($startTime);
        $endDate = new DateTime($endTime);
        
        // Si la fecha de fin es igual a la de inicio o es un string de fecha sin hora
        // establecer la fecha de fin al día siguiente
        if ($startDate->format('Y-m-d') === $endDate->format('Y-m-d') || 
            strpos($endTime, '00:00:00') !== false) {
            $endDate = clone $startDate;
            $endDate->modify('+1 day');
            $endTime = $endDate->format('Y-m-d H:i:s');
        }
    }
    
    $sql = "INSERT INTO appointments (title, description, start_time, end_time, calendar_type, all_day) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    $allDayInt = $allDay ? 1 : 0;
    mysqli_stmt_bind_param($stmt, "sssssi", $title, $description, $startTime, $endTime, $calendarType, $allDayInt);
    
    if (mysqli_stmt_execute($stmt)) {
        return mysqli_insert_id($conn);
    } else {
        return false;
    }
}

/**
 * Actualizar una cita existente
 */
function updateAppointment($id, $title, $description, $startTime, $endTime, $calendarType = null, $allDay = null) {
    global $conn;
    
    // Si es un evento de todo el día, ajustar las horas para asegurar que se muestre correctamente
    if ($allDay === true) {
        // Extraer solo la fecha del startTime y establecer hora a 00:00:00
        $dateParts = explode('T', $startTime);
        if (count($dateParts) > 1) {
            $startTime = $dateParts[0] . ' 00:00:00';
        }
        
        // Si el evento termina el mismo día, moverlo al día siguiente para FullCalendar
        $startDate = new DateTime($startTime);
        $endDate = new DateTime($endTime);
        
        // Si la fecha de fin es igual a la de inicio o es un string de fecha sin hora
        // establecer la fecha de fin al día siguiente
        if ($startDate->format('Y-m-d') === $endDate->format('Y-m-d') || 
            strpos($endTime, '00:00:00') !== false) {
            $endDate = clone $startDate;
            $endDate->modify('+1 day');
            $endTime = $endDate->format('Y-m-d H:i:s');
        }
    }
    
    // Si no se proporciona un tipo de calendario, mantener el existente
    if ($calendarType === null && $allDay === null) {
        $sql = "UPDATE appointments 
                SET title = ?, description = ?, start_time = ?, end_time = ? 
                WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssi", $title, $description, $startTime, $endTime, $id);
    } else if ($calendarType !== null && $allDay === null) {
        $sql = "UPDATE appointments 
                SET title = ?, description = ?, start_time = ?, end_time = ?, calendar_type = ? 
                WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssssi", $title, $description, $startTime, $endTime, $calendarType, $id);
    } else if ($calendarType === null && $allDay !== null) {
        $sql = "UPDATE appointments 
                SET title = ?, description = ?, start_time = ?, end_time = ?, all_day = ? 
                WHERE id = ?";
        
        $allDayInt = $allDay ? 1 : 0;
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssis", $title, $description, $startTime, $endTime, $allDayInt, $id);
    } else {
        $sql = "UPDATE appointments 
                SET title = ?, description = ?, start_time = ?, end_time = ?, calendar_type = ?, all_day = ? 
                WHERE id = ?";
        
        $allDayInt = $allDay ? 1 : 0;
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssssis", $title, $description, $startTime, $endTime, $calendarType, $allDayInt, $id);
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

/**
 * Actualizar solo las fechas de una cita (para drag & drop)
 */
function updateAppointmentDates($id, $startTime, $endTime) {
    global $conn;
    
    $sql = "UPDATE appointments 
            SET start_time = ?, end_time = ? 
            WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssi", $startTime, $endTime, $id);
    
    return mysqli_stmt_execute($stmt);
}
?> 