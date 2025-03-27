/**
 * Manejo de eventos del calendario
 * Funciones para manipular eventos (crear, editar, eliminar, arrastrar)
 */

// Función para manejar la selección de fecha
function handleDateSelection(info) {
    // Obtener la fecha y hora del clic
    const clickedDate = info.start;
    
    // Detectar si es un evento de todo el día
    const isAllDay = info.allDay;
    
    // Formatear la fecha y hora para el campo datetime-local
    // Formato YYYY-MM-DDThh:mm según el estándar HTML5
    const formattedStartDateTime = formatDateForInput(clickedDate);
    
    // Calcular la hora de fin (por defecto 1 hora después para eventos normales o el día siguiente para todo el día)
    const endDate = new Date(clickedDate);
    if (isAllDay) {
        endDate.setDate(endDate.getDate() + 1); // Añadir un día para eventos de todo el día
    } else {
        endDate.setHours(endDate.getHours() + 1); // Añadir una hora para eventos normales
    }
    const formattedEndDateTime = formatDateForInput(endDate);
    
    // Configurar el formulario para crear cita
    document.getElementById("modalTitle").innerHTML = "<i class=\"bi bi-calendar-plus\"></i> Crear Cita";
    
    // Limpiar formulario y establecer valores iniciales
    document.getElementById("appointmentForm").reset();
    document.getElementById("appointmentId").value = ""; // Asegurar que el ID esté vacío
    document.getElementById("startTime").value = formattedStartDateTime;
    document.getElementById("endTime").value = formattedEndDateTime;
    document.getElementById("allDayEvent").checked = isAllDay;
    
    // Seleccionar el tipo de calendario actual
    if (state.currentCalendarType === "general") {
        // Ahora permitimos usar el tipo general
        document.getElementById("calendarType").value = "general";
    } else {
        // Si estamos en una vista específica, mantener ese tipo
        document.getElementById("calendarType").value = state.currentCalendarType;
    }
    
    // Cargar usuarios en el select
    loadUsersIntoSelect();
    
    // Ocultar botón eliminar
    document.getElementById("deleteAppointment").style.display = "none";
    
    // Actualizar estado
    state.isEditMode = false;
    state.currentAppointmentId = null;
    
    // Mostrar modal
    document.getElementById("appointmentModal").style.display = "block";
}

// Función para manejar clic en evento existente
function handleEventClick(event) {
    // Limpiar formulario primero para evitar datos antiguos
    document.getElementById("appointmentForm").reset();
    
    // Registrar información para depuración
    console.log("Evento clickeado:", event);
    console.log("Propiedades extendidas:", event.extendedProps);
    
    // Configurar título del modal
    document.getElementById("modalTitle").innerHTML = "<i class=\"bi bi-calendar-check\"></i> Editar Cita";
    
    // Establecer el ID de la cita en el campo oculto
    document.getElementById("appointmentId").value = event.id;
    
    // Llenar datos del formulario
    document.getElementById("title").value = event.title;
    document.getElementById("description").value = event.extendedProps.description || "";
    
    // Verificar si es un evento de todo el día
    const isAllDay = event.allDay;
    document.getElementById("allDayEvent").checked = isAllDay;
    
    // Formatear fechas para el input datetime-local
    // IMPORTANTE: Asegurarnos de crear correctamente los objetos Date
    let startDate, endDate;
    
    console.log("Fecha original de inicio:", event.start);
    console.log("Fecha original de fin:", event.end);
    
    try {
        // Intentar crear las fechas directamente desde los valores
        startDate = new Date(event.start);
        
        // Si el evento tiene una fecha de fin, usarla
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
        
        // IMPORTANTE: Asignar las fechas ANTES de cargar usuarios para evitar que se sobreescriban
        const startTimeFormatted = formatDateForInput(startDate);
        const endTimeFormatted = formatDateForInput(endDate);
        
        console.log("Fecha inicio formateada:", startTimeFormatted);
        console.log("Fecha fin formateada:", endTimeFormatted);
        
        document.getElementById("startTime").value = startTimeFormatted;
        document.getElementById("endTime").value = endTimeFormatted;
        
    } catch (error) {
        console.error("Error al procesar las fechas:", error);
        // En caso de error, usar fechas actuales
        const now = new Date();
        const later = new Date(now.getTime() + 3600000);
        document.getElementById("startTime").value = formatDateForInput(now);
        document.getElementById("endTime").value = formatDateForInput(later);
    }
    
    // Establecer el tipo de calendario
    document.getElementById("calendarType").value = event.extendedProps.calendarType;
    
    // *** MUY IMPORTANTE *** Guardar el ID del usuario antes de cargar el select
    // Verificar tanto user_id como userId para mayor compatibilidad
    const userId = event.extendedProps.user_id || event.extendedProps.userId;
    const userName = event.extendedProps.user || event.extendedProps.user_name;
    
    console.log("Usuario a seleccionar:", userId, "Nombre:", userName);
    
    // Primero establecer el valor del usuario, muy importante
    if (userId) {
        document.getElementById("user_id").value = userId;
    }
    
    // Ahora cargar los usuarios en el select
    loadUsersIntoSelect();
    
    // Después de cargar, asegurarse de que el usuario seleccionado es correcto
    if (userId) {
        // Forzar nuevamente la selección del usuario después de cargar
        setTimeout(() => {
            document.getElementById("user_id").value = userId;
            updateColorPreview();
            console.log("Usuario establecido después de timeout:", document.getElementById("user_id").value);
        }, 100);
    }
    
    // Mostrar botón eliminar
    document.getElementById("deleteAppointment").style.display = "inline-block";
    document.getElementById("deleteAppointment").dataset.id = event.id;
    
    // Actualizar estado
    state.isEditMode = true;
    state.currentAppointmentId = event.id;
    
    // Verificar una vez más que las fechas se hayan establecido correctamente
    console.log("Fecha inicio en modal:", document.getElementById("startTime").value);
    console.log("Fecha fin en modal:", document.getElementById("endTime").value);
    
    // Mostrar modal
    document.getElementById("appointmentModal").style.display = "block";
}

// Función para manejar el arrastre de eventos (cambio de fecha/hora)
function handleEventDrop(info) {
    const event = info.event;
    const eventId = event.id;
    
    // Crear nuevos objetos Date para asegurar que se manejen correctamente
    const startDate = new Date(event.start);
    // Si el evento no tiene fecha de fin, calcular una hora después
    const endDate = event.end ? new Date(event.end) : new Date(startDate.getTime() + 60 * 60 * 1000);
    
    console.log("Arrastrando evento - Fecha original:", info.oldEvent.start);
    console.log("Arrastrando evento - Nueva fecha:", startDate);
    
    // Formatear fechas para MySQL (YYYY-MM-DD HH:MM:SS)
    const newStart = formatDateTimeForMySQL(startDate);
    const newEnd = formatDateTimeForMySQL(endDate);
    
    console.log("Formato MySQL - Nueva fecha inicio:", newStart);
    console.log("Formato MySQL - Nueva fecha fin:", newEnd);
    
    // Confirmar el cambio
    if (!confirm("¿Está seguro de cambiar esta cita a " + formatDateForDisplay(startDate) + "?")) {
        info.revert();
        return;
    }
    
    // Mostrar notificación de carga
    displaySuccessMessage("Actualizando cita...");
    
    // Actualizar el evento en la base de datos
    const formData = new FormData();
    formData.append('action', 'update_date');
    formData.append('appointmentId', eventId);
    formData.append('start', newStart);
    formData.append('end', newEnd);
    
    // Para debugging
    for (const [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }
    
    fetch("api/appointments.php", {
        method: "POST",
        body: formData
    })
    .then(response => {
        // Verificar si la respuesta es exitosa
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        // Intentar parsear como JSON
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (error) {
                console.error("Error al parsear JSON:", error);
                console.error("Respuesta del servidor:", text);
                throw new Error("La respuesta del servidor no es JSON válido");
            }
        });
    })
    .then(data => {
        if (data.success) {
            displaySuccessMessage("Cita actualizada exitosamente");
            // Forzar recarga de eventos para asegurar consistencia
            refreshCalendarEvents();
        } else {
            displayErrorMessage("Error al actualizar la cita: " + data.message);
            info.revert();
        }
    })
    .catch(error => {
        console.error("Error:", error);
        displayErrorMessage("Error al procesar la solicitud: " + error.message);
        info.revert();
    });
}

// Función para manejar el redimensionamiento de eventos (cambio de duración)
function handleEventResize(info) {
    const event = info.event;
    const eventId = event.id;
    
    // Crear nuevos objetos Date para asegurar que se manejen correctamente
    const startDate = new Date(event.start);
    const endDate = new Date(event.end);
    
    console.log("Redimensionando evento - Fecha original inicio:", info.oldEvent.start);
    console.log("Redimensionando evento - Fecha original fin:", info.oldEvent.end);
    console.log("Redimensionando evento - Nueva fecha inicio:", startDate);
    console.log("Redimensionando evento - Nueva fecha fin:", endDate);
    
    // Formatear fechas para MySQL (YYYY-MM-DD HH:MM:SS)
    const newStart = formatDateTimeForMySQL(startDate);
    const newEnd = formatDateTimeForMySQL(endDate);
    
    console.log("Formato MySQL - Nueva fecha inicio:", newStart);
    console.log("Formato MySQL - Nueva fecha fin:", newEnd);
    
    // Confirmar el cambio
    if (!confirm("¿Está seguro de cambiar la duración de esta cita?")) {
        info.revert();
        return;
    }
    
    // Mostrar notificación de carga
    displaySuccessMessage("Actualizando duración...");
    
    // Actualizar el evento en la base de datos
    const formData = new FormData();
    formData.append('action', 'update_date');
    formData.append('appointmentId', eventId);
    formData.append('start', newStart);
    formData.append('end', newEnd);
    
    // Para debugging
    for (const [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }
    
    fetch("api/appointments.php", {
        method: "POST",
        body: formData
    })
    .then(response => {
        // Verificar si la respuesta es exitosa
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        // Intentar parsear como JSON
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (error) {
                console.error("Error al parsear JSON:", error);
                console.error("Respuesta del servidor:", text);
                throw new Error("La respuesta del servidor no es JSON válido");
            }
        });
    })
    .then(data => {
        if (data.success) {
            displaySuccessMessage("Duración de la cita actualizada exitosamente");
            // Forzar recarga de eventos para asegurar consistencia
            refreshCalendarEvents();
        } else {
            displayErrorMessage("Error al actualizar la duración: " + data.message);
            info.revert();
        }
    })
    .catch(error => {
        console.error("Error:", error);
        displayErrorMessage("Error al procesar la solicitud: " + error.message);
        info.revert();
    });
} 