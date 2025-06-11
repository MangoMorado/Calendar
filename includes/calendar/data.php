<?php
/**
 * Calendar Data Processing
 * Este archivo contiene funciones relacionadas con el procesamiento de datos del calendario
 */

// Establecer timezone global desde settings
$timezone = 'America/Bogota';
require_once __DIR__ . '/../../config/database.php';
$sql = "SELECT setting_value FROM settings WHERE setting_key = 'timezone' LIMIT 1";
$result = mysqli_query($conn, $sql);
if ($result && $row = mysqli_fetch_assoc($result)) {
    $timezone = $row['setting_value'];
}
date_default_timezone_set($timezone);

// Función para obtener las citas según el tipo de calendario
function getCalendarData($calendarType = 'general') {
    global $conn;
    
    // Verificar si es un calendario de usuario específico
    $isUserCalendar = false;
    $userId = null;
    
    if (strpos($calendarType, 'user_') === 0) {
        $isUserCalendar = true;
        $userId = (int) substr($calendarType, 5);
        error_log("Obteniendo datos para el calendario del usuario ID: $userId");
    } else {
        // Validar el tipo de calendario estándar
        $availableCalendars = getCalendarTypes();
        if (!array_key_exists($calendarType, $availableCalendars)) {
            $calendarType = 'general';
        }
    }
    
    // Obtener todos los usuarios para asignarlos a las citas
    $usersQuery = "SELECT id, name, COALESCE(color, '#3788d8') as color FROM users WHERE calendar_visible = 1";
    $usersResult = mysqli_query($conn, $usersQuery);
    $users = [];
    
    while ($user = mysqli_fetch_assoc($usersResult)) {
        $users[$user['id']] = $user;
    }
    
    // Registrar cuántos usuarios se están recuperando
    error_log("Se obtuvieron " . count($users) . " usuarios con calendar_visible=1");
    
    // Si no se obtuvieron usuarios, podría ser un problema con la base de datos
    if (count($users) === 0) {
        error_log("ADVERTENCIA: No se encontraron usuarios con calendar_visible=1");
        // Consultar todos los usuarios para depuración
        $debugQuery = "SELECT id, name, calendar_visible FROM users";
        $debugResult = mysqli_query($conn, $debugQuery);
        if ($debugResult) {
            error_log("Total de usuarios en la base de datos: " . mysqli_num_rows($debugResult));
            while ($debugUser = mysqli_fetch_assoc($debugResult)) {
                error_log("Usuario: ID=" . $debugUser['id'] . ", Nombre=" . $debugUser['name'] . 
                        ", calendar_visible=" . $debugUser['calendar_visible']);
            }
        }
    }
    
    // Construir la consulta SQL para obtener las citas con la información del usuario
    $sql = "SELECT a.*, u.name as user, COALESCE(u.color, '#3788d8') as user_color 
            FROM appointments a 
            LEFT JOIN users u ON a.user_id = u.id";
    
    // Añadir condiciones según el tipo de calendario
    if ($isUserCalendar) {
        // Filtrar por usuario específico
        $sql .= " WHERE a.user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $userId);
    } elseif ($calendarType !== 'general') {
        // Filtrar por tipo de calendario si no es el general
        $sql .= " WHERE a.calendar_type = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $calendarType);
    } else {
        // Para el calendario general, obtenemos todas las citas
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
    
    // Procesar los días hábiles 
    if (isset($settings['businessDays'])) {
        $settings['businessDays'] = json_decode($settings['businessDays'], true);
    } else {
        // Por defecto, lunes a viernes (1-5)
        $settings['businessDays'] = [1, 2, 3, 4, 5];
    }
    
    return $settings;
}

// Función para obtener el título de la página según el tipo de calendario
function getCalendarPageTitle($calendarType) {
    return getCalendarName($calendarType) . ' | Mundo Animal';
} 