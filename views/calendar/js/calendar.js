document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            week: 'Semana',
            day: 'Día'
        },
        events: function(info, successCallback, failureCallback) {
            fetch('get_appointments.php')
                .then(response => response.json())
                .then(data => {
                    var events = data.map(function(appointment) {
                        return {
                            id: appointment.id,
                            title: appointment.title,
                            start: appointment.start_time,
                            end: appointment.end_time,
                            description: appointment.description,
                            calendar_type: appointment.calendar_type,
                            backgroundColor: appointment.user_color || '#0d6efd',
                            borderColor: appointment.user_color || '#0d6efd',
                            extendedProps: {
                                user_name: appointment.user_name || 'Sin asignar',
                                user_id: appointment.user_id
                            }
                        };
                    });
                    successCallback(events);
                })
                .catch(error => {
                    console.error('Error al cargar eventos:', error);
                    failureCallback(error);
                });
        },
        eventClick: function(info) {
            var event = info.event;
            var modal = new bootstrap.Modal(document.getElementById('appointmentModal'));
            var form = document.getElementById('appointmentForm');
            
            // Llenar el formulario con los datos del evento
            form.querySelector('[name="id"]').value = event.id;
            form.querySelector('[name="title"]').value = event.title;
            form.querySelector('[name="description"]').value = event.extendedProps.description || '';
            form.querySelector('[name="calendar_type"]').value = event.extendedProps.calendar_type || 'general';
            form.querySelector('[name="start_time"]').value = event.start.toISOString().slice(0, 16);
            form.querySelector('[name="end_time"]').value = event.end.toISOString().slice(0, 16);
            form.querySelector('[name="user_id"]').value = event.extendedProps.user_id || '';
            
            // Actualizar el color de la vista previa
            updateColorPreview();
            
            // Mostrar el modal
            modal.show();
            
            // Actualizar el título del modal
            document.getElementById('appointmentModalLabel').textContent = 'Editar Cita';
        },
        dateClick: function(info) {
            var modal = new bootstrap.Modal(document.getElementById('appointmentModal'));
            var form = document.getElementById('appointmentForm');
            
            // Limpiar el formulario
            form.reset();
            form.querySelector('[name="id"]').value = '';
            
            // Establecer la fecha seleccionada
            form.querySelector('[name="start_time"]').value = info.dateStr + 'T09:00';
            form.querySelector('[name="end_time"]').value = info.dateStr + 'T10:00';
            
            // Actualizar el color de la vista previa
            updateColorPreview();
            
            // Mostrar el modal
            modal.show();
            
            // Actualizar el título del modal
            document.getElementById('appointmentModalLabel').textContent = 'Nueva Cita';
        }
    });
    
    calendar.render();
    
    // Función para actualizar la vista previa del color
    function updateColorPreview() {
        const userSelect = document.getElementById('user_id');
        const selectedOption = userSelect.options[userSelect.selectedIndex];
        const color = selectedOption.dataset.color || '#0d6efd';
        const colorCircle = document.querySelector('#colorPreview .color-circle');
        const colorCode = document.querySelector('#colorPreview .color-code');
        
        colorCircle.style.backgroundColor = color;
        colorCode.textContent = color;
    }
    
    // Manejar el cambio de usuario
    document.getElementById('user_id').addEventListener('change', updateColorPreview);
    
    // Manejar el envío del formulario
    document.getElementById('appointmentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        var action = formData.get('id') ? 'update' : 'create';
        formData.append('action', action);
        
        fetch('process_appointment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Cerrar el modal
                var modal = bootstrap.Modal.getInstance(document.getElementById('appointmentModal'));
                modal.hide();
                
                // Recargar el calendario
                calendar.refetchEvents();
                
                // Mostrar mensaje de éxito
                alert(data.message);
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar la solicitud');
        });
    });
}); 