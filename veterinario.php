<?php
// Incluir archivos de configuración, funciones y autenticación
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Verificar que el usuario esté autenticado
requireAuth();

// Tipo de calendario fijo para esta página
$calendarType = 'veterinario';

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

// Obtener citas para esta semana y el tipo de calendario específico
$appointments = getAppointments($weekStartStr . ' 00:00:00', $weekEndStr . ' 23:59:59', $calendarType);

// Preparar los datos para FullCalendar (formato JSON)
$events = [];
foreach ($appointments as $appointment) {
    $events[] = [
        'id' => $appointment['id'],
        'title' => $appointment['title'],
        'start' => $appointment['start_time'],
        'end' => $appointment['end_time'],
        'description' => $appointment['description'],
        'backgroundColor' => '#2E86C1', // Color específico para veterinario
        'calendarType' => $appointment['calendar_type']
    ];
}
$eventsJson = json_encode($events);

// Obtener información del usuario actual
$currentUser = getCurrentUser();
$userId = $currentUser['id'];
$userDetails = getUserById($userId);

// Definir título de la página
$pageTitle = 'Calendario Veterinario | Mundo Animal';

// Estilos adicionales
$extraStyles = '<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css" rel="stylesheet" />';

// Incluir el header
include 'includes/header.php';
?>
    
<main class="container">
    <div class="calendar-header">
        <div class="calendar-title">
            <h2>Calendario Veterinario</h2>
            <p class="date-range"><?php echo date_format($weekStart, 'd M') . ' - ' . date_format($weekEnd, 'd M Y'); ?></p>
        </div>
        <div class="calendar-nav">
            <div class="calendar-tabs">
                <a href="index.php" class="calendar-tab">General</a>
                <a href="estetico.php" class="calendar-tab">Estético</a>
                <a href="veterinario.php" class="calendar-tab active">Veterinario</a>
            </div>
            <button id="createAppointment" class="btn btn-success" data-calendar-type="veterinario">
                <i class="bi bi-plus-lg"></i> Nueva Cita Veterinaria
            </button>
        </div>
    </div>
    
    <!-- Contenedor para FullCalendar -->
    <div id="calendar-container">
        <div id="calendar"></div>
    </div>
    
    <div class="info-panel">
        <div class="upcoming-appointments">
            <h3><i class="bi bi-clock"></i> Próximas Citas Veterinarias</h3>
            <div class="appointment-list" id="upcomingAppointmentsList">
                <!-- Se llenará con JavaScript -->
            </div>
        </div>
    </div>
</main>

<!-- Modal para crear/editar citas -->
<div id="appointmentModal" class="modal">
    <div class="modal-content">
        <span class="close"><i class="bi bi-x-lg"></i></span>
        <h2 id="modalTitle"><i class="bi bi-calendar-plus"></i> Crear Cita Veterinaria</h2>
        
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
            
            <!-- Tipo de calendario oculto -->
            <input type="hidden" id="calendarType" name="calendar_type" value="veterinario">
            
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

<style>
/* Estilos para las pestañas de calendarios */
.calendar-tabs {
    display: flex;
    gap: 0.5rem;
    margin-right: 1rem;
}

.calendar-tab {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    text-decoration: none;
    color: #4a5568;
    font-weight: 500;
    transition: all 0.2s ease;
    background-color: #f3f4f6;
}

.calendar-tab:hover {
    background-color: #e5e7eb;
}

.calendar-tab.active {
    background-color: #2E86C1;
    color: white;
}

/* Estilo específico para calendario veterinario */
.fc-event {
    background-color: #2E86C1 !important;
    border-color: #2E86C1 !important;
}

#createAppointment {
    background-color: #2E86C1;
}

#createAppointment:hover {
    background-color: #2874A6;
}
</style>

<?php
// Scripts adicionales
$extraScripts = '
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/locales-all.min.js"></script>
<script>
    // Pasar los eventos y tipo de calendario a variables globales para que app.js pueda acceder a ellos
    window.calendarEvents = ' . $eventsJson . ';
    window.currentCalendarType = "' . $calendarType . '";
</script>
<script src="assets/js/app.js"></script>
';

// Incluir el footer
include 'includes/footer.php';
?> 