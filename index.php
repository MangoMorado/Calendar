<?php
// Incluir archivos de configuración, funciones y autenticación
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Verificar que el usuario esté autenticado
requireAuth();

// Obtener los parámetros de navegación
$requestedWeek = isset($_GET['week']) ? $_GET['week'] : null;
$direction = isset($_GET['direction']) ? $_GET['direction'] : null;
$calendarType = isset($_GET['calendar']) ? $_GET['calendar'] : 'general';

// Validar el tipo de calendario
$availableCalendars = getCalendarTypes();
if (!array_key_exists($calendarType, $availableCalendars)) {
    $calendarType = 'general';
}

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

// Obtener citas para esta semana y el tipo de calendario seleccionado
$appointments = getAppointments($weekStartStr . ' 00:00:00', $weekEndStr . ' 23:59:59', $calendarType);

// Preparar los datos para FullCalendar (formato JSON)
$events = [];
foreach ($appointments as $appointment) {
    // Asignar colores diferentes según el tipo de calendario
    $color = '';
    switch ($appointment['calendar_type']) {
        case 'estetico':
            $color = '#8E44AD'; // Púrpura para estético
            break;
        case 'veterinario':
            $color = '#2E86C1'; // Azul para veterinario
            break;
        default:
            $color = '#5D69F7'; // Color predeterminado
    }
    
    $events[] = [
        'id' => $appointment['id'],
        'title' => $appointment['title'],
        'start' => $appointment['start_time'],
        'end' => $appointment['end_time'],
        'description' => $appointment['description'],
        'backgroundColor' => $color,
        'calendarType' => $appointment['calendar_type']
    ];
}
$eventsJson = json_encode($events);

// Obtener información del usuario actual
$currentUser = getCurrentUser();
$userId = $currentUser['id'];
$userDetails = getUserById($userId);

// Obtener la configuración del calendario
$settings = [];
$sql = "SELECT setting_key, setting_value FROM settings";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Valores por defecto si no existen en la base de datos
$settings['slotMinTime'] = $settings['slotMinTime'] ?? '00:00:00';
$settings['slotMaxTime'] = $settings['slotMaxTime'] ?? '24:00:00';
$settings['slotDuration'] = $settings['slotDuration'] ?? '00:30:00';
$settings['timeFormat'] = $settings['timeFormat'] ?? '12h';

// Definir título de la página según el tipo de calendario
$pageTitle = getCalendarName($calendarType) . ' | Mundo Animal';

// Estilos adicionales
$extraStyles = '<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css" rel="stylesheet" />
<style>
/* Estilos para los diferentes tipos de calendario */
.fc-event.calendar-estetico {
    background-color: #8E44AD !important;
    border-color: #8E44AD !important;
}

.fc-event.calendar-veterinario {
    background-color: #2E86C1 !important;
    border-color: #2E86C1 !important;
}

.fc-event.calendar-general {
    background-color: #5D69F7 !important;
    border-color: #5D69F7 !important;
}

/* Estilos para el selector de calendario */
.calendar-selector {
    position: relative;
    min-width: 200px;
    flex: 1;
}

#calendarTypeSelector {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-color: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    color: #2d3748;
    cursor: pointer;
    font-size: 0.95rem;
    height: 42px;
    padding: 0 15px;
    width: 100%;
    background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%23718096\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'%3E%3Cpolyline points=\'6 9 12 15 18 9\'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 15px center;
    background-size: 15px;
}

.calendar-nav {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

/* Leyenda de colores para los calendarios */
.calendar-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 0;
    margin-bottom: 20px;
    padding: 10px 15px;
    border-radius: 8px;
    background-color: rgba(243, 244, 246, 0.6);
}

.calendar-legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: #4a5568;
}

.calendar-legend-color {
    width: 12px;
    height: 12px;
    border-radius: 3px;
}

.calendar-legend-estetico {
    background-color: #8E44AD;
}

.calendar-legend-veterinario {
    background-color: #2E86C1;
}

.calendar-legend-general {
    background-color: #5D69F7;
}
</style>';

// Incluir el header
include 'includes/header.php';
?>
    
<main class="container">
    <div class="calendar-header">
        <div class="calendar-title">
            <h2>Calendario General</h2>
            <p class="date-range"><?php echo date_format($weekStart, 'd M') . ' - ' . date_format($weekEnd, 'd M Y'); ?></p>
        </div>
        <div class="calendar-nav">
            <div class="calendar-tabs">
                <a href="index.php" class="calendar-tab active">General</a>
                <a href="estetico.php" class="calendar-tab">Estético</a>
                <a href="veterinario.php" class="calendar-tab">Veterinario</a>
            </div>
            <button id="createAppointment" class="btn btn-success">
                <i class="bi bi-plus-lg"></i> Nueva Cita
            </button>
        </div>
    </div>
    
    <!-- Contenedor para FullCalendar -->
    <div id="calendar-container">
        <div id="calendar"></div>
    </div>
    
    <!-- Leyenda de colores para los calendarios -->
    <div class="calendar-legend">
        <div class="calendar-legend-item">
            <div class="calendar-legend-color calendar-legend-estetico"></div>
            <span>Estético</span>
        </div>
        <div class="calendar-legend-item">
            <div class="calendar-legend-color calendar-legend-veterinario"></div>
            <span>Veterinario</span>
        </div>
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
            
            <div class="form-group">
                <label for="calendarType"><i class="bi bi-calendar3"></i> Tipo de Calendario:</label>
                <select id="calendarType" name="calendar_type" class="form-control">
                    <option value="estetico">Calendario Estético</option>
                    <option value="veterinario">Calendario Veterinario</option>
                </select>
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

<?php
// Scripts adicionales
$extraScripts = '
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/locales-all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/interaction@5.10.1/main.min.js"></script>
<script>
    // Verificar que FullCalendar esté disponible
    if (typeof FullCalendar === "undefined") {
        console.error("Error: FullCalendar no está cargado correctamente");
    } else {
        console.log("FullCalendar cargado correctamente");
    }
    
    // Pasar los eventos, tipo de calendario y configuración a variables globales
    window.calendarEvents = ' . $eventsJson . ';
    window.currentCalendarType = "' . $calendarType . '";
    window.calendarSettings = ' . json_encode($settings) . ';
</script>
<script type="module" src="assets/js/app.js"></script>
';

// Incluir el footer
include 'includes/footer.php';
?>