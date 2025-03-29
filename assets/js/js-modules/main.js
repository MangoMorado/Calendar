/**
 * Archivo principal de JavaScript para el calendario
 * Carga e inicializa todos los módulos
 */

// Asegurarse de que jQuery esté cargado antes de ejecutar el código
document.addEventListener('DOMContentLoaded', function() {
    // Verificar que jQuery esté disponible
    if (typeof jQuery === 'undefined') {
        console.error('jQuery no está cargado. Por favor, asegúrate de que jQuery esté incluido antes de este script.');
        return;
    }

    // Inicializar eventos del modal
    initModalEvents();
    
    // Inicializar el calendario
    const calendar = initCalendar(eventsJson, calendarSettings);
}); 