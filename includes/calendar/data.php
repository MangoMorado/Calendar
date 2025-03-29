<?php
/**
 * Calendar Data Processing
 * Este archivo contiene funciones relacionadas con el procesamiento de datos del calendario
 */

// Función para obtener las citas según el tipo de calendario
function getCalendarData($calendarType = 'general') {
    global $conn;
    
    // Validar el tipo de calendario
    $availableCalendars = getCalendarTypes();
    if (!array_key_exists($calendarType, $availableCalendars)) {
        $calendarType = 'general';
    }
    
    // Obtener todos los usuarios para asignarlos a las citas
    $usersQuery = "SELECT id, name, COALESCE(color, '#3788d8') as color FROM users";
    $usersResult = mysqli_query($conn, $usersQuery);
    $users = [];
    
    while ($user = mysqli_fetch_assoc($usersResult)) {
        $users[$user['id']] = $user;
    }
    
    // Construir la consulta SQL para obtener las citas con la información del usuario
    $sql = "SELECT a.*, u.name as user, COALESCE(u.color, '#3788d8') as user_color 
            FROM appointments a 
            LEFT JOIN users u ON a.user_id = u.id";
    
    // Filtrar por tipo de calendario si no es el general
    if ($calendarType !== 'general') {
        $sql .= " WHERE a.calendar_type = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $calendarType);
    } else {
        $stmt = mysqli_prepare($conn, $sql);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Preparar los datos para FullCalendar (formato JSON)
    $events = [];
    while ($appointment = mysqli_fetch_assoc($result)) {
        // Usar el color del usuario asignado, o un color predeterminado
        $color = !empty($appointment['user_color']) ? $appointment['user_color'] : '#0d6efd';
        
        $events[] = [
            'id' => $appointment['id'],
            'title' => $appointment['title'],
            'start' => $appointment['start_time'],
            'end' => $appointment['end_time'],
            'description' => $appointment['description'],
            'backgroundColor' => $color,
            'borderColor' => $color,
            'calendarType' => $appointment['calendar_type'],
            'user_id' => $appointment['user_id'],
            'user' => $appointment['user'] ?? 'Sin asignar',
            'user_color' => $color
        ];
    }
    
    // Añadir los usuarios al resultado para que estén disponibles en el modal
    return [
        'events' => $events,
        'users' => array_values($users)
    ];
}

// Función para obtener la configuración del calendario
function getCalendarSettings() {
    global $conn;
    
    $settings = [];
    $sql = "SELECT setting_key, setting_value FROM settings";
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    // Valores por defecto si no existen en la base de datos
    $settings['slotMinTime'] = $settings['slotMinTime'] ?? '08:00:00';
    $settings['slotMaxTime'] = $settings['slotMaxTime'] ?? '20:00:00';
    $settings['slotDuration'] = $settings['slotDuration'] ?? '00:30:00';
    $settings['timeFormat'] = $settings['timeFormat'] ?? '12h';
    
    return $settings;
}

// Función para obtener el título de la página según el tipo de calendario
function getCalendarPageTitle($calendarType) {
    return getCalendarName($calendarType) . ' | Mundo Animal';
} 