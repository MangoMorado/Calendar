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
 * Función principal para inicializar y renderizar el calendario completo
 * Esta es la función que se llama desde index.php
 * 
 * @param string $calendarType Tipo de calendario a mostrar
 * @return string HTML renderizado del calendario
 */
function initCalendar($calendarType = 'general') {
    // Inicializar datos del calendario
    $calendarData = initializeCalendar($calendarType);
    
    // Renderizar el calendario con esos datos
    $renderedCalendar = renderCalendar($calendarData);
    
    // Almacenar el HTML del modal y los scripts como variables globales para usarlos en index.php
    global $modalHtml, $calendarScripts;
    $modalHtml = $renderedCalendar['modalHtml'];
    $calendarScripts = $renderedCalendar['calendarScripts'];
    
    // Devolver el HTML del calendario
    return $renderedCalendar['calendarHtml'];
}

/**
 * Función para inicializar el calendario completo
 * 
 * @param string $calendarType Tipo de calendario a mostrar
 * @return array Datos necesarios para el calendario
 */
function initializeCalendar($calendarType = 'general') {
    // Validar el tipo de calendario
    $availableCalendars = getCalendarTypes();
    
    // Si es un calendario de usuario, no validamos contra los tipos disponibles estándar
    $isUserCalendar = strpos($calendarType, 'user_') === 0;
    
    if (!$isUserCalendar && !array_key_exists($calendarType, $availableCalendars)) {
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
        $calendarData['calendarType'],
        $calendarData['users']
    );
    
    // Construir y devolver la estructura HTML
    return [
        'calendarHtml' => $calendarHtml,
        'modalHtml' => $modalHtml,
        'calendarScripts' => $calendarScripts
    ];
} 