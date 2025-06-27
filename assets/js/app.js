/**
 * Aplicación de Calendario - Mundo Animal
 * Gestión de citas para clínica veterinaria
 * Archivo principal que coordina todos los módulos
 */
import { initCalendar } from './modules/calendar.js';
import { initUpcomingAppointments } from './modules/appointments.js';
import { 
    initEventListeners, 
    handleEventClick, 
    handleTimeSlotSelection, 
    handleEventDrop, 
    handleEventResize,
    updateUndoButton
} from './modules/events.js';
import { showNotification, openModal, closeModal } from './modules/ui.js';
import { loadUsersIntoSelect } from './modules/modal.js';

// Funciones auxiliares para verificación
function checkFullCalendarAvailability() {
    console.log('Verificando disponibilidad de FullCalendar...');
    
    // Verificar en window
    if (typeof window.FullCalendar !== 'undefined') {
        console.log('FullCalendar disponible en window.FullCalendar');
        return window.FullCalendar;
    }
    
    // Verificar como variable global
    if (typeof FullCalendar !== 'undefined') {
        console.log('FullCalendar disponible como variable global');
        window.FullCalendar = FullCalendar; // Asegurar que esté en window también
        return FullCalendar;
    }
    
    console.error('FullCalendar no está disponible en ningún ámbito');
    return null;
}

// Inicializar la aplicación cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, verificando dependencias...');
    
    // Verificar disponibilidad de FullCalendar
    const FC = checkFullCalendarAvailability();
    
    // Verificar que window.eventsJson exista (nuevo nombre)
    if (!window.eventsJson) {
        console.warn('window.eventsJson no existe. Usando array vacío.');
        window.eventsJson = [];
    }
    
    // Verificar que window.calendarSettings exista
    if (!window.calendarSettings) {
        console.warn('window.calendarSettings no existe. Usando valores por defecto.');
        window.calendarSettings = {
            slotMinTime: '00:00:00',
            slotMaxTime: '24:00:00',
            slotDuration: '00:30:00',
            timeFormat: '12h'
        };
    }
    
    // Verificar que window.calendarUsers exista
    if (!window.calendarUsers) {
        console.warn('window.calendarUsers no existe. Usando array vacío.');
        window.calendarUsers = [];
    } else {
        console.log(`Usuarios del calendario cargados: ${window.calendarUsers.length}`, window.calendarUsers);
    }
    
    // Analizar la estructura del modal
    const modalStructureCheck = () => {
        const userSelect = document.getElementById('user_id');
        if (userSelect) {
            console.log(`Select de usuarios encontrado con ${userSelect.options.length} opciones.`);
            for (let i = 0; i < Math.min(userSelect.options.length, 5); i++) {
                console.log(`- Opción ${i}: valor='${userSelect.options[i].value}', texto='${userSelect.options[i].text}'`);
            }
            if (userSelect.options.length > 5) {
                console.log(`... y ${userSelect.options.length - 5} opciones más`);
            }
        } else {
            console.warn("No se encontró el select de usuarios en el DOM.");
        }
    };
    
    // Ejecutar verificación después de que el DOM esté completamente cargado
    setTimeout(modalStructureCheck, 1000);
    
    // Inicializar solo si FullCalendar está disponible
    if (FC) {
        initializeApp();
    } else {
        console.error('No se puede inicializar la aplicación: FullCalendar no está disponible');
        showNotification('Error al cargar el calendario. Por favor, recarga la página.', 'error');
    }
});

/**
 * Inicializa la aplicación del calendario
 */
function initializeApp() {
    console.log('Inicializando aplicación de calendario...');

    // Limpiar estado de deshacer al cargar la app
    window.lastAction = null;
    window.lastEventState = null;
    updateUndoButton();

    // Elementos del DOM
    const elements = {
        calendar: document.getElementById('calendar'),
        eventTooltip: document.getElementById('eventTooltip'),
        upcomingList: document.getElementById('upcomingAppointmentsList'),
        calendarTypeSelector: document.getElementById('calendarTypeSelector'),
        appointmentModal: document.getElementById('appointmentModal'),
        closeModalBtn: document.querySelector('.close'),
        createAppointmentBtn: document.getElementById('createAppointment'),
        deleteAppointmentBtn: document.getElementById('deleteAppointment'),
        appointmentForm: document.getElementById('appointmentForm'),
        undoButton: document.getElementById('undoButton')
    };
    
    // Verificar elementos esenciales
    if (!elements.calendar) {
        console.error('Error: Elemento de calendario no encontrado (#calendar)');
        return;
    }

    if (!elements.appointmentModal) {
        console.error('Error: Modal de cita no encontrado (#appointmentModal)');
    }
    
    // Estado global
    const state = {
        currentAppointmentId: null,
        isEditMode: false
    };
    
    // Obtener configuración del objeto global
    console.log('Configuración del calendario:', window.calendarSettings);
    
    // Configuración
    const config = {
        events: window.eventsJson || [],
        currentCalendarType: window.currentCalendarType || 'general',
        settings: window.calendarSettings || {
            slotMinTime: '00:00:00',
            slotMaxTime: '24:00:00',
            slotDuration: '00:30:00',
            timeFormat: '12h'
        },
        calendarColors: {
            'estetico': '#8E44AD', // Púrpura para estético
            'veterinario': '#2E86C1', // Azul para veterinario
            'general': '#5D69F7' // Color original
        },
        calendarNames: {
            'estetico': 'Estético',
            'veterinario': 'Veterinario',
            'general': 'General'
        },
        users: window.calendarUsers || []
    };
    
    console.log('Configuración final:', config);
    
    // Exponer la función de inicialización de usuarios globalmente
    window.initializeUserSelect = initializeUserSelect;
    
    // Inicializar el select de usuarios desde el inicio para evitar problemas
    initializeUserSelect(config.users);
    
    // Inicializar componentes usando los módulos
    try {
        // IMPORTANTE: Inicializar el calendario primero
        const calendar = initCalendar(elements, config, state);
        
        if (calendar) {
            console.log('Calendario inicializado correctamente');
            
            // Exponer el calendario al ámbito global ANTES de inicializar otros componentes
            window.calendar = calendar;
            
            // Sincronizar sistema de deshacer si existe estado previo
            if (window.lastAction && window.lastEventState) {
                setUndoState(window.lastAction, window.lastEventState);
            }
            
            // Inicializar listeners de eventos
            initEventListeners(elements, config, state, calendar);
            
            // Inicializar componente de próximas citas DESPUÉS de que el calendario está listo
            if (elements.upcomingList) {
                initUpcomingAppointments(elements, config);
            }
            
            // Exponer otras variables y funciones al ámbito global
            window.currentAppointmentId = null;
            window.isEditMode = false;
            window.showNotification = showNotification;
            window.openModal = openModal;
            window.closeModal = closeModal;
        } else {
            console.error('Error al inicializar el calendario');
        }
    } catch (error) {
        console.error('Error al inicializar la aplicación:', error);
    }
}

/**
 * Inicializa el select de usuarios al cargar la aplicación
 * @param {Array} users - Lista de usuarios disponibles
 */
function initializeUserSelect(users) {
    // Primero verificar si hay usuarios disponibles
    console.log('Inicializando select de usuarios con:', users);
    
    if (!Array.isArray(users) || users.length === 0) {
        console.warn('No hay usuarios proporcionados para inicializar el select');
        // Intentar usar los usuarios globales
        if (window.calendarUsers && Array.isArray(window.calendarUsers) && window.calendarUsers.length > 0) {
            users = window.calendarUsers;
            console.log(`Usando ${users.length} usuarios del ámbito global para inicializar el select`);
        } else {
            console.error('No se pueden inicializar usuarios en el select');
            
            // Plan de emergencia: cargar usuarios usando fetch si es posible
            fetch('api/users.php')
                .then(response => response.json())
                .then(data => {
                    if (data && Array.isArray(data) && data.length > 0) {
                        console.log('Usuarios obtenidos por fetch de API:', data);
                        window.calendarUsers = data;
                        initializeUserSelect(data); // Reintentar con los datos obtenidos
                    } else {
                        console.error('No se pudieron obtener usuarios de la API');
                    }
                })
                .catch(error => {
                    console.error('Error al obtener usuarios:', error);
                });
            
            return;
        }
    }

    // Obtener el select de usuarios
    const userSelect = document.getElementById('user_id');
    if (!userSelect) {
        console.warn('Select de usuarios no encontrado en la inicialización');
        return;
    }

    console.log(`Inicializando select de usuarios con ${users.length} usuarios`);
    
    // Limpiar opciones existentes
    userSelect.innerHTML = '';
    
    // Añadir opción por defecto
    const defaultOption = document.createElement('option');
    defaultOption.value = "";
    defaultOption.text = "-- Selecciona un usuario --";
    userSelect.appendChild(defaultOption);
    
    // Añadir usuarios
    users.forEach(user => {
        const option = document.createElement('option');
        option.value = user.id;
        option.text = user.name;
        if (user.color) {
            option.dataset.color = user.color;
        }
        userSelect.appendChild(option);
        console.log(`Agregado usuario al select: ${user.name} (ID: ${user.id})`);
    });
}

// Exponer el manejador de eliminación a nivel global
// Importar primero el manejador desde el módulo events.js
import('./modules/events.js').then(module => {
    window.handleDeleteAppointment = function(state, elements) {
        // Si el módulo exports directamente handleDeleteAppointment, usar eso
        if (typeof module.handleDeleteAppointment === 'function') {
            module.handleDeleteAppointment(state, elements, window.calendar);
        } 
        // Si no, buscar en el módulo
        else {
            // Buscamos la función en el módulo (podría no estar exportada directamente)
            for (const key in module) {
                if (typeof module[key] === 'function' && key.includes('Delete')) {
                    module[key](state, elements, window.calendar);
                    return;
                }
            }
            
            // Si no encontramos la función, usar una implementación básica
            console.warn('No se encontró el manejador de eliminación, usando implementación básica');
            if (state && state.currentAppointmentId) {
                if (confirm('¿Estás seguro de que deseas eliminar esta cita?')) {
                    const formData = new FormData();
                    formData.append('id', state.currentAppointmentId);
                    formData.append('action', 'delete');
                    
                    fetch('process_appointment.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message, 'success');
                            if (elements && elements.appointmentModal) {
                                closeModal(elements.appointmentModal);
                            }
                            
                            // Mostrar notificación de recarga
                            showNotification('Cita eliminada. Recargando página...', 'success');
                            
                            // Siempre recargar la página después de eliminar
                            setTimeout(() => {
                                window.location.reload();
                            }, 800); // Pequeño retraso para que la notificación sea visible
                        } else {
                            showNotification(data.message || 'Error al eliminar la cita', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error al procesar la solicitud', 'error');
                    });
                }
            } else {
                showNotification('No se ha seleccionado ninguna cita para eliminar', 'error');
            }
        }
    };
}).catch(error => {
    console.error('Error al cargar el módulo de eventos:', error);
});

// --- LIMPIEZA GLOBAL DE MODALES ATASCADOS (backdrop) ---
function limpiarBackdrops() {
    document.querySelectorAll('.modal-backdrop').forEach(e => e.remove());
    document.body.classList.remove('modal-open');
}
document.addEventListener('hidden.bs.modal', limpiarBackdrops);
document.addEventListener('show.bs.modal', limpiarBackdrops);
window.limpiarBackdrops = limpiarBackdrops;
