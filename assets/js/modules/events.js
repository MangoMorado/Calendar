/**
 * Módulo de Eventos
 * Maneja los controladores de eventos para la interfaz
 */
import { openModal, closeModal, showNotification } from './ui.js';

/**
 * Inicializa los event listeners
 */
export function initEventListeners(elements, config, state, calendar) {
    const { 
        calendarTypeSelector, 
        closeModalBtn,
        createAppointmentBtn, 
        deleteAppointmentBtn, 
        appointmentForm,
        appointmentModal
    } = elements;
    
    // Establecer el calendario en el ámbito global para acceso desde otros módulos
    window.calendar = calendar;
    
    // Selector de tipo de calendario
    if (calendarTypeSelector) {
        calendarTypeSelector.addEventListener('change', function() {
            const selectedCalendarType = this.value;
            window.location.href = `index.php?calendar=${selectedCalendarType}`;
        });
    }
    
    // Crear nueva cita
    if (createAppointmentBtn) {
        createAppointmentBtn.addEventListener('click', function() {
            handleCreateAppointmentClick(this, elements, config, state);
        });
    }
    
    // Cerrar modal con el botón X
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function() {
            closeModal(appointmentModal);
        });
    }
    
    // Cerrar modal al hacer clic fuera del contenido
    window.addEventListener('click', function(e) {
        if (e.target === appointmentModal) {
            closeModal(appointmentModal);
        }
    });
    
    // Manejar envío del formulario
    if (appointmentForm) {
        appointmentForm.addEventListener('submit', function(e) {
            handleFormSubmit(e, this, state, appointmentModal);
        });
    }
    
    // Eliminar cita
    if (deleteAppointmentBtn) {
        deleteAppointmentBtn.addEventListener('click', function() {
            handleDeleteAppointment(this, state, appointmentModal);
        });
    }
    
    // Teclas de acceso rápido
    document.addEventListener('keydown', function(e) {
        // Escape para cerrar el modal
        if (e.key === 'Escape' && appointmentModal.style.display === 'block') {
            closeModal(appointmentModal);
        }
    });
}

/**
 * Maneja el clic en el botón de crear cita
 */
function handleCreateAppointmentClick(button, elements, config, state) {
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-calendar-plus"></i> Crear Cita';
    document.getElementById('deleteAppointment').style.display = 'none';
    
    // Actualizar estado
    state.isEditMode = false;
    state.currentAppointmentId = null;
    window.isEditMode = false;
    window.currentAppointmentId = null;
    
    // Resetear formulario
    elements.appointmentForm.reset();
    
    // Establecer fecha/hora predeterminadas
    const now = new Date();
    now.setMinutes(Math.ceil(now.getMinutes() / 30) * 30, 0, 0); // Redondear a la media hora
    
    const later = new Date(now);
    later.setHours(later.getHours() + 1);
    
    document.getElementById('startTime').value = now.toISOString().substring(0, 16);
    document.getElementById('endTime').value = later.toISOString().substring(0, 16);
    
    // Configurar el tipo de calendario según la página actual
    const calendarTypeSelect = document.getElementById('calendarType');
    if (calendarTypeSelect) {
        // Si el botón tiene un atributo data-calendar-type, usarlo
        const buttonCalendarType = button.getAttribute('data-calendar-type');
        if (buttonCalendarType) {
            // Para páginas con tipo fijo (estetico.php, veterinario.php)
            if (calendarTypeSelect.tagName === 'INPUT' && calendarTypeSelect.type === 'hidden') {
                calendarTypeSelect.value = buttonCalendarType;
            } else {
                // Para el caso del dropdown
                [...calendarTypeSelect.options].forEach(option => {
                    if (option.value === buttonCalendarType) {
                        option.selected = true;
                    }
                });
            }
        } else if (config.currentCalendarType && config.currentCalendarType !== 'general') {
            // Para la vista general, preseleccionar según el último tipo usado
            [...calendarTypeSelect.options].forEach(option => {
                if (option.value === config.currentCalendarType) {
                    option.selected = true;
                }
            });
        }
    }
    
    // Abrir el modal
    openModal(elements.appointmentModal);
}

/**
 * Maneja el envío del formulario
 */
function handleFormSubmit(e, form, state, modal) {
    e.preventDefault();
    
    const formData = new FormData(form);
    
    if (state.isEditMode) {
        formData.append('id', state.currentAppointmentId);
        formData.append('action', 'update');
    } else {
        formData.append('action', 'create');
    }
    
    // Validar duración
    const startTime = new Date(formData.get('start_time'));
    const endTime = new Date(formData.get('end_time'));
    
    if (endTime <= startTime) {
        showNotification('La hora de fin debe ser posterior a la hora de inicio', 'error');
        return;
    }
    
    // Mostrar indicador de carga
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Procesando...';
    
    // Enviar datos con AJAX
    fetch('process_appointment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            
            // Cerrar el modal
            closeModal(modal);
            
            // Recargar la página después de un breve retraso
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification(data.message || 'Ha ocurrido un error.', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

/**
 * Maneja la eliminación de citas
 */
function handleDeleteAppointment(button, state, modal) {
    if (confirm('¿Estás seguro de que deseas eliminar esta cita?')) {
        const formData = new FormData();
        formData.append('id', state.currentAppointmentId);
        formData.append('action', 'delete');
        
        // Mostrar indicador de carga
        button.disabled = true;
        button.innerHTML = '<i class="bi bi-hourglass-split"></i> Eliminando...';
        
        fetch('process_appointment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                
                // Cerrar el modal
                closeModal(modal);
                
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showNotification(data.message || 'Ha ocurrido un error.', 'error');
                button.disabled = false;
                button.innerHTML = '<i class="bi bi-trash"></i> Eliminar';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error de conexión', 'error');
            button.disabled = false;
            button.innerHTML = '<i class="bi bi-trash"></i> Eliminar';
        });
    }
} 