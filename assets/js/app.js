document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const eventTooltip = document.getElementById('eventTooltip');
    const upcomingList = document.getElementById('upcomingAppointmentsList');
    const calendarTypeSelector = document.getElementById('calendarTypeSelector');
    
    // Obtener eventos del objeto global
    const events = window.calendarEvents || [];
    const currentCalendarType = window.currentCalendarType || 'general';
    
    // Colores para los diferentes tipos de calendario
    const calendarColors = {
        'estetico': '#8E44AD', // Púrpura para estético
        'veterinario': '#2E86C1', // Azul para veterinario
        'general': '#5D69F7' // Color original
    };
    
    // Nombres legibles para los tipos de calendario
    const calendarNames = {
        'estetico': 'Estético',
        'veterinario': 'Veterinario',
        'general': 'General'
    };
    
    // Mostrar próximas citas
    displayUpcomingAppointments(events);
    
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        locale: 'es',
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            week: 'Semana',
            day: 'Día',
            list: 'Lista'
        },
        slotMinTime: '08:00:00',
        slotMaxTime: '20:00:00',
        height: 'auto',
        allDaySlot: false,
        slotDuration: '00:30:00',
        nowIndicator: true,
        navLinks: true,
        selectable: true,
        selectMirror: true,
        dayMaxEvents: true,
        businessHours: {
            daysOfWeek: [1, 2, 3, 4, 5], // Lunes - Viernes
            startTime: '09:00',
            endTime: '18:00',
        },
        events: events,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        },
        // Personalizar la renderización de eventos
        eventDidMount: function(info) {
            // Aplicar clase específica al evento según el tipo de calendario
            const calendarType = info.event.extendedProps.calendarType || 'general';
            info.el.classList.add(`calendar-${calendarType}`);
        },
        // Tooltip personalizado para eventos
        eventMouseEnter: function(info) {
            const rect = info.el.getBoundingClientRect();
            const scrollTop = window.scrollY || document.documentElement.scrollTop;
            
            // Formatear fechas
            const start = new Date(info.event.start);
            const end = new Date(info.event.end);
            const formattedStart = start.toLocaleTimeString('es-ES', {hour: '2-digit', minute:'2-digit'});
            const formattedEnd = end.toLocaleTimeString('es-ES', {hour: '2-digit', minute:'2-digit'});
            const formattedDate = start.toLocaleDateString('es-ES', {weekday: 'long', day: 'numeric', month: 'long'});
            
            // Obtener el tipo de calendario para mostrar en el tooltip
            const calendarType = info.event.extendedProps.calendarType || 'general';
            const calendarName = calendarNames[calendarType] || 'General';
            
            // Contenido del tooltip
            eventTooltip.innerHTML = `
                <div class="tooltip-title">${info.event.title}</div>
                <div class="tooltip-time"><i class="bi bi-clock"></i> ${formattedStart} - ${formattedEnd}</div>
                <div class="tooltip-date"><i class="bi bi-calendar-event"></i> ${formattedDate}</div>
                <div class="tooltip-calendar"><i class="bi bi-calendar3"></i> ${calendarName}</div>
                ${info.event.extendedProps.description ? `<div class="tooltip-desc">${info.event.extendedProps.description}</div>` : ''}
            `;
            
            // Posicionar y mostrar tooltip
            eventTooltip.style.left = rect.left + window.scrollX + 'px';
            eventTooltip.style.top = rect.bottom + scrollTop + 'px';
            eventTooltip.style.display = 'block';
        },
        eventMouseLeave: function() {
            eventTooltip.style.display = 'none';
        },
        // Evento al seleccionar un rango de tiempo
        select: function(info) {
            document.getElementById('startTime').value = info.startStr.replace(/:\d+\.\d+Z$/, '');
            document.getElementById('endTime').value = info.endStr.replace(/:\d+\.\d+Z$/, '');
            
            // Mostrar el tipo de calendario apropiado en el selector
            if (currentCalendarType !== 'general') {
                const calendarTypeSelect = document.getElementById('calendarType');
                if (calendarTypeSelect) {
                    [...calendarTypeSelect.options].forEach(option => {
                        if (option.value === currentCalendarType) {
                            option.selected = true;
                        }
                    });
                }
            }
            
            document.getElementById('modalTitle').innerHTML = '<i class="bi bi-calendar-plus"></i> Crear Cita';
            document.getElementById('deleteAppointment').style.display = 'none';
            window.isEditMode = false;
            window.currentAppointmentId = null;
            openModal();
        },
        // Evento al hacer clic en una cita existente
        eventClick: function(info) {
            // Obtener los detalles de la cita con AJAX
            fetch(`get_appointment.php?id=${info.event.id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('title').value = data.title;
                    document.getElementById('description').value = data.description;
                    document.getElementById('startTime').value = data.start_time;
                    document.getElementById('endTime').value = data.end_time;
                    
                    // Seleccionar el tipo de calendario correcto
                    const calendarTypeSelect = document.getElementById('calendarType');
                    if (calendarTypeSelect) {
                        [...calendarTypeSelect.options].forEach(option => {
                            if (option.value === data.calendar_type) {
                                option.selected = true;
                            }
                        });
                    }
                    
                    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-calendar-check"></i> Editar Cita';
                    document.getElementById('deleteAppointment').style.display = 'inline-flex';
                    
                    // Configurar variables para modo de edición
                    window.currentAppointmentId = info.event.id;
                    window.isEditMode = true;
                    
                    openModal();
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
    });
    
    calendar.render();
    
    // Selector de tipo de calendario
    if (calendarTypeSelector) {
        calendarTypeSelector.addEventListener('change', function() {
            const selectedCalendarType = this.value;
            // Redirigir a la misma página con el tipo de calendario seleccionado
            window.location.href = `index.php?calendar=${selectedCalendarType}`;
        });
    }
    
    // Función para mostrar próximas citas
    function displayUpcomingAppointments(events) {
        if (!upcomingList) return;
        
        // Ordenar eventos por fecha de inicio
        const sortedEvents = [...events].sort((a, b) => new Date(a.start) - new Date(b.start));
        
        // Filtrar eventos futuros (a partir de hoy)
        const now = new Date();
        let upcomingEvents = sortedEvents.filter(event => new Date(event.start) >= now);
        
        // Si no estamos en la vista general, filtrar solo las citas del tipo seleccionado
        if (currentCalendarType !== 'general') {
            upcomingEvents = upcomingEvents.filter(event => event.calendarType === currentCalendarType);
        }
        
        // Tomar solo los primeros 5 eventos
        upcomingEvents = upcomingEvents.slice(0, 5);
        
        if (upcomingEvents.length === 0) {
            upcomingList.innerHTML = '<p class="no-events">No hay citas próximas</p>';
            return;
        }
        
        // Crear elementos para cada evento
        upcomingList.innerHTML = '';
        upcomingEvents.forEach(event => {
            const start = new Date(event.start);
            const formattedDate = start.toLocaleDateString('es-ES', {weekday: 'short', day: 'numeric', month: 'short'});
            const formattedTime = start.toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'});
            
            // Obtener el tipo de calendario
            const calendarType = event.calendarType || 'general';
            let calendarName = '';
            let calendarClass = '';
            let calendarColor = '';
            
            switch (calendarType) {
                case 'estetico':
                    calendarName = 'Estético';
                    calendarClass = 'calendar-estetico';
                    calendarColor = '#8E44AD';
                    break;
                case 'veterinario':
                    calendarName = 'Veterinario';
                    calendarClass = 'calendar-veterinario';
                    calendarColor = '#2E86C1';
                    break;
                default:
                    calendarName = 'General';
                    calendarClass = 'calendar-general';
                    calendarColor = '#5D69F7';
            }
            
            const eventEl = document.createElement('div');
            eventEl.className = `appointment-item ${calendarClass}`;
            eventEl.innerHTML = `
                <div class="appointment-date">
                    <div class="calendar-indicator" style="background-color: ${calendarColor};"></div>
                    <span class="day">${formattedDate}</span>
                    <span class="time">${formattedTime}</span>
                </div>
                <div class="appointment-details">
                    <div class="appointment-title">${event.title}</div>
                    <div class="appointment-calendar">${calendarName}</div>
                    ${event.description ? `<div class="appointment-desc">${event.description.substring(0, 60)}${event.description.length > 60 ? '...' : ''}</div>` : ''}
                </div>
            `;
            
            // Agregar evento al hacer clic
            eventEl.addEventListener('click', function() {
                // Encontrar el evento en el calendario
                const calEvent = calendar.getEventById(event.id);
                if (calEvent) {
                    calendar.gotoDate(calEvent.start);
                    setTimeout(() => {
                        calEvent.setProp('backgroundColor', '#EF4444');
                        setTimeout(() => calEvent.setProp('backgroundColor', ''), 1500);
                    }, 100);
                }
            });
            
            upcomingList.appendChild(eventEl);
        });
    }
    
    // Modal
    const appointmentModal = document.getElementById('appointmentModal');
    const closeModalBtn = document.querySelector('.close');
    const createAppointmentBtn = document.getElementById('createAppointment');
    const deleteAppointmentBtn = document.getElementById('deleteAppointment');
    const appointmentForm = document.getElementById('appointmentForm');
    
    // Variables para el manejo de citas
    window.currentAppointmentId = null;
    window.isEditMode = false;
    
    // Crear nueva cita
    if (createAppointmentBtn) {
        createAppointmentBtn.addEventListener('click', function() {
            document.getElementById('modalTitle').innerHTML = '<i class="bi bi-calendar-plus"></i> Crear Cita';
            document.getElementById('deleteAppointment').style.display = 'none';
            window.isEditMode = false;
            window.currentAppointmentId = null;
            appointmentForm.reset();
            
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
                const buttonCalendarType = this.getAttribute('data-calendar-type');
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
                } else if (currentCalendarType && currentCalendarType !== 'general') {
                    // Para la vista general, preseleccionar según el último tipo usado
                    [...calendarTypeSelect.options].forEach(option => {
                        if (option.value === currentCalendarType) {
                            option.selected = true;
                        }
                    });
                }
            }
            
            openModal();
        });
    }
    
    // Cerrar modal con el botón X
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function() {
            closeModal();
        });
    }
    
    // Cerrar modal al hacer clic fuera del contenido
    window.addEventListener('click', function(e) {
        if (e.target === appointmentModal) {
            closeModal();
        }
    });
    
    // Manejar envío del formulario
    if (appointmentForm) {
        appointmentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            if (window.isEditMode) {
                formData.append('id', window.currentAppointmentId);
                formData.append('action', 'update');
            } else {
                formData.append('action', 'create');
            }
            
            // Mostrar indicador de carga
            const submitBtn = this.querySelector('button[type="submit"]');
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
                    // Mostrar notificación
                    showNotification(data.message, 'success');
                    
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
        });
    }
    
    // Eliminar cita
    if (deleteAppointmentBtn) {
        deleteAppointmentBtn.addEventListener('click', function() {
            if (confirm('¿Estás seguro de que deseas eliminar esta cita?')) {
                const formData = new FormData();
                formData.append('id', window.currentAppointmentId);
                formData.append('action', 'delete');
                
                // Mostrar indicador de carga
                this.disabled = true;
                this.innerHTML = '<i class="bi bi-hourglass-split"></i> Eliminando...';
                
                fetch('process_appointment.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showNotification(data.message || 'Ha ocurrido un error.', 'error');
                        this.disabled = false;
                        this.innerHTML = '<i class="bi bi-trash"></i> Eliminar';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error de conexión', 'error');
                    this.disabled = false;
                    this.innerHTML = '<i class="bi bi-trash"></i> Eliminar';
                });
            }
        });
    }
    
    // Función para abrir el modal
    function openModal() {
        appointmentModal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Evitar scroll
        document.getElementById('title').focus();
    }
    
    // Función para cerrar el modal
    function closeModal() {
        appointmentModal.style.display = 'none';
        document.body.style.overflow = '';
    }
    
    // Función para mostrar notificaciones
    function showNotification(message, type) {
        // Crear elemento de notificación
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <div class="notification-icon">
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            </div>
            <div class="notification-content">
                <p>${message}</p>
            </div>
            <button class="notification-close"><i class="bi bi-x"></i></button>
        `;
        
        // Añadir a la página
        document.body.appendChild(notification);
        
        // Mostrar con animación
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Cerrar al hacer clic
        notification.querySelector('.notification-close').addEventListener('click', function() {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        });
        
        // Auto-cerrar después de 5 segundos
        setTimeout(() => {
            if (document.body.contains(notification)) {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
    }
});
