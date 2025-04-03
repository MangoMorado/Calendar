/**
 * Módulo de Utilidades
 * Funciones de formateo de fechas, notificaciones y otras funciones auxiliares
 */

/**
 * Formatea una fecha para input datetime-local
 * @param {Date} date - Fecha a formatear
 * @returns {string} - Fecha formateada (YYYY-MM-DDThh:mm)
 */
export function formatDateForInput(date) {
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
        
        return formattedDate;
    } catch (error) {
        console.error("Error al formatear fecha para input:", error);
        // En caso de error, devolver ahora mismo
        const now = new Date();
        return now.toISOString().slice(0, 16);
    }
}

/**
 * Formatea fecha y hora para mostrar al usuario
 * @param {Date} date - Fecha a formatear
 * @returns {string} - Fecha formateada para mostrar
 */
export function formatDateTime(date) {
    const options = { 
        day: "2-digit", 
        month: "2-digit", 
        hour: "2-digit", 
        minute: "2-digit"
    };
    return date.toLocaleDateString("es-ES", options);
}

/**
 * Formatea solo la fecha
 * @param {Date} date - Fecha a formatear
 * @returns {string} - Fecha formateada (DD/MM/YYYY)
 */
export function formatDate(date) {
    const options = { 
        day: "2-digit", 
        month: "2-digit",
        year: "numeric"
    };
    return date.toLocaleDateString("es-ES", options);
}

/**
 * Formatea fecha y hora para mostrar al usuario con formato completo
 * @param {Date} date - Fecha a formatear
 * @returns {string} - Fecha formateada (DD/MM/YYYY HH:MM)
 */
export function formatDateForDisplay(date) {
    const options = { 
        day: "2-digit", 
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit"
    };
    return date.toLocaleDateString("es-ES", options);
}

/**
 * Formatea fecha y hora para MySQL
 * @param {Date} date - Fecha a formatear
 * @returns {string} - Fecha formateada (YYYY-MM-DD HH:MM:SS)
 */
export function formatDateTimeForMySQL(date) {
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
        
        return formattedDate;
    } catch (error) {
        console.error("Error al formatear fecha para MySQL:", error);
        // En caso de error, devolver ahora formateado
        const now = new Date();
        return now.toISOString().slice(0, 19).replace('T', ' ');
    }
}

/**
 * Formatea solo la hora
 * @param {Date} date - Fecha a formatear
 * @returns {string} - Hora formateada (HH:MM)
 */
export function formatTime(date) {
    const options = { 
        hour: "2-digit", 
        minute: "2-digit",
        hour12: true
    };
    return date.toLocaleTimeString("es-ES", options);
}

/**
 * Formatea fecha para servidor
 * @param {Date} date - Fecha a formatear
 * @returns {string} - Fecha formateada (YYYY-MM-DD HH:MM:SS)
 */
export function formatDateTimeForServer(date) {
    // Alias para mantener compatibilidad
    return formatDateTimeForMySQL(date);
}

/**
 * Actualiza los eventos del calendario mediante una petición AJAX
 * @param {Object} calendar - Instancia del calendario FullCalendar
 * @param {function} onSuccess - Función a ejecutar al completar (opcional)
 */
export function refreshCalendarEvents(calendar, onSuccess) {
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
                
                // Verificar que los eventos tienen la información correcta
                if (Array.isArray(eventData)) {
                    // Actualizar el calendario
                    calendar.removeAllEvents();
                    calendar.addEventSource(eventData);
                    
                    // Ejecutar función de éxito si se proporcionó
                    if (typeof onSuccess === 'function') {
                        onSuccess(eventData);
                    }
                } else {
                    throw new Error('Los datos de eventos no son un array válido');
                }
            } catch (e) {
                console.error("Error al parsear eventos:", e);
                console.error("Texto recibido:", text);
                throw e;
            }
        });
} 