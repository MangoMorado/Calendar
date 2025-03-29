/**
 * Inicialización del calendario
 * Configuración e instanciación del calendario
 */

// Configuración de colores para los calendarios
if (typeof calendarColors === 'undefined') {
    const calendarColors = {
        estetico: "#8E44AD",
        veterinario: "#2E86C1",
        general: "#5D69F7"
    };
}

// Nombres de los calendarios
if (typeof calendarNames === 'undefined') {
    const calendarNames = {
        estetico: "Estético",
        veterinario: "Veterinario",
        general: "General"
    };
}

// Referencias a elementos DOM
if (typeof elements === 'undefined') {
    let elements = null;
}

// Estado actual de la aplicación
if (typeof state === 'undefined') {
    const state = {
        isEditMode: false,
        currentAppointmentId: null,
        currentCalendarType: null
    };
}

// Inicializar elementos DOM
function initializeElements() {
    elements = {
        calendarContainer: document.getElementById("calendar"),
        appointmentModal: document.getElementById("appointmentModal"),
        appointmentForm: document.getElementById("appointmentForm"),
        deleteButton: document.getElementById("deleteAppointment"),
        upcomingList: document.getElementById("upcomingAppointmentsList")
    };
}

// Inicializar el calendario
function initCalendar(eventsJson, settings) {
    // Inicializar elementos DOM si no están inicializados
    if (!elements) {
        initializeElements();
    }

    // Establecer el tipo de calendario actual
    state.currentCalendarType = settings.calendarType || 'general';

    const calendar = new FullCalendar.Calendar(elements.calendarContainer, {
        // Definir vista inicial basada en el ancho de la pantalla
        initialView: window.innerWidth < 768 ? "listWeek" : "timeGridWeek",
        locale: "es",
        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,timeGridWeek,timeGridDay,listWeek"
        },
        buttonText: {
            today: "Hoy",
            month: "Mes",
            week: "Semana",
            day: "Día",
            list: "Lista"
        },
        events: eventsJson,
        eventTimeFormat: {
            hour: "2-digit",
            minute: "2-digit",
            hour12: true
        },
        // Configuración de todo el día
        allDaySlot: true,
        allDayText: 'Todo el día',
        allDayMaintainDuration: true,
        nextDayThreshold: '00:00:00', // Considerar eventos como de todo el día si van hasta medianoche
        slotMinTime: settings.slotMinTime,
        slotMaxTime: settings.slotMaxTime,
        slotDuration: settings.slotDuration,
        height: "auto",
        selectable: true,
        selectMirror: true,
        editable: true,
        droppable: true,
        eventStartEditable: true,
        eventDurationEditable: true,
        eventResizableFromStart: false,
        // Función para manejar la visualización de eventos
        eventDidMount: function(info) {
            // Si es un evento de todo el día, asegurar que se muestre en la sección correcta
            if (info.event.allDay) {
                info.el.classList.add('fc-event-all-day');
            }
        },
        select: function(info) {
            // Manejar selección de fecha/hora para crear una cita
            handleDateSelection(info);
        },
        eventClick: function(info) {
            handleEventClick(info.event);
        },
        eventDrop: function(info) {
            // Manejar cuando un evento es arrastrado y soltado
            handleEventDrop(info);
        },
        eventResize: function(info) {
            // Manejar cuando un evento es redimensionado
            handleEventResize(info);
        },
        // Cambiar vista al cambiar el tamaño de ventana
        windowResize: function(view) {
            const newView = window.innerWidth < 768 ? "listWeek" : "timeGridWeek";
            if (calendar.view.type !== newView) {
                calendar.changeView(newView);
            }
        }
    });
    
    // Exponer el calendario globalmente para su uso en otras funciones
    window.calendar = calendar;
    
    // Renderizar el calendario
    calendar.render();
    
    // Mostrar las próximas citas
    displayUpcomingAppointments();
    
    return calendar;
} 