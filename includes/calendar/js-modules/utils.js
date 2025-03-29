/**
 * Funciones de utilidad para el calendario
 * Formateo de fechas, mensajes, actualización de eventos
 */

// Función para formatear fecha para input datetime-local
function formatDateForInput(date) {
    try {
        // Verificar que date es una instancia válida de Date
        if (!(date instanceof Date) || isNaN(date.getTime())) {
            console.error("Fecha inválida recibida:", date);
            // Usar fecha actual como fallback
            date = new Date();
        }
        
        // Formatear manualmente para evitar problemas con toISOString
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        
        // Crear el formato YYYY-MM-DDThh:mm
        const formattedDate = `${year}-${month}-${day}T${hours}:${minutes}`;
        console.log("Fecha formateada para input:", formattedDate);
        
        return formattedDate;
    } catch (error) {
        console.error("Error al formatear fecha para input:", error);
        // En caso de error, devolver ahora mismo
        const now = new Date();
        return now.toISOString().slice(0, 16);
    }
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
    try {
        // Verificar que date es una instancia de Date válida
        if (!(date instanceof Date) || isNaN(date.getTime())) {
            console.error("Fecha inválida para formatear a MySQL:", date);
            // Usar fecha actual como fallback
            date = new Date();
        }
        
        // Formatear manualmente para evitar problemas con toISOString
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const seconds = String(date.getSeconds()).padStart(2, '0');
        
        // Crear formato YYYY-MM-DD HH:MM:SS para MySQL
        const formattedDate = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
        console.log("Fecha formateada para MySQL:", formattedDate);
        
        return formattedDate;
    } catch (error) {
        console.error("Error al formatear fecha para MySQL:", error);
        // En caso de error, devolver ahora formateado
        const now = new Date();
        return now.toISOString().slice(0, 19).replace('T', ' ');
    }
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
                
                console.log("Eventos actualizados recibidos:", events);
                
                // Extraer los eventos del objeto de respuesta
                const eventData = events.data || events;
                
                // Verificar que los eventos tienen la información del usuario correcta
                if (Array.isArray(eventData)) {
                    eventData.forEach(event => {
                        if (event.extendedProps && event.extendedProps.user_id) {
                            // Asegurarse de que el nombre del usuario no sea null o undefined
                            if (event.extendedProps.user === null || event.extendedProps.user === undefined) {
                                event.extendedProps.user = 'Sin asignar';
                            }
                            console.log("Evento con usuario:", 
                                event.id, 
                                event.title, 
                                "Usuario:", 
                                event.extendedProps.user_id, 
                                event.extendedProps.user
                            );
                        }
                    });
                    
                    // Actualizar el calendario
                    calendar.removeAllEvents();
                    calendar.addEventSource(eventData);
                    
                    // Actualizar también las próximas citas
                    displayUpcomingAppointments();
                } else {
                    throw new Error('Los datos de eventos no son un array válido');
                }
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