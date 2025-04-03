/**
 * Módulo de Citas
 * Maneja la visualización y gestión de las citas
 */
import { formatDate, formatTime } from './utils.js';
import { setupEditModal } from './modal.js';

/**
 * Inicializa el componente de próximas citas
 * @param {Object} elements - Referencias a elementos DOM
 * @param {Object} config - Configuración de la aplicación
 */
export function initUpcomingAppointments(elements, config) {
    const { upcomingList } = elements;
    
    if (!upcomingList) {
        console.log('Elemento de próximas citas no encontrado');
        return;
    }
    
    // Intentar obtener el calendario del ámbito global si no se ha pasado
    const calendar = window.calendar;
    if (!calendar) {
        console.log('Calendario no disponible para próximas citas');
        return;
    }
    
    // Obtener eventos del calendario
    const events = calendar.getEvents();
    
    // Mostrar las próximas citas
    displayUpcomingAppointments(upcomingList, events, config);
}

/**
 * Muestra la lista de próximas citas
 * @param {HTMLElement} upcomingList - Elemento contenedor de la lista
 * @param {Array} events - Eventos del calendario
 * @param {Object} config - Configuración de la aplicación
 */
export function displayUpcomingAppointments(upcomingList, events, config) {
    const { calendarColors, calendarNames, currentCalendarType } = config;
    
    if (!upcomingList) return;
    
    // Ordenar eventos por fecha de inicio
    const sortedEvents = [...events].sort((a, b) => new Date(a.start) - new Date(b.start));
    
    // Filtrar eventos futuros (a partir de ahora)
    const now = new Date();
    let upcomingEvents = sortedEvents.filter(event => new Date(event.start) >= now);
    
    // Si no estamos en la vista general, filtrar solo las citas del tipo seleccionado
    if (currentCalendarType !== 'general') {
        upcomingEvents = upcomingEvents.filter(event => {
            const eventType = event.extendedProps.calendarType || 'general';
            return eventType === currentCalendarType;
        });
    }
    
    // Tomar solo los primeros 5 eventos
    upcomingEvents = upcomingEvents.slice(0, 5);
    
    // Crear elementos para cada evento
    if (upcomingEvents.length === 0) {
        upcomingList.innerHTML = '<p class="no-events">No hay citas próximas</p>';
        return;
    }
    
    upcomingList.innerHTML = '';
    
    upcomingEvents.forEach(event => {
        // Convertir a objeto Date
        const startDate = new Date(event.start);
        
        // Crear elemento para la cita
        const eventEl = document.createElement('div');
        eventEl.className = 'appointment-item';
        eventEl.dataset.eventId = event.id;
        
        // Obtener el tipo de calendario
        const calendarType = event.extendedProps.calendarType || 'general';
        eventEl.dataset.calendarType = calendarType;
        
        // Obtener color del calendario (prioridad al color del usuario si está disponible)
        const userColor = event.extendedProps.user_color;
        const calColor = userColor || calendarColors[calendarType] || calendarColors.general;
        
        // Obtener nombre de usuario
        const userName = event.extendedProps.user || event.extendedProps.user_name;
        const userDisplay = userName && userName !== 'null' && userName !== 'undefined' && userName.trim() !== '' 
            ? userName 
            : 'Sin asignar';
        
        // Crear HTML con la información
        eventEl.innerHTML = `
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
                    <div class="appointment-info">
                        <span class="appointment-calendar">
                            <i class="bi bi-calendar3"></i> ${calendarNames[calendarType] || 'General'}
                        </span>
                        <span class="appointment-user">
                            <i class="bi bi-person"></i> ${userDisplay}
                        </span>
                    </div>
                </div>
            </div>
            <div class="appointment-actions">
                <button class="btn btn-sm btn-outline-primary view-event" title="Ver detalles">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        `;
        
        upcomingList.appendChild(eventEl);
    });
    
    // Añadir eventos de clic
    addClickEventsToAppointments(upcomingList, events);
}

/**
 * Añade eventos de clic a los elementos de la lista de citas
 * @param {HTMLElement} upcomingList - Elemento contenedor de la lista
 * @param {Array} events - Eventos del calendario
 */
function addClickEventsToAppointments(upcomingList, events) {
    const appointmentItems = upcomingList.querySelectorAll('.appointment-item');
    
    appointmentItems.forEach(item => {
        item.addEventListener('click', function() {
            const eventId = this.dataset.eventId;
            
            // Intentar obtener el calendario del ámbito global
            const calendar = window.calendar;
            if (!calendar) {
                console.error('Calendario no disponible');
                return;
            }
            
            // Buscar el evento en el calendario
            const calEvent = calendar.getEventById(eventId);
            if (calEvent) {
                // Ir a la fecha del evento
                calendar.gotoDate(calEvent.start);
                
                // Resaltar el evento temporalmente
                setTimeout(() => {
                    const originalColor = calEvent.backgroundColor;
                    calEvent.setProp('backgroundColor', '#EF4444');
                    
                    // Restaurar color original después de un tiempo
                    setTimeout(() => {
                        calEvent.setProp('backgroundColor', originalColor || '');
                    }, 1500);
                }, 100);
                
                // Configurar elementos y estado para el modal
                const elements = {
                    appointmentForm: document.getElementById('appointmentForm'),
                    appointmentModal: document.getElementById('appointmentModal')
                };
                
                // Actualizar estado y variables globales para asegurar que la cita se pueda eliminar
                const state = {
                    isEditMode: true,
                    currentAppointmentId: eventId
                };
                
                // Actualizar también las variables globales
                window.state.isEditMode = true;
                window.state.currentAppointmentId = eventId;
                
                console.log('Estado actualizado para eliminación:', {
                    eventId: eventId,
                    isEditMode: true,
                    windowState: window.state
                });
                
                // Obtener configuración
                const config = {
                    users: window.calendarUsers || []
                };
                
                // Mostrar el modal de edición
                setupEditModal(calEvent, elements, config, state);
                
                // Después de abrir el modal, verificar que el ID está correctamente establecido
                setTimeout(() => {
                    const deleteButton = document.getElementById('deleteAppointment');
                    if (deleteButton) {
                        // Asegurarnos que el botón esté visible
                        deleteButton.style.display = 'inline-block';
                        // Establecer el ID de la cita en el botón de eliminar
                        deleteButton.dataset.id = eventId;
                    }
                    
                    // Verificar campos ocultos
                    const idField = document.getElementById('appointmentId');
                    if (idField) {
                        idField.value = eventId;
                    }
                }, 100);
            } else {
                console.log('Evento no encontrado en el calendario:', eventId);
            }
        });
    });
} 