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
    
    // Pre-llenar los campos en el modal
    document.getElementById("startTime").value = formattedStartDateTime;
    document.getElementById("endTime").value = formattedEndDateTime;
    
    // Marcar la casilla de todo el día si corresponde
    document.getElementById("allDayEvent").checked = isAllDay;
    
    // Configurar el formulario para crear cita
    document.getElementById("modalTitle").innerHTML = "<i class=\"bi bi-calendar-plus\"></i> Crear Cita";
    document.getElementById("appointmentForm").reset();
    document.getElementById("startTime").value = formattedStartDateTime;
    document.getElementById("endTime").value = formattedEndDateTime;
    document.getElementById("allDayEvent").checked = isAllDay;
    
    // Seleccionar el tipo de calendario actual
    if (state.currentCalendarType === "general") {
        // Si estamos en la vista general, seleccionar estético por defecto
        document.getElementById("calendarType").value = "estetico";
    } else {
        // Si estamos en una vista específica, mantener ese tipo
        document.getElementById("calendarType").value = state.currentCalendarType;
    }
    
    // Ocultar botón eliminar
    document.getElementById("deleteAppointment").style.display = "none";
    
    // Mostrar modal
    document.getElementById("appointmentModal").style.display = "block";
}

// Función para manejar clic en evento existente
function handleEventClick(event) {
    document.getElementById("modalTitle").innerHTML = "<i class=\"bi bi-calendar-check\"></i> Editar Cita";
    document.getElementById("title").value = event.title;
    document.getElementById("description").value = event.extendedProps.description || "";
    
    // Verificar si es un evento de todo el día
    const isAllDay = event.allDay;
    document.getElementById("allDayEvent").checked = isAllDay;
    
    // Formatear fechas para el input datetime-local
    const startDate = new Date(event.start);
    const endDate = event.end ? new Date(event.end) : new Date(startDate.getTime() + (isAllDay ? 86400000 : 3600000)); // 1 día después para todo el día, 1 hora para normal
    
    document.getElementById("startTime").value = formatDateForInput(startDate);
    document.getElementById("endTime").value = formatDateForInput(endDate);
    document.getElementById("calendarType").value = event.extendedProps.calendarType;
    
    // Mostrar botón eliminar
    document.getElementById("deleteAppointment").style.display = "inline-block";
    document.getElementById("deleteAppointment").dataset.id = event.id;
    
    // Actualizar estado
    state.isEditMode = true;
    state.currentAppointmentId = event.id;
    
    // Mostrar modal
    document.getElementById("appointmentModal").style.display = "block";
}

// Función para manejar el arrastre de eventos (cambio de fecha/hora)
function handleEventDrop(info) {
    const event = info.event;
    const eventId = event.id;
    const newStart = formatDateTimeForMySQL(event.start);
    const newEnd = formatDateTimeForMySQL(event.end || new Date(event.start.getTime() + 60 * 60 * 1000));
    
    // Confirmar el cambio
    if (!confirm("¿Está seguro de cambiar esta cita a " + formatDateForDisplay(event.start) + "?")) {
        info.revert();
        return;
    }
    
    // Mostrar notificación de carga
    displaySuccessMessage("Actualizando cita...");
    
    // Actualizar el evento en la base de datos usando fetch en lugar de jQuery.ajax
    const formData = new FormData();
    formData.append('action', 'update_date');
    formData.append('appointmentId', eventId);
    formData.append('start', newStart);
    formData.append('end', newEnd);
    
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
    const newStart = formatDateTimeForMySQL(event.start);
    const newEnd = formatDateTimeForMySQL(event.end);
    
    // Confirmar el cambio
    if (!confirm("¿Está seguro de cambiar la duración de esta cita?")) {
        info.revert();
        return;
    }
    
    // Mostrar notificación de carga
    displaySuccessMessage("Actualizando duración...");
    
    // Actualizar el evento en la base de datos usando fetch en lugar de jQuery.ajax
    const formData = new FormData();
    formData.append('action', 'update_date');
    formData.append('appointmentId', eventId);
    formData.append('start', newStart);
    formData.append('end', newEnd);
    
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