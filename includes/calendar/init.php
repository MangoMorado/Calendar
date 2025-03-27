<?php
/**
 * Calendar Initialization
 * Este archivo inicializa todos los componentes del calendario
 */

// Incluir archivos necesarios
require_once 'includes/calendar/data.php';
require_once 'includes/calendar/template.php';
require_once 'includes/calendar/modal.php';
require_once 'includes/calendar/scripts.php';

/**
 * Función para inicializar el calendario completo
 * 
 * @param string $calendarType Tipo de calendario a mostrar
 * @return array Datos necesarios para el calendario
 */
function initializeCalendar($calendarType = 'general') {
    // Validar el tipo de calendario
    $availableCalendars = getCalendarTypes();
    if (!array_key_exists($calendarType, $availableCalendars)) {
        $calendarType = 'general';
    }
    
    // Obtener datos del calendario
    $calendarData = getCalendarData($calendarType);
    $events = $calendarData['events'];
    $users = $calendarData['users'];
    $eventsJson = json_encode($events);
    
    // Obtener configuración
    $settings = getCalendarSettings();
    
    // Obtener título de la página
    $pageTitle = getCalendarPageTitle($calendarType);
    
    // Construir y devolver la estructura de datos
    return [
        'events' => $events,
        'eventsJson' => $eventsJson,
        'settings' => $settings,
        'pageTitle' => $pageTitle,
        'calendarType' => $calendarType,
        'users' => $users
    ];
}

/**
 * Función para renderizar el calendario completo
 * 
 * @param array $calendarData Datos del calendario
 * @return array HTML components and scripts
 */
function renderCalendar($calendarData) {
    // Renderizar componentes
    $calendarHtml = renderCalendarTemplate($calendarData['calendarType']);
    $modalHtml = renderAppointmentModal();
    $calendarScripts = getCalendarScripts(
        $calendarData['eventsJson'], 
        $calendarData['settings'], 
        $calendarData['calendarType']
    );
    
    // Construir y devolver la estructura HTML
    return [
        'calendarHtml' => $calendarHtml,
        'modalHtml' => $modalHtml,
        'calendarScripts' => $calendarScripts
    ];
} 