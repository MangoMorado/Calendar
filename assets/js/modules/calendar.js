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
                daysOfWeek: [1, 2, 3, 4, 5], // Lunes a Viernes
                startTime: settings.businessHours?.startTime || '08:00',
                endTime: settings.businessHours?.endTime || '18:00'
            },
            
            // Formato de visualización de la hora
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                meridiem: settings.timeFormat === '12h',
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
                // Importamos aquí para evitar dependencias circulares
                import('./events.js')
                .then(module => {
                    module.handleTimeSlotSelection(info, elements, config, state);
                })
                .catch(error => {
                    console.error('Error al importar módulo de eventos:', error);
                    // Fallback al handler local
                    console.warn('Usando handler local como respaldo');
                    handleTimeSlotSelection(info, elements, settings, state);
                });
            },
            
            eventClick: function(info) {
                // Importamos aquí para evitar dependencias circulares
                import('./events.js')
                .then(module => {
                    module.handleEventClick(info, elements, config, state);
                })
                .catch(error => {
                    console.error('Error al importar módulo de eventos:', error);
                    // Fallback al handler local
                    console.warn('Usando handler local como respaldo');
                    handleEventClick(info, elements, state);
                });
            },
            
            eventDrop: function(info) {
                // Importamos aquí para evitar dependencias circulares
                import('./events.js')
                .then(module => {
                    module.handleEventDrop(info, currentCalendarType);
                })
                .catch(error => {
                    console.error('Error al importar módulo de eventos:', error);
                    // Fallback al handler local
                    console.warn('Usando handler local como respaldo');
                    handleEventDrop(info, currentCalendarType);
                });
            },
            
            eventResize: function(info) {
                // Importamos aquí para evitar dependencias circulares
                import('./events.js')
                .then(module => {
                    module.handleEventResize(info, currentCalendarType);
                })
                .catch(error => {
                    console.error('Error al importar módulo de eventos:', error);
                    // Fallback al handler local
                    console.warn('Usando handler local como respaldo');
                    handleEventResize(info, currentCalendarType);
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
    
    // Si el evento tiene un usuario asignado, mantenerlo
    if (info.event.extendedProps.user_id) {
        formData.append('user_id', info.event.extendedProps.user_id);
    }
    
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
            throw new Error(`Error de servidor: ${response.status} ${response.statusText}`);
        }
        return response.text();
    })
    .then(text => {
        // Intentar parsear como JSON de manera más permisiva
        try {
            const cleanText = text.trim();
            console.log('Respuesta del servidor (arrastrar):', cleanText);
            return JSON.parse(cleanText);
        } catch (error) {
            console.error('No se pudo parsear la respuesta como JSON:', text);
            throw new Error('No se pudo parsear la respuesta del servidor como JSON');
        }
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
        showNotification(`Error: ${error.message || 'Error de conexión'}`, 'error');
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
    
    // Si el evento tiene un usuario asignado, mantenerlo
    if (info.event.extendedProps.user_id) {
        formData.append('user_id', info.event.extendedProps.user_id);
    }
    
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
            throw new Error(`Error de servidor: ${response.status} ${response.statusText}`);
        }
        return response.text();
    })
    .then(text => {
        // Intentar parsear como JSON de manera más permisiva
        try {
            const cleanText = text.trim();
            console.log('Respuesta del servidor (redimensionar):', cleanText);
            return JSON.parse(cleanText);
        } catch (error) {
            console.error('No se pudo parsear la respuesta como JSON:', text);
            throw new Error('No se pudo parsear la respuesta del servidor como JSON');
        }
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
        showNotification(`Error: ${error.message || 'Error de conexión'}`, 'error');
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
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error de servidor: ${response.status} ${response.statusText}`);
            }
            return response.text();
        })
        .then(text => {
            // Intentar parsear como JSON de manera más permisiva
            try {
                const cleanText = text.trim();
                console.log('Respuesta del servidor (obtener detalles):', cleanText);
                return JSON.parse(cleanText);
            } catch (error) {
                console.error('No se pudo parsear la respuesta como JSON:', text);
                throw new Error('No se pudo parsear la respuesta del servidor como JSON');
            }
        })
        .then(data => {
            // Llenar el formulario con los datos recibidos
            const titleField = document.getElementById('title');
            const descriptionField = document.getElementById('description');
            const startTimeField = document.getElementById('startTime') || document.getElementById('start_time');
            const endTimeField = document.getElementById('endTime') || document.getElementById('end_time');
            const calendarTypeSelect = document.getElementById('calendarType') || document.getElementById('calendar_type');
            const userSelect = document.getElementById('user_id');
            
            if (titleField) titleField.value = data.title || '';
            if (descriptionField) descriptionField.value = data.description || '';
            
            // Formatear fechas para el input datetime-local
            if (startTimeField) {
                const startStr = data.start_time ? data.start_time.replace(' ', 'T') : '';
                startTimeField.value = startStr;
            }
            
            if (endTimeField) {
                const endStr = data.end_time ? data.end_time.replace(' ', 'T') : '';
                endTimeField.value = endStr;
            }
            
            // Seleccionar el tipo de calendario correcto
            if (calendarTypeSelect) {
                const calendarType = data.calendar_type || 'general';
                calendarTypeSelect.value = calendarType;
            }
            
            // Seleccionar el usuario si existe
            if (userSelect && data.user_id) {
                userSelect.value = data.user_id;
                // Disparar evento change para actualizar la vista previa del color
                const changeEvent = new Event('change');
                userSelect.dispatchEvent(changeEvent);
            }
            
            // Configurar el modal para editar cita
            const modalTitle = document.getElementById('modalTitle');
            if (modalTitle) {
                modalTitle.innerHTML = '<i class="bi bi-calendar-check"></i> Editar Cita';
            }
            
            const deleteButton = document.getElementById('deleteAppointment');
            if (deleteButton) {
                deleteButton.style.display = 'inline-flex';
                deleteButton.dataset.id = info.event.id;
            }
            
            // Actualizar estado
            state.currentAppointmentId = info.event.id;
            state.isEditMode = true;
            window.currentAppointmentId = info.event.id;
            window.isEditMode = true;
            
            // Abrir el modal
            openModal(elements.appointmentModal);
        })
        .catch(error => {
            console.error('Error al cargar los detalles de la cita:', error);
            showNotification(`Error: ${error.message || 'Error al cargar los detalles'}`, 'error');
        });
}

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