/**
 * Módulo de Eventos
 * Maneja los controladores de eventos para la interfaz
 */
import { openModal, closeModal, showNotification } from './ui.js';
import { setupCreateModal, setupEditModal, processAppointmentForm, updateColorPreview } from './modal.js';
import { formatDateTimeForServer } from './utils.js';

// Variables para el sistema de deshacer
let lastAction = null;
let lastEventState = null;

/**
 * Permite establecer el estado de deshacer desde fuera del módulo
 * @param {string} action - La acción que se realizó (create, update, delete)
 * @param {Object} eventState - El estado del evento antes del cambio
 */
export function setUndoState(action, eventState) {
    lastAction = action;
    lastEventState = eventState;
    updateUndoButton();
}

/**
 * Inicializa los event listeners
 */
export function initEventListeners(elements, config, state, calendar) {
    const { 
        calendarTypeSelector, 
        closeModalBtn,
        createAppointmentBtn, 
        deleteAppointmentBtn, 
        appointmentForm,
        appointmentModal,
        calendar: calendarEl,
        undoButton
    } = elements;
    
    // Establecer el calendario en el ámbito global para acceso desde otros módulos
    window.calendar = calendar;
    
    // Selector de tipo de calendario
    if (calendarTypeSelector) {
        calendarTypeSelector.addEventListener('change', function() {
            const selectedCalendarType = this.value;
            window.location.href = `index.php?calendar=${selectedCalendarType}`;
        });
    }
    
    // Crear nueva cita
    if (createAppointmentBtn) {
        createAppointmentBtn.addEventListener('click', function() {
            setupCreateModal(elements, config, state);
        });
    }
    
    // Selector de usuario para actualizar color
    const userSelector = document.getElementById('user_id');
    if (userSelector) {
        userSelector.addEventListener('change', updateColorPreview);
    }
    
    // Cerrar modal
    if (closeModalBtn) {
        console.log('Botón de cerrar modal encontrado:', closeModalBtn);
        closeModalBtn.addEventListener('click', function() {
            console.log('Botón de cerrar modal clickeado');
            closeModal(appointmentModal);
        });
    } else {
        console.warn('⚠️ Botón de cerrar modal no encontrado. Buscando elemento con clase .close');
        const closeBtn = document.querySelector('.close');
        if (closeBtn) {
            console.log('Botón de cerrar encontrado con selector alternativo:', closeBtn);
            closeBtn.addEventListener('click', function() {
                console.log('Botón de cerrar modal clickeado (selector alternativo)');
                closeModal(appointmentModal);
            });
        } else {
            console.error('❌ No se pudo encontrar el botón de cerrar modal');
        }
    }
    
    // Respaldo adicional: agregar evento click a cualquier elemento con clase .close
    document.addEventListener('click', function(e) {
        if (e.target.closest('.close')) {
            console.log('Botón de cerrar clickeado (evento delegado)');
            closeModal(appointmentModal);
        }
    });
    
    // Cerrar modal al hacer clic fuera del contenido
    window.addEventListener('click', function(e) {
        if (e.target === appointmentModal) {
            closeModal(appointmentModal);
        }
    });
    
    // Cerrar modal con tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && appointmentModal.style.display === 'block') {
            closeModal(appointmentModal);
        }
    });
    
    // Envío de formulario
    if (appointmentForm) {
        appointmentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Procesar formulario y cerrar modal al completar
            processAppointmentForm(this, state, function() {
                closeModal(appointmentModal);
                
                // Recargar eventos del calendario
                if (calendar && typeof calendar.refetchEvents === 'function') {
                    setTimeout(() => calendar.refetchEvents(), 500);
                } else {
                    // Si falla el refetch, recargar la página después de un momento
                    setTimeout(() => window.location.reload(), 1000);
                }
            });
        });
    }
    
    // Eliminar cita
    if (deleteAppointmentBtn) {
        deleteAppointmentBtn.addEventListener('click', function() {
            handleDeleteAppointment(state, elements, calendar);
        });
    }
    
    // Botón para deshacer cambios
    if (undoButton) {
        undoButton.addEventListener('click', function() {
            handleUndo(calendar);
        });
        
        // Comprobar si hay un estado de deshacer guardado en la carga inicial
        setTimeout(() => updateUndoButton(), 500);
    }
}

/**
 * Manejador para selección de fecha/hora en el calendario
 */
export function handleTimeSlotSelection(info, elements, config, state) {
    console.log("Slot seleccionado:", {
        start: info.startStr,
        end: info.endStr,
        clickedDate: info.start,
        endDate: info.end,
        slotDuration: config.settings.slotDuration
    });
    
    // Asegurarse de que tenemos usuarios disponibles
    if (!config.users || config.users.length === 0) {
        if (window.calendarUsers && window.calendarUsers.length > 0) {
            console.log("Actualizando usuarios desde ámbito global para el modal");
            config.users = window.calendarUsers;
        } else {
            console.warn("⚠️ No hay usuarios disponibles para la cita");
        }
    }
    
    // Crear objeto de evento temporal para el modal
    const event = {
        start: info.start,
        end: info.end || new Date(info.start.getTime() + 3600000),
        allDay: info.allDay,
        extendedProps: {
            calendarType: config.currentCalendarType || 'general'
        }
    };
    
    // Configurar modal usando el evento temporal
    setupCreateModal(elements, config, state, event);
    
    // Después de abrir el modal, verificar si los usuarios se cargaron correctamente
    setTimeout(() => {
        const userSelect = document.getElementById('user_id');
        if (userSelect && userSelect.options.length <= 1) {
            console.warn("⚠️ Los usuarios no se cargaron correctamente en el modal, intentando nuevamente...");
            
            // Intentar inicializar el select de usuarios nuevamente
            if (typeof window.initializeUserSelect === 'function') {
                window.initializeUserSelect(config.users);
            } else if (config.users && config.users.length > 0) {
                // Añadir opciones manualmente si la función no está disponible
                for (const user of config.users) {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.text = user.name;
                    if (user.color) option.dataset.color = user.color;
                    userSelect.appendChild(option);
                }
                console.log(`Se añadieron ${config.users.length} usuarios directamente al select`);
            }
        }
        
        // Asegurarse de que las fechas seleccionadas estén establecidas en el formulario
        // Esto es una medida adicional en caso de que setupCreateModal no las haya configurado correctamente
        import('./utils.js').then(utils => {
            const startField = document.getElementById('startTime') || document.getElementById('start_time');
            const endField = document.getElementById('endTime') || document.getElementById('end_time');
            
            if (startField && info.start) {
                startField.value = utils.formatDateForInput(info.start);
                console.log("Fecha de inicio establecida en el formulario:", startField.value);
            }
            
            if (endField && info.end) {
                endField.value = utils.formatDateForInput(info.end);
                console.log("Fecha de fin establecida en el formulario:", endField.value);
            } else if (endField && info.start) {
                // Si no hay fecha de fin, calcular una hora después
                const endTime = new Date(info.start.getTime());
                endTime.setHours(endTime.getHours() + 1);
                endField.value = utils.formatDateForInput(endTime);
                console.log("Fecha de fin calculada:", endField.value);
            }
        }).catch(error => {
            console.error("Error al importar módulo de utilidades:", error);
        });
    }, 100);
}

/**
 * Manejador para clic en evento existente
 */
export function handleEventClick(info, elements, config, state) {
    console.log("Evento seleccionado:", {
        id: info.event.id,
        title: info.event.title,
        start: info.event.startStr,
        end: info.event.endStr,
        extendedProps: info.event.extendedProps
    });
    
    // Asegurarse de que tenemos usuarios disponibles
    if (!config || !config.users || config.users.length === 0) {
        if (window.calendarUsers && window.calendarUsers.length > 0) {
            console.log("Actualizando usuarios desde ámbito global para el modal de edición");
            if (!config) config = {};
            config.users = window.calendarUsers;
        } else {
            console.warn("⚠️ No hay usuarios disponibles para editar la cita");
        }
    }
    
    // Configurar el modal de edición
    setupEditModal(info.event, elements, config, state);
    
    // Obtener detalles actualizados de la cita desde API protegida por JWT
    getJwtToken()
        .then(token => {
            if (!token) throw new Error('No se pudo obtener token JWT');
            return fetch(`api/get_appointment.php?id=${info.event.id}`, {
                headers: { 'Authorization': `Bearer ${token}` },
                credentials: 'include'
            });
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error de servidor: ${response.status} ${response.statusText}`);
            }
            return response.json();
        })
        .then(json => {
            if (!json || json.success !== true || !json.data) {
                const message = (json && json.message) ? json.message : 'Respuesta inválida del servidor';
                throw new Error(message);
            }
            const data = json.data;
            // Llenar el formulario con los datos recibidos
            const titleField = document.getElementById('title');
            const descriptionField = document.getElementById('description');
            const startTimeField = document.getElementById('startTime') || document.getElementById('start_time');
            const endTimeField = document.getElementById('endTime') || document.getElementById('end_time');
            const calendarTypeSelect = document.getElementById('calendarType') || document.getElementById('calendar_type');
            const userSelect = document.getElementById('user_id');

            if (titleField) titleField.value = data.title || '';
            if (descriptionField) descriptionField.value = data.description || '';

            if (startTimeField) startTimeField.value = data.start_time || '';
            if (endTimeField) endTimeField.value = data.end_time || '';

            if (calendarTypeSelect) {
                const calendarType = data.calendar_type || 'general';
                calendarTypeSelect.value = calendarType;
            }

            if (userSelect && data.user_id) {
                userSelect.value = data.user_id;
                const changeEvent = new Event('change');
                userSelect.dispatchEvent(changeEvent);
            }
        })
        .catch(error => {
            console.error('Error al cargar los detalles de la cita:', error);
            showNotification(`Error: ${error.message || 'Error al cargar los detalles'}`, 'error');
        });

    // Después de abrir el modal, verificar si los usuarios se cargaron correctamente
    setTimeout(() => {
        const userSelect = document.getElementById('user_id');
        if (userSelect && userSelect.options.length <= 1) {
            console.warn("⚠️ Los usuarios no se cargaron correctamente en el modal de edición, intentando nuevamente...");
            
            // Intentar inicializar el select de usuarios nuevamente
            if (typeof window.initializeUserSelect === 'function') {
                window.initializeUserSelect(config.users);
                
                // Después de inicializar, seleccionar el usuario correcto
                const userId = info.event.extendedProps.user_id || info.event.extendedProps.userId;
                if (userId && userSelect.options.length > 1) {
                    userSelect.value = userId;
                    
                    // Forzar evento change para actualizar el color
                    const changeEvent = new Event('change');
                    userSelect.dispatchEvent(changeEvent);
                }
            }
        }
    }, 100);
}

/**
 * Obtiene y cachea un JWT basado en la sesión actual (api/token.php)
 */
function getJwtToken() {
    if (window.jwtToken) return Promise.resolve(window.jwtToken);
    return fetch('api/token.php', { method: 'POST', credentials: 'include' })
        .then(r => r.json())
        .then(json => {
            if (json && json.success && json.data && json.data.token) {
                window.jwtToken = json.data.token;
                return window.jwtToken;
            }
            throw new Error(json && json.message ? json.message : 'No se pudo obtener token');
        })
        .catch(err => {
            console.error('Error obteniendo token JWT:', err);
            return null;
        });
}

/**
 * Manejador para eliminar cita
 */
function handleDeleteAppointment(state, elements, calendar) {
    // Obtener el ID de la cita desde múltiples fuentes posibles
    let appointmentId = null;
    
    // 1. Intentar obtener el ID desde el estado proporcionado
    if (state && state.currentAppointmentId) {
        appointmentId = state.currentAppointmentId;
    }
    
    // 2. Si no está en el estado proporcionado, verificar el estado global
    if (!appointmentId && window.state && window.state.currentAppointmentId) {
        appointmentId = window.state.currentAppointmentId;
    }
    
    // 3. Verificar si hay un ID en el botón de eliminar
    const deleteButton = document.getElementById('deleteAppointment');
    if (!appointmentId && deleteButton && deleteButton.dataset.id) {
        appointmentId = deleteButton.dataset.id;
    }
    
    // 4. Verificar si hay un ID en el campo oculto del formulario
    if (!appointmentId) {
        const idField = document.getElementById('appointmentId');
        if (idField && idField.value) {
            appointmentId = idField.value;
        }
    }
    
    // Si todavía no tenemos ID, mostrar error
    if (!appointmentId) {
        showNotification('No se ha seleccionado ninguna cita para eliminar', 'error');
        console.error('No se pudo encontrar el ID de la cita para eliminar. Estado:', {
            providedState: state,
            windowState: window.state,
            deleteButtonDataId: deleteButton ? deleteButton.dataset.id : 'no disponible',
            formFieldId: document.getElementById('appointmentId') ? document.getElementById('appointmentId').value : 'no disponible'
        });
        return;
    }
    
    console.log('Eliminando cita con ID:', appointmentId);
    
    if (confirm('¿Estás seguro de que deseas eliminar esta cita?')) {
        // Crear FormData para enviar
        const formData = new FormData();
        formData.append('id', appointmentId);
        formData.append('action', 'delete');
        
        // Mostrar indicador de carga
        showNotification('Eliminando cita...', 'info');
        
        // Enviar petición al servidor
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
                console.log('Respuesta del servidor (eliminar):', cleanText);
                return JSON.parse(cleanText);
            } catch (error) {
                console.error('No se pudo parsear la respuesta como JSON:', text);
                throw new Error('No se pudo parsear la respuesta del servidor como JSON');
            }
        })
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                
                // Cerrar modal
                closeModal(elements.appointmentModal);
                
                // Mostrar notificación de recarga
                showNotification('Cita eliminada. Recargando página...', 'success');
                
                // Siempre recargar la página después de eliminar una cita exitosamente
                setTimeout(() => {
                    window.location.reload();
                }, 800); // Pequeño retraso para que la notificación sea visible
            } else {
                showNotification(data.message || 'Error al eliminar la cita', 'error');
            }
        })
        .catch(error => {
            console.error('Error al eliminar cita:', error);
            showNotification(`Error: ${error.message || 'Error de conexión'}`, 'error');
        });
    }
}

/**
 * Maneja el arrastre de un evento
 */
export function handleEventDrop(info, currentCalendarType) {
    const eventId = info.event.id;
    const newStart = info.event.start;
    const newEnd = info.event.end || new Date(newStart.getTime() + 3600000); // Si no hay end, añadir 30 min
    const formattedStart = formatDateTimeForServer(newStart);
    const formattedEnd = formatDateTimeForServer(newEnd);
    
    console.log('Evento arrastrado:', {
        id: eventId,
        title: info.event.title,
        start: formattedStart,
        end: formattedEnd,
        calendarType: info.event.extendedProps.calendarType || 'general'
    });
    
    // Guardar estado anterior para la función de deshacer
    lastAction = 'update';
    lastEventState = {
        id: eventId,
        title: info.event.title,
        start: info.oldEvent.start,
        end: info.oldEvent.end,
        allDay: info.oldEvent.allDay,
        extendedProps: Object.assign({}, info.oldEvent.extendedProps)
    };
    
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
            
            // Actualizar la UI para el botón de deshacer
            updateUndoButton();
        } else {
            showNotification(data.message || 'Error al actualizar la cita', 'error');
            info.revert(); // Revertir el cambio
            
            // Limpiar lastAction y lastEventState para que no se pueda deshacer un evento fallido
            lastAction = null;
            lastEventState = null;
            updateUndoButton();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification(`Error: ${error.message || 'Error de conexión'}`, 'error');
        info.revert(); // Revertir el cambio
        
        // Limpiar lastAction y lastEventState para que no se pueda deshacer un evento fallido
        lastAction = null;
        lastEventState = null;
        updateUndoButton();
    });
}

/**
 * Maneja el redimensionamiento de un evento
 */
export function handleEventResize(info, currentCalendarType) {
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
    
    // Guardar estado anterior para la función de deshacer
    lastAction = 'update';
    lastEventState = {
        id: eventId,
        title: info.event.title,
        start: info.oldEvent.start,
        end: info.oldEvent.end,
        allDay: info.oldEvent.allDay,
        extendedProps: Object.assign({}, info.oldEvent.extendedProps)
    };
    
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
            
            // Actualizar la UI para el botón de deshacer
            updateUndoButton();
        } else {
            showNotification(data.message || 'Error al actualizar el horario', 'error');
            info.revert(); // Revertir el cambio
            
            // Limpiar lastAction y lastEventState para que no se pueda deshacer un evento fallido
            lastAction = null;
            lastEventState = null;
            updateUndoButton();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification(`Error: ${error.message || 'Error de conexión'}`, 'error');
        info.revert(); // Revertir el cambio
        
        // Limpiar lastAction y lastEventState para que no se pueda deshacer un evento fallido
        lastAction = null;
        lastEventState = null;
        updateUndoButton();
    });
}

/**
 * Actualiza la visibilidad del botón de deshacer
 */
export function updateUndoButton() {
    const undoButton = document.getElementById('undoButton');
    if (!undoButton) return;
    
    if (lastAction && lastEventState) {
        undoButton.style.display = 'flex'; // Asegurar que se muestre
        undoButton.classList.add('show');
        
        // Sincronizar con variables globales para persistencia
        window.lastAction = lastAction;
        window.lastEventState = lastEventState;
        
        console.log('Botón deshacer activado', {action: lastAction, event: lastEventState.id});
    } else {
        undoButton.classList.remove('show');
        setTimeout(() => {
            if (!lastAction && !lastEventState) {
                undoButton.style.display = 'none';
            }
        }, 300); // Esperar a que termine la animación
        
        // Limpiar variables globales
        window.lastAction = null;
        window.lastEventState = null;
    }
}

/**
 * Maneja la funcionalidad de deshacer
 */
function handleUndo(calendar) {
    if (!lastAction || !lastEventState || !calendar) return;
    
    // Obtener el evento actual
    const currentEvent = calendar.getEventById(lastEventState.id);
    if (!currentEvent) {
        showNotification('No se puede deshacer: El evento ya no existe', 'error');
        return;
    }
    
    // Mostrar notificación
    showNotification('Deshaciendo último cambio...', 'info');
    
    // Crear FormData con los datos originales
    const formData = new FormData();
    formData.append('id', lastEventState.id);
    formData.append('action', 'update');
    formData.append('title', lastEventState.title);
    formData.append('description', lastEventState.extendedProps.description || '');
    formData.append('start_time', formatDateTimeForServer(lastEventState.start));
    formData.append('end_time', formatDateTimeForServer(lastEventState.end));
    formData.append('calendar_type', lastEventState.extendedProps.calendarType || 'general');
    
    // Si el evento tiene un usuario asignado, incluirlo
    if (lastEventState.extendedProps.user_id) {
        formData.append('user_id', lastEventState.extendedProps.user_id);
    }
    
    // Enviar petición al servidor
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
            console.log('Respuesta del servidor (deshacer):', cleanText);
            return JSON.parse(cleanText);
        } catch (error) {
            console.error('No se pudo parsear la respuesta como JSON:', text);
            throw new Error('No se pudo parsear la respuesta del servidor como JSON');
        }
    })
    .then(data => {
        if (data.success) {
            showNotification('Cambio deshecho correctamente', 'success');
            
            // Actualizar el evento en el calendario
            currentEvent.setProp('title', lastEventState.title);
            currentEvent.setStart(lastEventState.start);
            currentEvent.setEnd(lastEventState.end);
            currentEvent.setAllDay(lastEventState.allDay);
            
            // Recargar eventos para asegurar consistencia
            setTimeout(() => calendar.refetchEvents(), 500);
            
            // Limpiar estado de deshacer
            lastAction = null;
            lastEventState = null;
            updateUndoButton();
        } else {
            showNotification(data.message || 'Error al deshacer cambio', 'error');
        }
    })
    .catch(error => {
        console.error('Error al deshacer cambio:', error);
        showNotification(`Error: ${error.message || 'Error de conexión'}`, 'error');
    });
} 