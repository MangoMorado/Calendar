document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const appointmentModal = document.getElementById('appointmentModal');
    const appointmentForm = document.getElementById('appointmentForm');
    const closeModalBtn = document.querySelector('.close');
    const createAppointmentBtn = document.getElementById('createAppointment');
    const deleteAppointmentBtn = document.getElementById('deleteAppointment');
    const weekPrevBtn = document.getElementById('weekPrev');
    const weekNextBtn = document.getElementById('weekNext');
    const weekCurrentBtn = document.getElementById('weekCurrent');
    
    // Variables para el manejo de citas
    let currentAppointmentId = null;
    let isEditMode = false;
    
    // Abrir modal para crear una nueva cita
    if (createAppointmentBtn) {
        createAppointmentBtn.addEventListener('click', function() {
            openModal();
        });
    }
    
    // Evento para slots de tiempo en el calendario
    document.querySelectorAll('.appointment-slot').forEach(slot => {
        slot.addEventListener('click', function(e) {
            // Solo abrir el modal si se hizo clic directamente en el slot y no en una cita
            if (e.target.classList.contains('appointment-slot')) {
                const date = this.dataset.date;
                const time = this.dataset.time;
                
                // Establecer fecha y hora inicial en el formulario
                document.getElementById('startTime').value = `${date}T${time}:00`;
                
                // Calcular hora de fin predeterminada (1 hora después)
                const endDateTime = new Date(`${date}T${time}:00`);
                endDateTime.setHours(endDateTime.getHours() + 1);
                
                const endTimeStr = endDateTime.toISOString().slice(0, 16);
                document.getElementById('endTime').value = endTimeStr;
                
                openModal();
            }
        });
    });
    
    // Evento para citas existentes
    document.querySelectorAll('.appointment').forEach(appointment => {
        appointment.addEventListener('click', function(e) {
            e.stopPropagation(); // Evitar que se propague al slot
            
            const id = this.dataset.id;
            isEditMode = true;
            currentAppointmentId = id;
            
            // Cargar detalles de la cita con AJAX
            fetch(`get_appointment.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('title').value = data.title;
                    document.getElementById('description').value = data.description;
                    document.getElementById('startTime').value = data.start_time.replace(' ', 'T');
                    document.getElementById('endTime').value = data.end_time.replace(' ', 'T');
                    
                    // Mostrar botón de eliminar
                    if (deleteAppointmentBtn) {
                        deleteAppointmentBtn.style.display = 'inline-block';
                    }
                    
                    openModal();
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });
    });
    
    // Cerrar modal
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closeModal);
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
            
            if (isEditMode) {
                formData.append('id', currentAppointmentId);
                formData.append('action', 'update');
            } else {
                formData.append('action', 'create');
            }
            
            // Enviar datos con AJAX
            fetch('process_appointment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Recargar la página para mostrar los cambios
                    window.location.reload();
                } else {
                    alert(data.message || 'Ha ocurrido un error.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    }
    
    // Manejar eliminación de cita
    if (deleteAppointmentBtn) {
        deleteAppointmentBtn.addEventListener('click', function() {
            if (confirm('¿Estás seguro de que deseas eliminar esta cita?')) {
                const formData = new FormData();
                formData.append('id', currentAppointmentId);
                formData.append('action', 'delete');
                
                // Enviar solicitud de eliminación
                fetch('process_appointment.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Ha ocurrido un error al eliminar la cita.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        });
    }
    
    // Navegación del calendario
    if (weekPrevBtn) {
        weekPrevBtn.addEventListener('click', function() {
            const currentWeek = this.dataset.week;
            window.location.href = `index.php?week=${currentWeek}&direction=prev`;
        });
    }
    
    if (weekNextBtn) {
        weekNextBtn.addEventListener('click', function() {
            const currentWeek = this.dataset.week;
            window.location.href = `index.php?week=${currentWeek}&direction=next`;
        });
    }
    
    if (weekCurrentBtn) {
        weekCurrentBtn.addEventListener('click', function() {
            window.location.href = 'index.php';
        });
    }
    
    // Función para abrir el modal
    function openModal() {
        if (appointmentModal) {
            appointmentModal.style.display = 'block';
        }
    }
    
    // Función para cerrar el modal
    function closeModal() {
        if (appointmentModal) {
            appointmentModal.style.display = 'none';
            resetForm();
        }
    }
    
    // Función para resetear el formulario
    function resetForm() {
        if (appointmentForm) {
            appointmentForm.reset();
        }
        
        isEditMode = false;
        currentAppointmentId = null;
        
        if (deleteAppointmentBtn) {
            deleteAppointmentBtn.style.display = 'none';
        }
    }
}); 