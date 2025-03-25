/**
 * Manejo de próximas citas
 * Funciones para mostrar y gestionar las próximas citas
 */

// Mostrar próximas citas
function displayUpcomingAppointments() {
    // Obtener todas las citas del calendario
    const events = calendar.getEvents();
    
    // Ordenar eventos por fecha de inicio
    const sortedEvents = events.sort((a, b) => new Date(a.start) - new Date(b.start));
    
    // Filtrar eventos futuros (a partir de hoy)
    const now = new Date();
    let upcomingEvents = sortedEvents.filter(event => new Date(event.start) >= now);
    
    // Si no estamos en la vista general, filtrar solo las citas del tipo seleccionado
    if (state.currentCalendarType !== "general") {
        upcomingEvents = upcomingEvents.filter(event => event.extendedProps.calendarType === state.currentCalendarType);
    }
    
    // Tomar solo los primeros 5 eventos
    upcomingEvents = upcomingEvents.slice(0, 5);
    
    const upcomingList = elements.upcomingList;
    
    if (upcomingEvents.length === 0) {
        upcomingList.innerHTML = "<p class=\"no-events\">No hay citas próximas</p>";
        return;
    }
    
    // Crear elementos para cada evento
    upcomingList.innerHTML = "";
    
    upcomingEvents.forEach(event => {
        const startDate = new Date(event.start);
        
        const eventElement = document.createElement("div");
        eventElement.className = "appointment-item";
        eventElement.dataset.eventId = event.id;
        
        // Obtener el tipo de calendario
        const calType = event.extendedProps.calendarType || "general";
        eventElement.dataset.calendarType = calType;
        
        // Obtener el color del calendario
        const calColor = calendarColors[calType] || calendarColors.general;
        
        eventElement.innerHTML = `
            <div class="appointment-color" style="background-color: ${calColor}"></div>
            <div class="appointment-details">
                <div class="appointment-title">${event.title}</div>
                <div class="appointment-meta">
                    <span class="appointment-time">
                        <i class="bi bi-calendar-date"></i> ${formatDate(startDate)}
                    </span>
                    <span class="appointment-time">
                        <i class="bi bi-clock"></i> ${formatTime(startDate)}
                    </span>
                    <span class="appointment-calendar">
                        <i class="bi bi-calendar3"></i> ${calendarNames[calType]}
                    </span>
                </div>
            </div>
            <div class="appointment-actions">
                <button class="btn btn-sm btn-outline-primary view-event" title="Ver detalles">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        `;
        
        upcomingList.appendChild(eventElement);
    });
    
    // Añadir eventos de clic
    addClickEventsToAppointments();
}

// Añadir eventos de clic a los elementos de la lista de citas
function addClickEventsToAppointments() {
    const appointmentItems = elements.upcomingList.querySelectorAll(".appointment-item");
    
    appointmentItems.forEach(item => {
        item.addEventListener("click", function() {
            const eventId = this.dataset.eventId;
            
            // Buscar el evento en el calendario
            const calEvent = calendar.getEventById(eventId);
            if (calEvent) {
                // Ir a la fecha del evento
                calendar.gotoDate(calEvent.start);
                
                // Resaltar el evento
                setTimeout(() => {
                    calEvent.setProp("backgroundColor", "#EF4444");
                    setTimeout(() => {
                        // Restaurar el color original después de un momento
                        calEvent.setProp("backgroundColor", calEvent.extendedProps.backgroundColor);
                    }, 1500);
                }, 100);
                
                // Mostrar los detalles del evento
                handleEventClick(calEvent);
            }
        });
    });
} 