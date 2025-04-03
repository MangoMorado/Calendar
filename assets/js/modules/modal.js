/**
 * Módulo para manejar operaciones del modal
 * Gestiona la creación, edición y visualización de citas en el modal
 */
import { formatDateForInput, formatDateTimeForServer } from './utils.js';
import { showNotification } from './ui.js';

/**
 * Configura el modal para crear una nueva cita
 * @param {Object} elements - Referencias a elementos DOM
 * @param {Object} config - Configuración de la aplicación
 * @param {Object} state - Estado de la aplicación
 * @param {Object|null} selectedEvent - Evento seleccionado en el calendario (opcional)
 */
export function setupCreateModal(elements, config, state, selectedEvent = null) {
    // Obtener referencias del modal
    const { appointmentForm, appointmentModal } = elements;
    const { currentCalendarType } = config;
    
    // Actualizar título del modal
    const modalTitle = document.getElementById('modalTitle');
    if (modalTitle) {
        modalTitle.innerHTML = '<i class="bi bi-calendar-plus"></i> Crear Cita';
    }
    
    // Ocultar botón eliminar
    const deleteButton = document.getElementById('deleteAppointment');
    if (deleteButton) {
        deleteButton.style.display = 'none';
    }
    
    // Resetear formulario
    appointmentForm.reset();
    
    // Establecer valores por defecto basados en el momento actual o en el evento seleccionado
    let startDate, endDate;
    
    if (selectedEvent && selectedEvent.start) {
        // Usar las fechas del evento seleccionado
        console.log("Usando fechas del evento seleccionado:", selectedEvent);
        startDate = new Date(selectedEvent.start);
        
        if (selectedEvent.end) {
            endDate = new Date(selectedEvent.end);
        } else {
            // Si no hay fecha de fin, calcular una hora después
            endDate = new Date(startDate.getTime());
            endDate.setHours(endDate.getHours() + 1);
        }
        
        // Si es un evento de todo el día, ajustar el checkbox
        const allDayCheckbox = document.getElementById('allDayEvent');
        if (allDayCheckbox && selectedEvent.allDay) {
            allDayCheckbox.checked = true;
        }
    } else {
        // Usar fecha y hora actuales
        startDate = new Date();
        startDate.setMinutes(Math.ceil(startDate.getMinutes() / 30) * 30, 0, 0); // Redondear a la media hora
        
        endDate = new Date(startDate);
        endDate.setHours(endDate.getHours() + 1);
    }
    
    // Formatear y asignar fechas a los campos del formulario
    console.log("Asignando fechas al formulario:", { 
        inicio: formatDateForInput(startDate), 
        fin: formatDateForInput(endDate) 
    });
    
    // Llenar campos de fecha con valores calculados
    fillFormField('startTime', formatDateForInput(startDate), ['start_time']);
    fillFormField('endTime', formatDateForInput(endDate), ['end_time']);
    
    // Preseleccionar tipo de calendario
    let calendarTypeValue = 'estetico'; // Valor por defecto
    
    // Si hay un evento seleccionado con tipo específico, usar ese tipo
    if (selectedEvent && selectedEvent.extendedProps && selectedEvent.extendedProps.calendarType) {
        calendarTypeValue = selectedEvent.extendedProps.calendarType;
    } 
    // Si no, y estamos en una vista específica, usar ese tipo
    else if (currentCalendarType && currentCalendarType !== 'general') {
        calendarTypeValue = currentCalendarType;
    }
    
    fillFormField('calendarType', calendarTypeValue, ['calendar_type']);
    
    // Actualizar estado
    state.isEditMode = false;
    state.currentAppointmentId = null;
    
    // Cargar usuarios en el select si es necesario
    const userSelect = document.getElementById('user_id');
    if (userSelect) {
        // Cargar usuarios (esta función ahora verifica si ya están cargados)
        loadUsersIntoSelect(config.users);
        
        // Preseleccionar usuario si el evento tiene uno
        if (selectedEvent && selectedEvent.extendedProps && selectedEvent.extendedProps.user_id) {
            userSelect.value = selectedEvent.extendedProps.user_id;
        }
        
        // Disparar evento change para actualizar la vista previa del color
        const changeEvent = new Event('change');
        userSelect.dispatchEvent(changeEvent);
    }
    
    // Mostrar modal
    appointmentModal.style.display = 'block';
}

/**
 * Configura el modal para editar una cita existente
 * @param {Object} event - Objeto de evento FullCalendar
 * @param {Object} elements - Referencias a elementos DOM
 * @param {Object} config - Configuración de la aplicación  
 * @param {Object} state - Estado de la aplicación
 */
export function setupEditModal(event, elements, config, state) {
    // Obtener referencias del modal
    const { appointmentForm, appointmentModal } = elements;
    
    // Actualizar título del modal
    const modalTitle = document.getElementById('modalTitle');
    if (modalTitle) {
        modalTitle.innerHTML = '<i class="bi bi-calendar-check"></i> Editar Cita';
    }
    
    // Resetear formulario primero para evitar datos antiguos
    appointmentForm.reset();
    
    // Registrar información para depuración
    console.log("Evento a editar:", event);
    console.log("Propiedades extendidas:", event.extendedProps);
    
    // Establecer el ID de la cita
    const appointmentId = event.id;
    
    // Buscar el campo de ID con diferentes posibles nombres
    const idField = document.getElementById('appointmentId') || 
                   document.querySelector('input[name="id"]');
    if (idField) {
        idField.value = appointmentId;
    } else {
        console.warn("Campo de ID no encontrado");
    }
    
    // Llenar campos del formulario
    fillFormField('title', event.title);
    fillFormField('description', event.extendedProps.description || '');
    
    // Verificar si es un evento de todo el día
    const isAllDay = event.allDay;
    const allDayCheckbox = document.getElementById('allDayEvent');
    if (allDayCheckbox) {
        allDayCheckbox.checked = isAllDay;
    }
    
    // Formatear fechas para el input datetime-local
    try {
        // Intentar crear las fechas directamente desde los valores
        let startDate = new Date(event.start);
        
        // Si el evento tiene una fecha de fin, usarla
        let endDate;
        if (event.end) {
            endDate = new Date(event.end);
        } else {
            // Si no tiene una fecha de fin, crear una hora después o al día siguiente
            endDate = new Date(startDate.getTime());
            if (isAllDay) {
                endDate.setDate(endDate.getDate() + 1);
            } else {
                endDate.setHours(endDate.getHours() + 1);
            }
        }
        
        console.log("Fecha de inicio procesada:", startDate);
        console.log("Fecha de fin procesada:", endDate);
        
        // Asignar fechas a los campos (buscando por múltiples IDs posibles)
        fillFormField('startTime', formatDateForInput(startDate), ['start_time']);
        fillFormField('endTime', formatDateForInput(endDate), ['end_time']);
        
    } catch (error) {
        console.error("Error al procesar las fechas:", error);
        // En caso de error, usar fechas actuales
        const now = new Date();
        const later = new Date(now.getTime() + 3600000);
        fillFormField('startTime', formatDateForInput(now), ['start_time']);
        fillFormField('endTime', formatDateForInput(later), ['end_time']);
    }
    
    // Establecer el tipo de calendario
    fillFormField('calendarType', event.extendedProps.calendarType || 'general', ['calendar_type']);
    
    // Obtener el ID del usuario si existe
    const userId = event.extendedProps.user_id || event.extendedProps.userId;
    console.log("Usuario a seleccionar:", userId);
    
    // Cargar usuarios en el select si es necesario
    const userSelect = document.getElementById('user_id');
    if (userSelect) {
        // Primero cargar los usuarios (esta función ahora verifica si ya están cargados)
        loadUsersIntoSelect(config.users);
        
        // Después de cargar, asegurarse de que el usuario correcto esté seleccionado
        if (userId) {
            console.log(`Configurando usuario ID ${userId} como seleccionado`);
            userSelect.value = userId;
            // Forzar evento change en el select para actualizar el color
            const changeEvent = new Event('change');
            userSelect.dispatchEvent(changeEvent);
        }
    }
    
    // Mostrar botón eliminar y configurarlo correctamente
    const deleteButton = document.getElementById('deleteAppointment');
    if (deleteButton) {
        deleteButton.style.display = 'inline-block';
        deleteButton.dataset.id = appointmentId;
        
        // Asegurarnos de que el evento click está configurado correctamente
        // Primero removemos cualquier evento previo para evitar duplicación
        const newDeleteButton = deleteButton.cloneNode(true);
        deleteButton.parentNode.replaceChild(newDeleteButton, deleteButton);
        
        // Añadir evento de clic
        newDeleteButton.addEventListener('click', function() {
            // Verificar si hay manejadores de eventos globales
            if (typeof window.handleDeleteAppointment === 'function') {
                window.handleDeleteAppointment(state, elements);
            } else {
                // Si no hay manejador global, usar estado básico
                if (confirm('¿Estás seguro de que deseas eliminar esta cita?')) {
                    const formData = new FormData();
                    formData.append('id', appointmentId);
                    formData.append('action', 'delete');
                    
                    // Mostrar indicador de carga
                    if (typeof showNotification === 'function') {
                        showNotification('Eliminando cita...', 'info');
                    }
                    
                    fetch('process_appointment.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Usar showNotification si está disponible
                            if (typeof showNotification === 'function') {
                                showNotification(data.message || 'Cita eliminada con éxito', 'success');
                                showNotification('Cita eliminada. Recargando página...', 'success');
                            } else {
                                alert(data.message || 'Cita eliminada con éxito');
                            }
                            
                            // Siempre recargar la página
                            setTimeout(() => {
                                window.location.reload();
                            }, 800);
                        } else {
                            // Usar showNotification si está disponible
                            if (typeof showNotification === 'function') {
                                showNotification(data.message || 'Error al eliminar la cita', 'error');
                            } else {
                                alert(data.message || 'Error al eliminar la cita');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Usar showNotification si está disponible
                        if (typeof showNotification === 'function') {
                            showNotification('Error al procesar la solicitud', 'error');
                        } else {
                            alert('Error al procesar la solicitud');
                        }
                    });
                }
            }
        });
    }
    
    // Actualizar estado local
    state.isEditMode = true;
    state.currentAppointmentId = appointmentId;
    
    // Sincronizar con el estado global (si existe)
    if (window.state) {
        window.state.isEditMode = true;
        window.state.currentAppointmentId = appointmentId;
    }
    
    console.log('Estado actualizado para edición:', {
        eventId: appointmentId,
        localState: state,
        windowState: window.state
    });
    
    // Mostrar modal
    appointmentModal.style.display = 'block';
}

/**
 * Función auxiliar para llenar un campo del formulario buscando por múltiples IDs posibles
 * @param {string} primaryId - ID principal del campo
 * @param {string} value - Valor a asignar
 * @param {Array} alternativeIds - IDs alternativos a probar si el principal no existe
 */
function fillFormField(primaryId, value, alternativeIds = []) {
    let field = document.getElementById(primaryId);
    
    // Si no se encuentra con el ID principal, buscar por IDs alternativos
    if (!field && alternativeIds.length > 0) {
        for (const altId of alternativeIds) {
            field = document.getElementById(altId);
            if (field) break;
        }
    }
    
    // Si aún no se encuentra, buscar por nombre
    if (!field) {
        field = document.querySelector(`[name="${primaryId}"]`);
        if (!field && alternativeIds.length > 0) {
            for (const altId of alternativeIds) {
                field = document.querySelector(`[name="${altId}"]`);
                if (field) break;
            }
        }
    }
    
    // Si se encontró el campo, asignar el valor
    if (field) {
        field.value = value;
    } else {
        console.warn(`Campo ${primaryId} no encontrado`);
    }
}

/**
 * Carga usuarios en el select del formulario
 * @param {Array} users - Lista de usuarios disponibles
 */
export function loadUsersIntoSelect(users) {
    const userSelect = document.getElementById('user_id');
    if (!userSelect) {
        console.warn('Select de usuarios no encontrado');
        return;
    }
    
    // Si no hay usuarios en la lista proporcionada, verificar si están disponibles en el ámbito global
    if (!Array.isArray(users) || users.length === 0) {
        console.warn('No hay usuarios proporcionados, comprobando en ámbito global...');
        if (window.calendarUsers && Array.isArray(window.calendarUsers) && window.calendarUsers.length > 0) {
            users = window.calendarUsers;
            console.log(`Usando ${users.length} usuarios del ámbito global.`);
        } else {
            console.error('No se encontraron usuarios disponibles');
            return;
        }
    }
    
    // Guardar el valor seleccionado actual si existe
    const currentSelectedValue = userSelect.value;
    console.log("Valor actual seleccionado:", currentSelectedValue);
    
    // Comprobar si realmente tenemos opciones con valores en el select
    let hasValidOptions = false;
    for (let i = 0; i < userSelect.options.length; i++) {
        if (userSelect.options[i].value && userSelect.options[i].value !== "") {
            hasValidOptions = true;
            break;
        }
    }
    
    // Si no hay opciones válidas o solo está la opción vacía, forzar la carga de usuarios
    if (!hasValidOptions || userSelect.options.length <= 1) {
        console.log("Inicializando select con usuarios:", users.length);
        
        // Limpiar todas las opciones
        userSelect.innerHTML = '';
        
        // Añadir opción por defecto
        const defaultOption = document.createElement('option');
        defaultOption.value = "";
        defaultOption.text = "-- Selecciona un usuario --";
        userSelect.appendChild(defaultOption);
        
        // Añadir usuarios desde la lista
        users.forEach(user => {
            const option = document.createElement('option');
            option.value = user.id;
            option.text = user.name;
            if (user.color) {
                option.dataset.color = user.color;
            }
            
            // Marcar como seleccionado si coincide con el usuario actual
            if (String(user.id) === String(currentSelectedValue)) {
                option.selected = true;
            }
            
            userSelect.appendChild(option);
            console.log(`Añadido usuario: ${user.name} (ID: ${user.id})`);
        });
        
        console.log(`Se cargaron ${users.length} usuarios en el select`);
    } else {
        console.log("El select ya tiene opciones válidas, manteniendo estructura actual.");
        
        // Si ya hay un valor seleccionado, mantenerlo
        if (currentSelectedValue) {
            userSelect.value = currentSelectedValue;
        }
    }
    
    // Verificar el estado final del select
    console.log(`Estado final del select: ${userSelect.options.length} opciones`);
    for (let i = 0; i < Math.min(userSelect.options.length, 5); i++) {
        console.log(`- Opción ${i}: valor='${userSelect.options[i].value}', texto='${userSelect.options[i].text}'`);
    }
    
    // Actualizar la vista previa del color
    updateColorPreview();
}

/**
 * Actualiza la vista previa del color según el usuario seleccionado
 */
export function updateColorPreview() {
    const userSelect = document.getElementById('user_id');
    const colorPreview = document.getElementById('colorPreview');
    
    if (!userSelect || !colorPreview) {
        console.warn('No se encontraron elementos para previsualización de color');
        return;
    }
    
    // Obtener opción seleccionada
    const selectedOption = userSelect.options[userSelect.selectedIndex];
    
    if (!selectedOption || selectedOption.value === "") {
        // Ocultar o reset del preview cuando no hay selección
        const colorCircle = colorPreview.querySelector('.color-circle');
        const colorCode = colorPreview.querySelector('.color-code');
        
        if (colorCircle) {
            colorCircle.style.backgroundColor = '#ccc';
        }
        
        if (colorCode) {
            colorCode.textContent = 'Sin usuario seleccionado';
        }
        
        return;
    }
    
    // Intentar obtener el color desde data-color del option
    let color = selectedOption.dataset.color;
    
    // Si no hay color en data-color, buscar en los datos de usuario
    if (!color && window.calendarUsers) {
        const userId = selectedOption.value;
        const user = window.calendarUsers.find(user => String(user.id) === String(userId));
        if (user && user.color) {
            color = user.color;
        }
    }
    
    console.log(`Color para usuario ${selectedOption.text}: ${color}`);
    
    // Aplicar el color
    if (color) {
        // Actualizar el círculo y el texto
        const colorCircle = colorPreview.querySelector('.color-circle');
        const colorCode = colorPreview.querySelector('.color-code');
        
        if (colorCircle) {
            colorCircle.style.backgroundColor = color;
        }
        
        if (colorCode) {
            colorCode.textContent = color;
        }
    } else {
        console.warn(`No se encontró color para el usuario con ID ${selectedOption.value}`);
    }
}

/**
 * Procesa el formulario de cita (creación/edición)
 * @param {HTMLFormElement} form - Formulario a procesar
 * @param {Object} state - Estado de la aplicación
 * @param {Function} onSuccess - Función a ejecutar en caso de éxito
 */
export function processAppointmentForm(form, state, onSuccess) {
    // Crear FormData con los datos del formulario
    const formData = new FormData(form);
    
    // Añadir acción según el estado
    if (state.isEditMode) {
        formData.append('id', state.currentAppointmentId);
        formData.append('action', 'update');
    } else {
        formData.append('action', 'create');
    }
    
    // Normalizar nombres de campos (para manejar diferentes convenciones de nombres en los modales)
    normalizeFormFields(formData);
    
    // Validar campos obligatorios
    const title = formData.get('title');
    if (!title || title.trim() === '') {
        showNotification('El título de la cita es obligatorio', 'error');
        return;
    }
    
    // Validar usuario seleccionado (si el campo está presente en el formulario)
    const userSelect = document.getElementById('user_id');
    if (userSelect && userSelect.options.length > 0) {
        const userId = formData.get('user_id');
        if (!userId || userId === '') {
            showNotification('Debes seleccionar un usuario', 'error');
            return;
        }
    }
    
    // Validar duración
    const startTime = new Date(formData.get('start_time'));
    const endTime = new Date(formData.get('end_time'));
    
    if (isNaN(startTime.getTime()) || isNaN(endTime.getTime())) {
        showNotification('Las fechas de inicio y fin son obligatorias y deben ser válidas', 'error');
        return;
    }
    
    if (endTime <= startTime) {
        showNotification('La hora de fin debe ser posterior a la hora de inicio', 'error');
        return;
    }
    
    // Registrar los datos que se están enviando para depuración
    console.log('Enviando datos de cita:', Object.fromEntries(formData));
    
    // Mostrar indicador de carga
    showNotification('Procesando cita...', 'info');
    
    // Enviar petición al servidor
    fetch('process_appointment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Verificar si la respuesta es correcta
        if (!response.ok) {
            throw new Error(`Error de servidor: ${response.status} ${response.statusText}`);
        }
        
        // Recuperar el texto de la respuesta primero
        return response.text();
    })
    .then(text => {
        // Intentar parsear como JSON de manera más permisiva
        try {
            // Eliminar cualquier espacio en blanco o carácter no deseado al principio o final
            const cleanText = text.trim();
            console.log('Respuesta del servidor limpia:', cleanText);
            
            // Parsear como JSON
            return JSON.parse(cleanText);
        } catch (error) {
            console.error('No se pudo parsear la respuesta como JSON:', text);
            throw new Error('No se pudo parsear la respuesta del servidor como JSON');
        }
    })
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            
            // Si es una NUEVA cita, recargar la página completamente
            if (!state.isEditMode) {
                console.log('Nueva cita creada. Recargando página...');
                showNotification('Cita creada. Recargando página...', 'success');
                
                // Pequeño retraso para que la notificación sea visible
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
                return; // Salir para no ejecutar onSuccess
            }
            
            // Ejecutar callback de éxito para actualizaciones (no creaciones)
            if (typeof onSuccess === 'function') {
                onSuccess();
            }
        } else {
            showNotification(data.message || 'Ha ocurrido un error', 'error');
        }
    })
    .catch(error => {
        console.error('Error al procesar cita:', error);
        showNotification(`Error: ${error.message || 'Error de conexión'}`, 'error');
    });
}

/**
 * Normaliza los nombres de los campos del formulario para asegurar compatibilidad
 * @param {FormData} formData - Objeto FormData a normalizar
 */
function normalizeFormFields(formData) {
    // Mapeo de posibles nombres de campos a nombres estandarizados
    const fieldMapping = {
        // Campos de tiempo
        'startTime': 'start_time',
        'endTime': 'end_time',
        // Tipo de calendario
        'calendarType': 'calendar_type',
        // ID de usuario
        'user_id': 'user_id',
        // Otros campos que podrían tener diferentes nombres
        'allDayEvent': 'all_day'
    };
    
    // Crear copia de los datos originales
    const originalData = {};
    for (let [key, value] of formData.entries()) {
        originalData[key] = value;
    }
    
    // Verificar y normalizar campos
    for (const [oldName, standardName] of Object.entries(fieldMapping)) {
        // Si ya existe el nombre estándar pero también el nombre alternativo, usar el valor del estándar
        if (originalData[standardName] !== undefined && originalData[oldName] !== undefined) {
            continue; // El campo estándar ya existe, no necesitamos hacer nada
        }
        
        // Si existe el nombre alternativo pero no el estándar, copiar el valor
        if (originalData[oldName] !== undefined && originalData[standardName] === undefined) {
            formData.append(standardName, originalData[oldName]);
        }
        
        // Si existe el nombre estándar pero con formato diferente (ej: camelCase vs snake_case)
        // esto ya está cubierto por los casos anteriores
    }
    
    // Registrar para depuración
    console.log('Campos normalizados:', Object.fromEntries(formData));
} 