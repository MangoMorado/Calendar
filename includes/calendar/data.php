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
    
    // Obtener citas para mostrar
    $appointments = getAppointments(null, null, $calendarType);
    
    // Preparar los datos para FullCalendar (formato JSON)
    $events = [];
    foreach ($appointments as $appointment) {
        // Asignar colores diferentes según el tipo de calendario
        $color = '';
        switch ($appointment['calendar_type']) {
            case 'estetico':
                $color = '#8E44AD'; // Púrpura para estético
                break;
            case 'veterinario':
                $color = '#2E86C1'; // Azul para veterinario
                break;
            default:
                $color = '#5D69F7'; // Color predeterminado
        }
        
        $events[] = [
            'id' => $appointment['id'],
            'title' => $appointment['title'],
            'start' => $appointment['start_time'],
            'end' => $appointment['end_time'],
            'description' => $appointment['description'],
            'backgroundColor' => $color,
            'borderColor' => $color,
            'calendarType' => $appointment['calendar_type']
        ];
    }
    
    return $events;
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