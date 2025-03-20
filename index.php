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
    <title>Agenda de Citas | MangoCal</title>
    
    <!-- Fuentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Estilos CSS básicos -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- FullCalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css' rel='stylesheet' />
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <h1>Mundo Animal</h1>
                <p class="tagline">Agenda de Citas</p>
            </div>
        </div>
    </header>
    
    <main class="container">
        <div class="calendar-header">
            <div class="calendar-title">
                <h2>Calendario de Citas</h2>
                <p class="date-range"><?php echo date_format($weekStart, 'd M') . ' - ' . date_format($weekEnd, 'd M Y'); ?></p>
            </div>
            <div class="calendar-nav">
                <button id="createAppointment" class="btn btn-success">
                    <i class="bi bi-plus-lg"></i> Nueva Cita
                </button>
            </div>
        </div>
        
        <!-- Contenedor para FullCalendar -->
        <div id="calendar-container">
            <div id="calendar"></div>
        </div>
        
        <div class="info-panel">
            <div class="upcoming-appointments">
                <h3><i class="bi bi-clock"></i> Próximas Citas</h3>
                <div class="appointment-list" id="upcomingAppointmentsList">
                    <!-- Se llenará con JavaScript -->
                </div>
            </div>
        </div>
    </main>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> MangoCal. Todos los derechos reservados.</p>
        </div>
    </footer>
    
    <!-- Modal para crear/editar citas -->
    <div id="appointmentModal" class="modal">
        <div class="modal-content">
            <span class="close"><i class="bi bi-x-lg"></i></span>
            <h2 id="modalTitle"><i class="bi bi-calendar-plus"></i> Crear Cita</h2>
            
            <form id="appointmentForm" method="post">
                <div class="form-group">
                    <label for="title"><i class="bi bi-type"></i> Título:</label>
                    <input type="text" id="title" name="title" class="form-control" required placeholder="Nombre de la cita">
                </div>
                
                <div class="form-group">
                    <label for="description"><i class="bi bi-text-paragraph"></i> Descripción:</label>
                    <textarea id="description" name="description" class="form-control" rows="3" placeholder="Detalles adicionales"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="startTime"><i class="bi bi-clock"></i> Hora de Inicio:</label>
                        <input type="datetime-local" id="startTime" name="start_time" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="endTime"><i class="bi bi-clock-history"></i> Hora de Fin:</label>
                        <input type="datetime-local" id="endTime" name="end_time" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" id="deleteAppointment" class="btn btn-danger" style="display: none;">
                        <i class="bi bi-trash"></i> Eliminar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tooltip personalizado -->
    <div id="eventTooltip" class="tooltip"></div>
    
    <!-- Scripts -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/locales-all.min.js'></script>
    <script>
        // Pasar los eventos a una variable global para que app.js pueda acceder a ellos
        window.calendarEvents = <?php echo $eventsJson; ?>;
    </script>
    <script src="assets/js/app.js"></script>
</body>
</html>