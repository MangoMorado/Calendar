/**
 * Manejadores de eventos del modal
 * Funciones para controlar el modal de citas
 */

// Manejar clic en botón de nueva cita
function setupCreateAppointmentButton() {
    document.getElementById("createAppointment").addEventListener("click", function() {
        document.getElementById("modalTitle").innerHTML = "<i class=\"bi bi-calendar-plus\"></i> Crear Cita";
        document.getElementById("appointmentForm").reset();
        document.getElementById("deleteAppointment").style.display = "none";
        
        // Establecer fecha/hora predeterminadas
        const now = new Date();
        now.setMinutes(Math.ceil(now.getMinutes() / 30) * 30, 0, 0); // Redondear a la media hora
        
        const later = new Date(now);
        later.setHours(later.getHours() + 1);
        
        document.getElementById("startTime").value = formatDateForInput(now);
        document.getElementById("endTime").value = formatDateForInput(later);
        
        // Configurar el tipo de calendario según la página actual
        document.getElementById("calendarType").value = state.currentCalendarType;
        
        // Mostrar modal
        document.getElementById("appointmentModal").style.display = "block";
    });
}

// Configurar evento para cerrar modal
function setupModalCloseEvents() {
    // Cerrar modal al hacer clic en X
    document.querySelector(".close").addEventListener("click", function() {
        document.getElementById("appointmentModal").style.display = "none";
    });
    
    // Cerrar modal al hacer clic fuera del contenido
    window.addEventListener("click", function(event) {
        if (event.target == document.getElementById("appointmentModal")) {
            document.getElementById("appointmentModal").style.display = "none";
        }
    });
    
    // Cerrar modal al presionar ESC
    document.addEventListener("keydown", function(event) {
        if (event.key === "Escape" || event.key === "Esc") {
            const modal = document.getElementById("appointmentModal");
            if (modal && modal.style.display === "block") {
                modal.style.display = "none";
            }
        }
    });
}

// Configurar evento para envío del formulario
function setupFormSubmitEvent() {
    document.getElementById("appointmentForm").addEventListener("submit", function(e) {
        e.preventDefault();
        
        // Mostrar notificación de carga
        const actionType = state.isEditMode ? "Actualizando" : "Creando";
        displaySuccessMessage(`${actionType} cita...`);
        
        // Recoger datos del formulario
        const formData = new FormData(this);
        
        // Añadir ID si estamos en modo edición
        if (state.isEditMode) {
            formData.append("id", state.currentAppointmentId);
            formData.append("action", "update");
        } else {
            formData.append("action", "create");
        }
        
        // Enviar datos mediante AJAX
        fetch("api/appointments.php", {
            method: "POST",
            body: formData
        })
        .then(response => {
            // Primero verificar si la respuesta es exitosa
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            // Luego intentar parsear como JSON
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
                // Cerrar modal
                document.getElementById("appointmentModal").style.display = "none";
                
                // Mostrar mensaje de éxito
                displaySuccessMessage(state.isEditMode ? "Cita actualizada exitosamente" : "Cita creada exitosamente");
                
                // Refrescar eventos sin recargar la página
                refreshCalendarEvents();
                
                // Restablecer modo de edición
                state.isEditMode = false;
                state.currentAppointmentId = null;
            } else {
                displayErrorMessage("Error: " + data.message);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            displayErrorMessage("Error al procesar la solicitud: " + error.message);
        });
    });
}

// Configurar evento para eliminación de citas
function setupDeleteAppointmentEvent() {
    document.getElementById("deleteAppointment").addEventListener("click", function() {
        const appointmentId = this.dataset.id;
        if (confirm("¿Está seguro de que desea eliminar esta cita?")) {
            // Mostrar notificación de carga
            displaySuccessMessage("Eliminando cita...");
            
            // Enviar solicitud de eliminación
            fetch("api/appointments.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: `action=delete&id=${appointmentId}`
            })
            .then(response => {
                // Primero verificar si la respuesta es exitosa
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }
                // Luego intentar parsear como JSON
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
                    // Cerrar modal
                    document.getElementById("appointmentModal").style.display = "none";
                    
                    // Mostrar mensaje de éxito
                    displaySuccessMessage("Cita eliminada exitosamente");
                    
                    // Refrescar eventos sin recargar la página
                    refreshCalendarEvents();
                } else {
                    displayErrorMessage("Error: " + data.message);
                }
            })
            .catch(error => {
                console.error("Error:", error);
                displayErrorMessage("Error al procesar la solicitud: " + error.message);
            });
        }
    });
}

// Configurar evento para el checkbox de todo el día
function setupAllDayCheckboxEvent() {
    const allDayCheckbox = document.getElementById("allDayEvent");
    const startTimeInput = document.getElementById("startTime");
    const endTimeInput = document.getElementById("endTime");
    
    allDayCheckbox.addEventListener("change", function() {
        if (this.checked) {
            // Es un evento de todo el día
            // Extraer solo la fecha del campo start
            const startDate = new Date(startTimeInput.value);
            // Establecer la hora a las 00:00:00
            startDate.setHours(0, 0, 0, 0);
            startTimeInput.value = formatDateForInput(startDate);
            
            // Calcular el día siguiente a las 00:00:00 para el campo end
            const endDate = new Date(startDate);
            endDate.setDate(endDate.getDate() + 1);
            endTimeInput.value = formatDateForInput(endDate);
        } else {
            // Es un evento normal, dejar la hora actual pero asegurar que end sea una hora después de start
            const startDate = new Date(startTimeInput.value);
            const endDate = new Date(startDate);
            endDate.setHours(endDate.getHours() + 1);
            endTimeInput.value = formatDateForInput(endDate);
        }
    });
}

// Inicializar todos los eventos del modal
function initModalEvents() {
    setupCreateAppointmentButton();
    setupModalCloseEvents();
    setupFormSubmitEvent();
    setupDeleteAppointmentEvent();
    setupAllDayCheckboxEvent();
} 