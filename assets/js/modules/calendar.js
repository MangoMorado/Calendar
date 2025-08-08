/**
 * Módulo de Calendario
 * Maneja la configuración y eventos del calendario
 */
import { openModal, showNotification } from './ui.js';

/**
 * Inicializa el componente del calendario
 */
export function initCalendar(elements, config, state) {
    // Verificar disponibilidad de FullCalendar
    console.log("FullCalendar global:", window.FullCalendar);
    console.log("Verificación directa de FullCalendar:", typeof FullCalendar !== 'undefined' ? "Disponible" : "No disponible");
    
    if (!window.FullCalendar) {
        console.error('Error: FullCalendar no está disponible');
        return null;
    }
    
    // Obtener elemento del calendario
    const calendarEl = elements.calendar;
    if (!calendarEl) {
        console.error('Error: Elemento #calendar no encontrado');
        return null;
    }
    
    // Obtener configuración y tipo de calendario actual
    const { settings, currentCalendarType, calendarColors, events } = config;
    console.log('Configuración aplicada:', settings);
    
    // Detectar si es un dispositivo móvil
    const isMobile = window.innerWidth < 768;
    
    try {
        console.log('Intentando crear instancia de calendario con FullCalendar', FullCalendar.version);
        
        // Crear instancia de calendario
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: isMobile ? 'timeGridDay' : 'timeGridWeek',
            themeSystem: 'bootstrap5',
            timeZone: 'local',
            locale: 'es',
            height: 'auto', // Altura automática en lugar de scroll
            
            // Configuración de tiempo
            slotMinTime: settings.slotMinTime,
            slotMaxTime: settings.slotMaxTime,
            slotDuration: settings.slotDuration,
            snapDuration: settings.slotDuration,
            
            // Configuración general optimizada para móviles
            headerToolbar: {
                left: isMobile ? 'prev,next' : 'prev,next today',
                center: 'title',
                right: isMobile ? 'timeGridDay,dayGridMonth' : 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            
            // Eliminar scroll vertical y horizontal
            scrollTimeReset: false, // No hacer scroll automático a la hora actual
            
            // Optimización móvil
            expandRows: true, // Expandir filas para llenar el espacio
            stickyHeaderDates: true, // Mantener el encabezado visible al hacer scroll
            
            // Desactivar elementos que consumen espacio
            allDaySlot: !isMobile, // Ocultar slot de "todo el día" en móviles
            weekNumbers: false, // Ocultar números de semana
            
            // Fechas y formato
            firstDay: 1, // Lunes
            nowIndicator: true,
            navLinks: !isMobile, // Desactivar enlaces de navegación en móviles
            dayMaxEvents: isMobile ? 2 : true, // Limitar número de eventos visibles en móviles
            businessHours: {
                daysOfWeek: settings.businessDays || [1, 2, 3, 4, 5], // Usar días hábiles configurados
                startTime: settings.businessHours?.startTime || '08:00',
                endTime: settings.businessHours?.endTime || '18:00'
            },
            
            // Ocultar días no laborables
            hiddenDays: Array.from({length: 7}, (_, i) => i)
                .filter(day => !settings.businessDays || !settings.businessDays.includes(day)),
            
            // Formato de visualización de la hora
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                meridiem: settings.timeFormat === '12h',
                hour12: settings.timeFormat === '12h'
            },
            slotLabelFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: settings.timeFormat === '12h'
            },
            
            // Permitir interacción con eventos
            selectable: true,
            editable: true,
            
            // Eventos iniciales
            events: events,
            
            // Optimización del renderizado
            eventDisplay: isMobile ? 'block' : 'auto', // Estilo de visualización de eventos
            
            // Handlers de eventos
            select: function(info) {
                import('./events.js')
                .then(module => module.handleTimeSlotSelection(info, elements, config, state))
                .catch(error => {
                    console.error('Error al importar módulo de eventos:', error);
                });
            },
            
            eventClick: function(info) {
                import('./events.js')
                .then(module => module.handleEventClick(info, elements, config, state))
                .catch(error => {
                    console.error('Error al importar módulo de eventos:', error);
                });
            },
            
            eventDrop: function(info) {
                import('./events.js')
                .then(module => module.handleEventDrop(info, currentCalendarType))
                .catch(error => {
                    console.error('Error al importar módulo de eventos:', error);
                });
            },
            
            eventResize: function(info) {
                import('./events.js')
                .then(module => module.handleEventResize(info, currentCalendarType))
                .catch(error => {
                    console.error('Error al importar módulo de eventos:', error);
                });
            },
            
            eventDidMount: function(info) {
                // Personalizar el color del evento según el tipo de calendario
                if (info.event.extendedProps.calendarType) {
                    const calendarType = info.event.extendedProps.calendarType;
                    if (calendarColors[calendarType]) {
                        info.el.style.backgroundColor = calendarColors[calendarType];
                        info.el.style.borderColor = calendarColors[calendarType];
                    }
                }
                
                // Aplicar color personalizado si el usuario tiene uno
                if (info.event.extendedProps.user_color) {
                    info.el.style.backgroundColor = info.event.extendedProps.user_color;
                    info.el.style.borderColor = info.event.extendedProps.user_color;
                }
                
                // Añadir tooltip
                info.el.title = info.event.title;
                
                // En dispositivos móviles, no mostrar tooltip para ahorrar espacio
                if (!isMobile) {
                    info.el.addEventListener('mouseover', function() {
                        showEventTooltip(info.event, info.el, elements.eventTooltip);
                    });
                    
                    info.el.addEventListener('mouseout', function() {
                        hideEventTooltip(elements.eventTooltip);
                    });
                }
            }
        });
        
        // Escuchar cambios de tamaño de ventana para adaptar vista
        window.addEventListener('resize', () => {
            const newIsMobile = window.innerWidth < 768;
            if (newIsMobile !== isMobile) {
                calendar.changeView(newIsMobile ? 'timeGridDay' : 'timeGridWeek');
                // Refrescar el calendario
                calendar.refetchEvents();
            }
        });
        
        // Renderizar calendario
        calendar.render();
        
        // Añadir clase personalizada para optimizar espacio
        calendarEl.classList.add('mobile-optimized');
        
        return calendar;
    } catch (error) {
        console.error('Error al inicializar el calendario:', error);
        return null;
    }
}

/**
 * Maneja el arrastre de un evento
 */
// (Se eliminaron los fallbacks duplicados de handlers; usar siempre modules/events.js)

/**
 * Muestra un tooltip personalizado para un evento
 * @param {Object} event - El evento de FullCalendar
 * @param {HTMLElement} element - El elemento DOM del evento
 * @param {HTMLElement} tooltipEl - El elemento DOM del tooltip
 */
function showEventTooltip(event, element, tooltipEl) {
    if (!tooltipEl) return;
    
    try {
        // Obtener posición del elemento
        const rect = element.getBoundingClientRect();
        const scrollTop = window.scrollY || document.documentElement.scrollTop;
        
        // Formatear fechas
        const start = new Date(event.start);
        const end = event.end ? new Date(event.end) : new Date(start.getTime() + 3600000);
        
        const formatOptions = { 
            hour: '2-digit', 
            minute: '2-digit', 
            hour12: true 
        };
        
        const dateFormatOptions = {
            weekday: 'long',
            day: 'numeric',
            month: 'long'
        };
        
        const formattedStart = start.toLocaleTimeString('es-ES', formatOptions);
        const formattedEnd = end.toLocaleTimeString('es-ES', formatOptions);
        const formattedDate = start.toLocaleDateString('es-ES', dateFormatOptions);
        
        // Obtener tipo de calendario
        const calendarType = event.extendedProps.calendarType || 'general';
        let calendarName;
        
        switch (calendarType) {
            case 'estetico':
                calendarName = 'Estético';
                break;
            case 'veterinario':
                calendarName = 'Veterinario';
                break;
            default:
                calendarName = 'General';
        }
        
        // Construir contenido HTML del tooltip
        tooltipEl.innerHTML = `
            <div class="tooltip-title">${event.title}</div>
            <div class="tooltip-time"><i class="bi bi-clock"></i> ${formattedStart} - ${formattedEnd}</div>
            <div class="tooltip-date"><i class="bi bi-calendar-event"></i> ${formattedDate}</div>
            <div class="tooltip-calendar"><i class="bi bi-calendar3"></i> ${calendarName}</div>
            ${event.extendedProps.description ? `<div class="tooltip-desc">${event.extendedProps.description}</div>` : ''}
        `;
        
        // Posicionar tooltip
        tooltipEl.style.left = rect.left + window.scrollX + 'px';
        tooltipEl.style.top = rect.bottom + scrollTop + 'px';
        tooltipEl.style.display = 'block';
    } catch (error) {
        console.error('Error al mostrar tooltip:', error);
    }
}

/**
 * Oculta el tooltip de evento
 * @param {HTMLElement} tooltipEl - El elemento DOM del tooltip
 */
function hideEventTooltip(tooltipEl) {
    if (tooltipEl) {
        tooltipEl.style.display = 'none';
    }
} 