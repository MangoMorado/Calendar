/**
 * Funciones de utilidad para el calendario
 * Formateo de fechas, mensajes, actualización de eventos
 */

// Función para formatear fecha para input datetime-local
function formatDateForInput(date) {
    return date.toISOString().slice(0, 16);
}

// Función para formatear fecha y hora para mostrar
function formatDateTime(date) {
    const options = { 
        day: "2-digit", 
        month: "2-digit", 
        hour: "2-digit", 
        minute: "2-digit"
    };
    return date.toLocaleDateString("es-ES", options);
}

// Función para formatear solo la fecha
function formatDate(date) {
    const options = { 
        day: "2-digit", 
        month: "2-digit",
        year: "numeric"
    };
    return date.toLocaleDateString("es-ES", options);
}

// Función para formatear fecha para mostrar al usuario
function formatDateForDisplay(date) {
    const options = { 
        day: "2-digit", 
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit"
    };
    return date.toLocaleDateString("es-ES", options);
}

// Función para formatear fecha y hora para MySQL
function formatDateTimeForMySQL(date) {
    return date.toISOString().slice(0, 19).replace('T', ' ');
}

// Función para formatear solo la hora
function formatTime(date) {
    const options = { 
        hour: "2-digit", 
        minute: "2-digit",
        hour12: true
    };
    return date.toLocaleTimeString("es-ES", options);
}

// Función para mostrar mensajes de éxito
function displaySuccessMessage(message) {
    // Crear la notificación con el estilo original
    const notification = document.createElement('div');
    notification.className = 'notification success';
    
    const icon = document.createElement('div');
    icon.className = 'notification-icon';
    icon.innerHTML = '<i class="bi bi-check-circle-fill"></i>';
    
    const content = document.createElement('div');
    content.className = 'notification-content';
    content.innerHTML = `<p>${message}</p>`;
    
    const closeBtn = document.createElement('button');
    closeBtn.className = 'notification-close';
    closeBtn.innerHTML = '<i class="bi bi-x"></i>';
    closeBtn.onclick = function() {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    };
    
    notification.appendChild(icon);
    notification.appendChild(content);
    notification.appendChild(closeBtn);
    
    // Añadir al DOM
    document.body.appendChild(notification);
    
    // Mostrar con animación
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Auto cerrar después de 3 segundos
    setTimeout(function() {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Función para mostrar mensajes de error
function displayErrorMessage(message) {
    // Crear la notificación con el estilo original
    const notification = document.createElement('div');
    notification.className = 'notification error';
    
    const icon = document.createElement('div');
    icon.className = 'notification-icon';
    icon.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i>';
    
    const content = document.createElement('div');
    content.className = 'notification-content';
    content.innerHTML = `<p>${message}</p>`;
    
    const closeBtn = document.createElement('button');
    closeBtn.className = 'notification-close';
    closeBtn.innerHTML = '<i class="bi bi-x"></i>';
    closeBtn.onclick = function() {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    };
    
    notification.appendChild(icon);
    notification.appendChild(content);
    notification.appendChild(closeBtn);
    
    // Añadir al DOM
    document.body.appendChild(notification);
    
    // Mostrar con animación
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Auto cerrar después de 5 segundos
    setTimeout(function() {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

// Función para refrescar los eventos del calendario
function refreshCalendarEvents() {
    fetch("api/appointments.php?action=get_events")
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.text();
        })
        .then(text => {
            let events;
            try {
                // Intentar parsear como JSON si es una cadena
                if (typeof text === 'string') {
                    events = JSON.parse(text);
                } else {
                    // Si ya es un objeto, usarlo directamente
                    events = text;
                }
                
                // Actualizar el calendario
                calendar.removeAllEvents();
                calendar.addEventSource(events);
                
                // Actualizar también las próximas citas
                displayUpcomingAppointments();
            } catch (e) {
                console.error("Error al parsear eventos:", e);
                console.error("Texto recibido:", text);
                displayErrorMessage("Error al cargar los eventos del calendario");
            }
        })
        .catch(error => {
            console.error("Error al obtener eventos:", error);
            displayErrorMessage("Error al conectar con el servidor");
        });
} 