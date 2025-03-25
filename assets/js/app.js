/**
 * Aplicación de Calendario - Mundo Animal
 * Gestión de citas para clínica veterinaria
 * Archivo principal que coordina todos los módulos
 */
import { initCalendar } from './modules/calendar.js';
import { initUpcomingAppointments } from './modules/appointments.js';
import { initEventListeners } from './modules/events.js';
import { showNotification, openModal, closeModal } from './modules/ui.js';

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
    
    // Verificar que window.calendarEvents exista
    if (!window.calendarEvents) {
        console.warn('window.calendarEvents no existe. Usando array vacío.');
        window.calendarEvents = [];
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
        appointmentForm: document.getElementById('appointmentForm')
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
        events: window.calendarEvents || [],
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
        }
    };
    
    console.log('Configuración final:', config);
    
    // Inicializar componentes usando los módulos
    try {
        // Inicializar componentes solo si existen
        if (elements.upcomingList) {
            initUpcomingAppointments(elements, config);
        }
        
        // Inicializar calendario - elemento crucial
        const calendar = initCalendar(elements, config, state);
        
        if (calendar) {
            console.log('Calendario inicializado correctamente');
            
            // Inicializar listeners de eventos
            initEventListeners(elements, config, state, calendar);
            
            // Exponer variables y funciones necesarias al ámbito global
            window.currentAppointmentId = null;
            window.isEditMode = false;
            window.showNotification = showNotification;
            window.openModal = openModal;
            window.closeModal = closeModal;
            window.calendar = calendar;
        } else {
            console.error('Error al inicializar el calendario');
        }
    } catch (error) {
        console.error('Error al inicializar la aplicación:', error);
    }
}
