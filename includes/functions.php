<?php
// Incluir la configuración de la base de datos
require_once __DIR__ . '/../config/database.php';

/**
 * Obtener todas las citas para un rango de fechas
 */
function getAppointments($startDate, $endDate, $calendarType = null) {
    global $conn;
    
    // Construir la consulta base con JOIN
    $baseQuery = "SELECT 
                    a.*, 
                    COALESCE(u.name, 'Sin asignar') as user,
                    COALESCE(u.color, '#0d6efd') as user_color,
                    u.id as user_id
                  FROM appointments a 
                  LEFT JOIN users u ON a.user_id = u.id";
    
    // Añadir orden por fecha a todas las consultas
    $orderBy = " ORDER BY a.start_time ASC";
    
    // Cuando startDate y endDate son null, obtenemos todas las citas o filtramos solo por tipo
    if ($startDate === null || $endDate === null) {
        if ($calendarType && $calendarType !== 'general') {
            $sql = $baseQuery . " WHERE a.calendar_type = ?" . $orderBy;
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $calendarType);
        } else {
            $sql = $baseQuery . $orderBy;
            $stmt = mysqli_prepare($conn, $sql);
        }
    } else {
        // Filtrar por rango de fechas
        $sql = $baseQuery . " WHERE a.start_time >= ? AND a.start_time <= ?";
        
        // Si se especifica un tipo de calendario, filtrar por ese tipo
        // Para el calendario general, obtener todas las citas
        if ($calendarType && $calendarType !== 'general') {
            $sql .= " AND a.calendar_type = ?";
            $sql .= $orderBy;
            
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sss", $startDate, $endDate, $calendarType);
        } else {
            $sql .= $orderBy;
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ss", $startDate, $endDate);
        }
    }
    
    mysqli_stmt_execute($stmt);
    
    $result = mysqli_stmt_get_result($stmt);
    $appointments = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        // Log para depuración
        error_log("Cita con ID " . $row['id'] . " - Usuario: " . 
                 (isset($row['user_id']) ? $row['user_id'] : 'NULL') . 
                 " - Nombre: " . (isset($row['user']) ? $row['user'] : 'NULL'));
        
        $appointments[] = $row;
    }
    
    return $appointments;
}

/**
 * Obtener una cita por su ID
 */
function getAppointmentById($id) {
    global $conn;
    
    $sql = "SELECT 
                a.*,
                u.name as user,
                u.color as user_color,
                u.id as user_id
            FROM appointments a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE a.id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_fetch_assoc($result);
}

/**
 * Crear una nueva cita
 */
function createAppointment($title, $description, $startTime, $endTime, $calendarType = 'general', $allDay = false, $userId = null) {
    global $conn;
    
    // Log para depuración
    error_log("Creando cita: Título=$title, Tipo=$calendarType, UserId=" . var_export($userId, true));
    
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
    
    $sql = "INSERT INTO appointments (title, description, start_time, end_time, calendar_type, all_day, user_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    $allDayInt = $allDay ? 1 : 0;
    mysqli_stmt_bind_param($stmt, "sssssii", $title, $description, $startTime, $endTime, $calendarType, $allDayInt, $userId);
    
    if (mysqli_stmt_execute($stmt)) {
        $newId = mysqli_insert_id($conn);
        error_log("Cita creada con ID: $newId y Usuario: $userId");
        return $newId;
    } else {
        error_log("Error al crear cita: " . mysqli_error($conn));
        return false;
    }
}

/**
 * Actualizar una cita existente
 */
function updateAppointment($id, $title, $description, $startTime, $endTime, $calendarType = null, $allDay = null, $userId = null) {
    global $conn;
    
    // Log para depuración
    error_log("Actualizando cita: ID=$id, Título=$title, Tipo=$calendarType, UserId=" . var_export($userId, true));
    
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
    
    // Construir la consulta SQL basada en los parámetros proporcionados
    $sql = "UPDATE appointments SET title = ?, description = ?, start_time = ?, end_time = ?";
    $params = [$title, $description, $startTime, $endTime];
    $types = "ssss";
    
    if ($calendarType !== null) {
        $sql .= ", calendar_type = ?";
        $params[] = $calendarType;
        $types .= "s";
    }
    
    if ($allDay !== null) {
        $sql .= ", all_day = ?";
        $allDayInt = $allDay ? 1 : 0;
        $params[] = $allDayInt;
        $types .= "i";
    }
    
    if ($userId !== null) {
        $sql .= ", user_id = ?";
        $params[] = $userId;
        $types .= "i";
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $id;
    $types .= "i";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    
    $result = mysqli_stmt_execute($stmt);
    if ($result) {
        error_log("Cita ID: $id actualizada correctamente con Usuario: $userId");
    } else {
        error_log("Error al actualizar cita: " . mysqli_error($conn));
    }
    
    return $result;
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
    
    // Validar el ID
    $id = intval($id);
    if ($id <= 0) {
        error_log("ID de cita inválido: $id");
        return false;
    }
    
    // Validar las fechas
    if (empty($startTime) || empty($endTime)) {
        error_log("Fechas vacías para cita ID: $id");
        return false;
    }
    
    // Registrar en el log para depuración
    error_log("Actualizando fechas para cita ID: $id - Inicio: $startTime - Fin: $endTime");
    
    // Ejecutar la consulta de actualización
    $sql = "UPDATE appointments 
            SET start_time = ?, end_time = ?, updated_at = NOW() 
            WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssi", $startTime, $endTime, $id);
    
    $result = mysqli_stmt_execute($stmt);
    
    // Verificar si la actualización fue exitosa
    if ($result) {
        error_log("Fechas actualizadas exitosamente para cita ID: $id");
    } else {
        error_log("Error al actualizar fechas para cita ID: $id - " . mysqli_error($conn));
    }
    
    return $result;
}
?> 