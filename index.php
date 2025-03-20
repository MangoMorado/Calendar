<?php
// Incluir archivos de configuración y funciones
require_once 'config/database.php';
require_once 'includes/functions.php';

// Obtener los parámetros de navegación
$requestedWeek = isset($_GET['week']) ? $_GET['week'] : null;
$direction = isset($_GET['direction']) ? $_GET['direction'] : null;

// Determinar las fechas de la semana a mostrar
if ($requestedWeek && $direction) {
    // Formato de la fecha de la semana es YYYY-MM-DD (primer día de la semana)
    $weekStart = new DateTime($requestedWeek);
    
    if ($direction === 'prev') {
        $weekStart->modify('-7 days');
    } elseif ($direction === 'next') {
        $weekStart->modify('+7 days');
    }
    
    $weekEnd = clone $weekStart;
    $weekEnd->modify('+6 days');
} else {
    // Usar la semana actual
    $weekDates = getCurrentWeekDates();
    $weekStart = new DateTime($weekDates['start']);
    $weekEnd = new DateTime($weekDates['end']);
}

// Formatear fechas para uso en consultas y visualización
$weekStartStr = $weekStart->format('Y-m-d');
$weekEndStr = $weekEnd->format('Y-m-d');

// Obtener citas para esta semana
$appointments = getAppointments($weekStartStr . ' 00:00:00', $weekEndStr . ' 23:59:59');

// Preparar los datos para FullCalendar (formato JSON)
$events = [];
foreach ($appointments as $appointment) {
    $events[] = [
        'id' => $appointment['id'],
        'title' => $appointment['title'],
        'start' => $appointment['start_time'],
        'end' => $appointment['end_time'],
        'description' => $appointment['description']
    ];
}
$eventsJson = json_encode($events);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda de Citas</title>
    <!-- Estilos CSS básicos -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- FullCalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css' rel='stylesheet' />
</head>
<body>
    <header>
        <div class="container">
            <h1>Agenda de Citas</h1>
        </div>
    </header>
    
    <main class="container">
        <div class="calendar-header">
            <div class="calendar-title">
                <h2>Calendario de Citas</h2>
            </div>
            <div class="calendar-nav">
                <button id="createAppointment" class="btn btn-success">Nueva Cita</button>
            </div>
        </div>
        
        <!-- Contenedor para FullCalendar -->
        <div id="calendar"></div>
    </main>
    
    <!-- Modal para crear/editar citas -->
    <div id="appointmentModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modalTitle">Crear Cita</h2>
            
            <form id="appointmentForm" method="post">
                <div class="form-group">
                    <label for="title">Título:</label>
                    <input type="text" id="title" name="title" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Descripción:</label>
                    <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="startTime">Hora de Inicio:</label>
                    <input type="datetime-local" id="startTime" name="start_time" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="endTime">Hora de Fin:</label>
                    <input type="datetime-local" id="endTime" name="end_time" class="form-control" required>
                </div>
                
                <div class="form-actions">
                    <button type="button" id="deleteAppointment" class="btn btn-danger" style="display: none;">Eliminar</button>
                    <button type="submit" class="btn btn-success">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/locales-all.min.js'></script>
    <script>
        // Inicializar FullCalendar cuando el DOM esté cargado
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            
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
                events: <?php echo $eventsJson; ?>,
                // Evento al seleccionar un rango de tiempo
                select: function(info) {
                    document.getElementById('startTime').value = info.startStr.replace(/:\d+\.\d+Z$/, '');
                    document.getElementById('endTime').value = info.endStr.replace(/:\d+\.\d+Z$/, '');
                    
                    document.getElementById('modalTitle').textContent = 'Crear Cita';
                    document.getElementById('deleteAppointment').style.display = 'none';
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
                            
                            document.getElementById('modalTitle').textContent = 'Editar Cita';
                            document.getElementById('deleteAppointment').style.display = 'inline-block';
                            
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
                    document.getElementById('modalTitle').textContent = 'Crear Cita';
                    document.getElementById('deleteAppointment').style.display = 'none';
                    window.isEditMode = false;
                    window.currentAppointmentId = null;
                    appointmentForm.reset();
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
                        formData.append('id', window.currentAppointmentId);
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
            
            // Funciones para gestionar el modal
            function openModal() {
                appointmentModal.style.display = 'block';
            }
            
            function closeModal() {
                appointmentModal.style.display = 'none';
                appointmentForm.reset();
                window.isEditMode = false;
                window.currentAppointmentId = null;
            }
        });
    </script>
    
    <!-- Archivo original de la aplicación (ahora no se usa, lo mantenemos por referencia) -->
    <!-- <script src="assets/js/app.js"></script> -->
</body>
</html> 