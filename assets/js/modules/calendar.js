/**
 * Módulo de Calendario
 * Maneja la configuración y eventos del calendario
 */
import { openModal, showNotification } from './ui.js';

/**
 * Inicializa el componente del calendario
 */
export function initCalendar(elements, config, state) {
    const { calendar: calendarEl, eventTooltip } = elements;
    const { events, currentCalendarType, settings, calendarNames } = config;
    
    // Asegurar que el elemento del calendario exista
    if (!calendarEl) {
        console.error('Error: Elemento del calendario no encontrado');
        return null;
    }
    
    // Debuguear disponibilidad de FullCalendar
    console.log('FullCalendar global:', window.FullCalendar);
    console.log('Verificación directa de FullCalendar:', typeof FullCalendar !== 'undefined' ? 'Disponible' : 'No disponible');
    
    // Asegurar que FullCalendar esté disponible
    if (typeof window.FullCalendar === 'undefined' && typeof FullCalendar === 'undefined') {
        console.error('Error: FullCalendar no está cargado en ningún ámbito');
        return null;
    }
    
    // Determinar qué versión de FullCalendar usar
    const FC = window.FullCalendar || FullCalendar;
    
    console.log('Configuración aplicada:', settings);
    
    try {
        // Crear instancia de calendario
        console.log('Intentando crear instancia de calendario con FullCalendar 6.1.15');
        
        const calendar = new FC.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            locale: 'es',
            buttonText: {
                today: 'Hoy',
                month: 'Mes',
                week: 'Semana',
                day: 'Día',
                list: 'Lista'
            },
            slotMinTime: settings.slotMinTime,
            slotMaxTime: settings.slotMaxTime,
            height: 'auto',
            allDaySlot: false,
            slotDuration: settings.slotDuration,
            nowIndicator: true,
            navLinks: true,
            selectable: true,
            selectMirror: true,
            dayMaxEvents: true,
            // Habilitar eventos arrastrables y configuración explícita
            editable: true,
            droppable: true,
            eventStartEditable: true,
            // Habilitar cambio de duración de eventos
            eventResizableFromStart: false,
            eventDurationEditable: true,
            businessHours: {
                daysOfWeek: [1, 2, 3, 4, 5, 6, 0], // Todos los días
                startTime: settings.slotMinTime,
                endTime: settings.slotMaxTime,
            },
            events: events,
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: settings.timeFormat === '12h'
            },
            // Personalizar la renderización de eventos
            eventDidMount: function(info) {
                // Aplicar clase específica al evento según el tipo de calendario
                const calendarType = info.event.extendedProps.calendarType || 'general';
                info.el.classList.add(`calendar-${calendarType}`);
                
                // Asegurar que los eventos sean arrastrables agregando data-attributes
                info.el.setAttribute('draggable', 'true');
                info.el.dataset.eventId = info.event.id;
            },
            // Tooltip personalizado para eventos
            eventMouseEnter: function(info) {
                const rect = info.el.getBoundingClientRect();
                const scrollTop = window.scrollY || document.documentElement.scrollTop;
                
                // Formatear fechas
                const start = new Date(info.event.start);
                const end = new Date(info.event.end);
                const formattedStart = start.toLocaleTimeString('es-ES', {hour: '2-digit', minute:'2-digit', hour12: true});
                const formattedEnd = end.toLocaleTimeString('es-ES', {hour: '2-digit', minute:'2-digit', hour12: true});
                const formattedDate = start.toLocaleDateString('es-ES', {weekday: 'long', day: 'numeric', month: 'long'});
                
                // Obtener el tipo de calendario para mostrar en el tooltip
                const calendarType = info.event.extendedProps.calendarType || 'general';
                const calendarName = calendarNames[calendarType] || 'General';
                
                // Contenido del tooltip
                eventTooltip.innerHTML = `
                    <div class="tooltip-title">${info.event.title}</div>
                    <div class="tooltip-time"><i class="bi bi-clock"></i> ${formattedStart} - ${formattedEnd}</div>
                    <div class="tooltip-date"><i class="bi bi-calendar-event"></i> ${formattedDate}</div>
                    <div class="tooltip-calendar"><i class="bi bi-calendar3"></i> ${calendarName}</div>
                    ${info.event.extendedProps.description ? `<div class="tooltip-desc">${info.event.extendedProps.description}</div>` : ''}
                `;
                
                // Posicionar y mostrar tooltip
                eventTooltip.style.left = rect.left + window.scrollX + 'px';
                eventTooltip.style.top = rect.bottom + scrollTop + 'px';
                eventTooltip.style.display = 'block';
            },
            eventMouseLeave: function() {
                eventTooltip.style.display = 'none';
            },
            // Evento al seleccionar un rango de tiempo
            select: function(info) {
                handleTimeSlotSelection(info, elements, settings, state);
            },
            // Evento al hacer clic en una cita existente
            eventClick: function(info) {
                handleEventClick(info, elements, state);
            },
            // Eventos para drag and drop
            eventDrop: function(info) {
                handleEventDrop(info, currentCalendarType);
            },
            // Evento para redimensionar un evento
            eventResize: function(info) {
                handleEventResize(info, currentCalendarType);
            }
        });
        
        calendar.render();
        return calendar;
    } catch (error) {
        console.error('Error al crear la instancia de calendario:', error);
        return null;
    }
}

/**
 * Maneja el cambio de evento por drag and drop
 */
function handleEventDrop(info, currentCalendarType) {
    const eventId = info.event.id;
    const newStart = info.event.start;
    const newEnd = info.event.end || new Date(newStart.getTime() + 30*60000); // Si no hay end, añadir 30 min
    const formattedStart = formatDateTimeForServer(newStart);
    const formattedEnd = formatDateTimeForServer(newEnd);
    
    console.log('Evento arrastrado:', {
        id: eventId,
        title: info.event.title,
        start: formattedStart,
        end: formattedEnd,
        calendarType: info.event.extendedProps.calendarType || 'general'
    });
    
    // Crear los datos para la petición
    const formData = new FormData();
    formData.append('id', eventId);
    formData.append('action', 'update');
    formData.append('start_time', formattedStart);
    formData.append('end_time', formattedEnd);
    formData.append('title', info.event.title);
    formData.append('description', info.event.extendedProps.description || '');
    
    // Si no estamos en una vista específica, conservar el tipo de calendario
    if (currentCalendarType === 'general') {
        formData.append('calendar_type', info.event.extendedProps.calendarType || 'general');
    } else {
        // Si estamos en una vista específica (estético o veterinario), mantener ese tipo
        formData.append('calendar_type', currentCalendarType);
    }
    
    // Mostrar indicador de carga
    showNotification('Actualizando cita...', 'info');
    
    // Enviar la petición al servidor
    fetch('process_appointment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showNotification(data.message || 'Cita actualizada correctamente', 'success');
        } else {
            showNotification(data.message || 'Error al actualizar la cita', 'error');
            info.revert(); // Revertir el cambio
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error de conexión: ' + error.message, 'error');
        info.revert(); // Revertir el cambio
    });
}

/**
 * Maneja el redimensionamiento de un evento
 */
function handleEventResize(info, currentCalendarType) {
    const eventId = info.event.id;
    const newStart = info.event.start;
    const newEnd = info.event.end || new Date(newStart.getTime() + 30*60000); // Si no hay end, añadir 30 min
    const formattedStart = formatDateTimeForServer(newStart);
    const formattedEnd = formatDateTimeForServer(newEnd);
    
    console.log('Evento redimensionado:', {
        id: eventId,
        title: info.event.title,
        start: formattedStart,
        end: formattedEnd,
        calendarType: info.event.extendedProps.calendarType || 'general'
    });
    
    // Crear los datos para la petición
    const formData = new FormData();
    formData.append('id', eventId);
    formData.append('action', 'update');
    formData.append('start_time', formattedStart);
    formData.append('end_time', formattedEnd);
    formData.append('title', info.event.title);
    formData.append('description', info.event.extendedProps.description || '');
    
    // Si no estamos en una vista específica, conservar el tipo de calendario
    if (currentCalendarType === 'general') {
        formData.append('calendar_type', info.event.extendedProps.calendarType || 'general');
    } else {
        // Si estamos en una vista específica (estético o veterinario), mantener ese tipo
        formData.append('calendar_type', currentCalendarType);
    }
    
    // Mostrar indicador de carga
    showNotification('Actualizando horario...', 'info');
    
    // Enviar la petición al servidor
    fetch('process_appointment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showNotification(data.message || 'Horario actualizado correctamente', 'success');
        } else {
            showNotification(data.message || 'Error al actualizar el horario', 'error');
            info.revert(); // Revertir el cambio
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error de conexión: ' + error.message, 'error');
        info.revert(); // Revertir el cambio
    });
}

/**
 * Formatea una fecha para enviarla al servidor
 */
function formatDateTimeForServer(date) {
    return date.toISOString().replace('T', ' ').substring(0, 19);
}

/**
 * Maneja la selección de un slot de tiempo
 */
function handleTimeSlotSelection(info, elements, settings, state) {
    // Obtener la fecha y hora del clic
    const clickedDate = info.start;
    
    // Formatear la fecha y hora para el campo datetime-local
    // Formato YYYY-MM-DDThh:mm según el estándar HTML5
    const formattedStartDateTime = clickedDate.toISOString().substr(0, 16);
    
    // Calcular la hora de fin basada en la duración de slots
    const slotDuration = settings.slotDuration;
    const [hours, minutes, seconds] = slotDuration.split(':').map(Number);
    const endDate = new Date(clickedDate);
    endDate.setHours(endDate.getHours() + hours);
    endDate.setMinutes(endDate.getMinutes() + minutes);
    endDate.setSeconds(endDate.getSeconds() + seconds);
    
    // Formatear la fecha y hora de fin para el campo datetime-local
    const formattedEndDateTime = endDate.toISOString().substr(0, 16);

    console.log('Slot seleccionado:', {
        start: formattedStartDateTime,
        end: formattedEndDateTime,
        clickedDate: clickedDate,
        endDate: endDate,
        slotDuration: slotDuration
    });

    // Pre-llenar los campos en el modal
    const startTimeInput = document.getElementById('startTime');
    const endTimeInput = document.getElementById('endTime');
    
    if (startTimeInput && endTimeInput) {
        startTimeInput.value = formattedStartDateTime;
        endTimeInput.value = formattedEndDateTime;
    } else {
        console.error('No se encontraron los campos de fecha y hora');
    }

    // Configurar el modal para crear cita
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-calendar-plus"></i> Crear Cita';
    document.getElementById('deleteAppointment').style.display = 'none';
    
    // Actualizar estado
    state.isEditMode = false;
    state.currentAppointmentId = null;
    window.isEditMode = false;
    window.currentAppointmentId = null;
    
    // Abrir el modal
    openModal(elements.appointmentModal);
}

/**
 * Maneja el clic en un evento existente
 */
function handleEventClick(info, elements, state) {
    // Obtener los detalles de la cita con AJAX
    fetch(`get_appointment.php?id=${info.event.id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('title').value = data.title;
            document.getElementById('description').value = data.description;
            document.getElementById('startTime').value = data.start_time;
            document.getElementById('endTime').value = data.end_time;
            
            // Seleccionar el tipo de calendario correcto
            const calendarTypeSelect = document.getElementById('calendarType');
            if (calendarTypeSelect) {
                [...calendarTypeSelect.options].forEach(option => {
                    if (option.value === data.calendar_type) {
                        option.selected = true;
                    }
                });
            }
            
            // Configurar el modal para editar cita
            document.getElementById('modalTitle').innerHTML = '<i class="bi bi-calendar-check"></i> Editar Cita';
            document.getElementById('deleteAppointment').style.display = 'inline-flex';
            
            // Actualizar estado
            state.currentAppointmentId = info.event.id;
            state.isEditMode = true;
            window.currentAppointmentId = info.event.id;
            window.isEditMode = true;
            
            // Abrir el modal
            openModal(elements.appointmentModal);
        })
        .catch(error => {
            console.error('Error:', error);
            window.showNotification('Error al cargar los datos de la cita', 'error');
        });
} 