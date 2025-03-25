/**
 * Módulo de Citas
 * Maneja la visualización y gestión de las citas
 */

/**
 * Inicializa el componente de próximas citas
 */
export function initUpcomingAppointments(elements, config) {
    const { upcomingList } = elements;
    const { events, currentCalendarType, calendarColors, calendarNames } = config;
    
    if (!upcomingList) return;
    
    displayUpcomingAppointments(upcomingList, events, currentCalendarType, calendarColors, calendarNames);
}

/**
 * Muestra la lista de próximas citas
 */
function displayUpcomingAppointments(upcomingList, events, currentCalendarType, calendarColors, calendarNames) {
    // Ordenar eventos por fecha de inicio
    const sortedEvents = [...events].sort((a, b) => new Date(a.start) - new Date(b.start));
    
    // Filtrar eventos futuros (a partir de hoy)
    const now = new Date();
    let upcomingEvents = sortedEvents.filter(event => new Date(event.start) >= now);
    
    // Si no estamos en la vista general, filtrar solo las citas del tipo seleccionado
    if (currentCalendarType !== 'general') {
        upcomingEvents = upcomingEvents.filter(event => event.calendarType === currentCalendarType);
    }
    
    // Tomar solo los primeros 5 eventos
    upcomingEvents = upcomingEvents.slice(0, 5);
    
    if (upcomingEvents.length === 0) {
        upcomingList.innerHTML = '<p class="no-events">No hay citas próximas</p>';
        return;
    }
    
    // Crear elementos para cada evento
    upcomingList.innerHTML = '';
    upcomingEvents.forEach(event => {
        const start = new Date(event.start);
        const formattedDate = start.toLocaleDateString('es-ES', {weekday: 'short', day: 'numeric', month: 'short'});
        const formattedTime = start.toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit', hour12: true});
        
        // Obtener el tipo de calendario
        const calendarType = event.calendarType || 'general';
        const calendarName = calendarNames[calendarType] || 'General';
        const calendarClass = `calendar-${calendarType}`;
        const calendarColor = calendarColors[calendarType] || '#5D69F7';
        
        const eventEl = document.createElement('div');
        eventEl.className = `appointment-item ${calendarClass}`;
        eventEl.dataset.eventId = event.id;
        eventEl.innerHTML = `
            <div class="appointment-date">
                <div class="calendar-indicator" style="background-color: ${calendarColor};"></div>
                <span class="day">${formattedDate}</span>
                <span class="time">${formattedTime}</span>
            </div>
            <div class="appointment-details">
                <div class="appointment-title">${event.title}</div>
                <div class="appointment-calendar">${calendarName}</div>
                ${event.description ? `<div class="appointment-desc">${event.description.substring(0, 60)}${event.description.length > 60 ? '...' : ''}</div>` : ''}
            </div>
        `;
        
        upcomingList.appendChild(eventEl);
    });
    
    // Guardar los eventos en un atributo de datos para acceder a ellos más tarde
    upcomingList.dataset.events = JSON.stringify(upcomingEvents);
    
    // Agregar eventos de clic
    addClickEventsToAppointments(upcomingList);
}

/**
 * Agrega eventos de clic a los elementos de la lista de citas
 */
function addClickEventsToAppointments(upcomingList) {
    const appointmentItems = upcomingList.querySelectorAll('.appointment-item');
    
    appointmentItems.forEach(item => {
        item.addEventListener('click', function() {
            // La lógica para manejar el clic en la cita solo se ejecutará cuando el calendario esté disponible
            const eventId = this.dataset.eventId;
            
            // Esperar a que el objeto calendar esté disponible
            setTimeout(() => {
                if (window.calendar) {
                    const calEvent = window.calendar.getEventById(eventId);
                    if (calEvent) {
                        window.calendar.gotoDate(calEvent.start);
                        setTimeout(() => {
                            calEvent.setProp('backgroundColor', '#EF4444');
                            setTimeout(() => calEvent.setProp('backgroundColor', ''), 1500);
                        }, 100);
                    }
                }
            }, 100);
        });
    });
} 