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
        
        // Cargar usuarios en el select
        loadUsersIntoSelect();
        
        // Mostrar modal
        document.getElementById("appointmentModal").style.display = "block";
    });
}

// Cargar la lista de usuarios en el select
function loadUsersIntoSelect() {
    const userSelect = document.getElementById("user_id");
    
    // Guardar el valor seleccionado actual si existe
    const currentSelectedValue = userSelect.value;
    console.log("ANTES: Valor actual del select:", currentSelectedValue);
    
    // Obtener el texto actual seleccionado para preservarlo
    let currentSelectedText = "";
    if (currentSelectedValue && userSelect.selectedIndex >= 0) {
        currentSelectedText = userSelect.options[userSelect.selectedIndex].text;
    }
    console.log("ANTES: Texto actual seleccionado:", currentSelectedText);
    
    // Guardar los colores de los usuarios para usarlos más tarde
    const userColors = {};
    if (typeof calendarUsers !== 'undefined' && calendarUsers.length > 0) {
        calendarUsers.forEach(user => {
            userColors[user.id] = user.color;
        });
    }
    
    // Limpiar opciones existentes (excepto la primera - opción vacía)
    while (userSelect.options.length > 1) {
        userSelect.remove(1);
    }
    
    // Añadir usuarios desde la variable global calendarUsers
    if (typeof calendarUsers !== 'undefined' && calendarUsers.length > 0) {
        console.log("Usuarios disponibles:", calendarUsers);
        
        // Buscar si existe el usuario actual en la lista (comparando como strings para evitar problemas de tipo)
        let userExists = false;
        if (currentSelectedValue) {
            userExists = calendarUsers.some(user => String(user.id) === String(currentSelectedValue));
        }
        console.log("¿El usuario seleccionado existe en la lista?", userExists);
        
        // Añadir todas las opciones de usuario
        calendarUsers.forEach(user => {
            const option = document.createElement("option");
            option.value = user.id;
            option.text = user.name;
            option.dataset.color = user.color;
            
            // Marcar como seleccionado si coincide con el usuario actual (comparando como strings)
            if (String(user.id) === String(currentSelectedValue)) {
                option.selected = true;
                console.log("Marcando como seleccionado el usuario:", user.name);
            }
            
            userSelect.appendChild(option);
        });
        
        // Intentar restaurar la selección si el usuario existía
        if (userExists) {
            userSelect.value = currentSelectedValue;
            console.log("Restaurando la selección de usuario a:", currentSelectedValue);
        }
    } else {
        console.log("No hay usuarios disponibles para cargar");
    }
    
    // Verificar el valor final después de cargar
    console.log("DESPUÉS: Valor final del select:", userSelect.value);
    
    // Verificar si se restauró correctamente
    if (currentSelectedValue && userSelect.value != currentSelectedValue) {
        console.log("ADVERTENCIA: No se pudo restaurar el valor seleccionado.");
        console.log("Se esperaba:", currentSelectedValue, "Actual:", userSelect.value);
        
        // Último intento: forzar el valor directamente
        try {
            // Buscar la opción correcta
            for (let i = 0; i < userSelect.options.length; i++) {
                if (String(userSelect.options[i].value) === String(currentSelectedValue)) {
                    userSelect.selectedIndex = i;
                    console.log("Forzando selección del índice:", i);
                    break;
                }
            }
        } catch (e) {
            console.error("Error al intentar forzar la selección:", e);
        }
    }
    
    // Actualizar la vista previa del color
    updateColorPreview();
}

// Actualizar la vista previa del color según el usuario seleccionado
function updateColorPreview() {
    const userSelect = document.getElementById("user_id");
    const colorPreview = document.getElementById("colorPreview");
    const colorCircle = colorPreview.querySelector(".color-circle");
    const colorCode = colorPreview.querySelector(".color-code");
    
    console.log("Actualizando color preview para usuario:", userSelect.value);
    
    // Comprobar si hay una selección válida (índice > 0 significa que no es la opción vacía)
    if (userSelect.selectedIndex > 0) {
        try {
            const selectedOption = userSelect.options[userSelect.selectedIndex];
            const color = selectedOption.dataset.color || "#0d6efd";
            
            console.log("Color seleccionado:", color, "para usuario:", selectedOption.text);
            
            colorCircle.style.backgroundColor = color;
            colorCode.textContent = color;
            colorPreview.style.display = "flex";
        } catch (e) {
            console.error("Error al actualizar el color:", e);
            // Usar color por defecto en caso de error
            colorCircle.style.backgroundColor = "#0d6efd";
            colorCode.textContent = "Color predeterminado";
            colorPreview.style.display = "flex";
        }
    } else {
        // Si no hay usuario seleccionado o es la opción vacía
        colorCircle.style.backgroundColor = "#cccccc";
        colorCode.textContent = "Sin usuario asignado";
        colorPreview.style.display = "flex";
    }
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

// Configurar evento para cambio de usuario en el select
function setupUserSelectEvent() {
    document.getElementById("user_id").addEventListener("change", updateColorPreview);
}

// Configurar evento para envío del formulario
function setupFormSubmitEvent() {
    document.getElementById("appointmentForm").addEventListener("submit", function(e) {
        e.preventDefault();
        
        // Mostrar notificación de carga
        const actionType = state.isEditMode ? "Actualizando" : "Creando";
        displaySuccessMessage(`${actionType} cita...`);
        
        // Verificar el valor del usuario antes de enviar
        const userSelect = document.getElementById("user_id");
        const userId = userSelect.value;
        const userName = userId && userSelect.selectedIndex > 0 ? 
                        userSelect.options[userSelect.selectedIndex].text : "Sin asignar";
        
        console.log("Enviando formulario con usuario:", userId, userName);
        
        // Recoger datos del formulario
        const formData = new FormData(this);
        
        // Asegurarse de que el user_id sea un número o NULL
        if (userId === "" || userId === "0") {
            formData.set("user_id", ""); // Para que sea NULL en la base de datos
            console.log("Usuario establecido como NULL (vacío)");
        } else {
            // Asegurarse de que sea un número entero
            formData.set("user_id", parseInt(userId, 10));
            console.log("Usuario establecido como número:", parseInt(userId, 10));
        }
        
        // Añadir ID si estamos en modo edición
        if (state.isEditMode) {
            formData.append("id", state.currentAppointmentId);
            formData.append("action", "update");
        } else {
            formData.append("action", "create");
        }
        
        // Mostrar los datos que se van a enviar
        console.log("DATOS DEL FORMULARIO A ENVIAR:");
        for (const [key, value] of formData.entries()) {
            console.log(`${key}: ${value} (tipo: ${typeof value})`);
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
    setupUserSelectEvent();
} 