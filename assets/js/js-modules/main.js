/**
 * Archivo principal de JavaScript para el calendario
 * Carga e inicializa todos los módulos
 */

// Asegurarse de que jQuery esté cargado antes de ejecutar el código
$(document).ready(function() {
    // Inicializar eventos del modal
    initModalEvents();
    
    // Inicializar el calendario
    const calendar = initCalendar(eventsJson, calendarSettings);
}); 