<?php
/**
 * Calendar Template
 * Este archivo contiene la estructura HTML del calendario
 */

// Función para generar la estructura HTML del calendario
function renderCalendarTemplate($calendarType = 'general') {
    // Obtener el rango de fechas para mostrar
    $today = new DateTime();
    $weekStart = clone $today;
    $weekStart->modify('monday this week');
    $weekEnd = clone $weekStart;
    $weekEnd->modify('+6 days');
    $dateRange = $weekStart->format('d M') . ' - ' . $weekEnd->format('d M Y');
    
    // HTML de la plantilla
    ob_start();
    ?>
    <main class="calendar-grid calendar-home">
        <div class="calendar-header-section">
            <div class="calendar-title">
                <h2><?php echo getCalendarName($calendarType); ?></h2>
                <p class="date-range"><?php echo $dateRange; ?></p>
            </div>
        </div>
        
        <div id="alerts-container" class="alerts-container">
            <!-- Contenedor para mensajes de alerta dinámicos -->
        </div>
        
        <div class="calendar-navigation-section">
            <div class="calendar-tabs">
                <a href="index.php" class="calendar-tab <?php echo $calendarType === 'general' ? 'active' : ''; ?>">General</a>
                <a href="index.php?calendar=estetico" class="calendar-tab <?php echo $calendarType === 'estetico' ? 'active' : ''; ?>">Estético</a>
                <a href="index.php?calendar=veterinario" class="calendar-tab <?php echo $calendarType === 'veterinario' ? 'active' : ''; ?>">Veterinario</a>
            </div>
        </div>
        
        <div class="upcoming-appointments-section">
            <div class="upcoming-appointments">
                <h3><i class="bi bi-clock"></i> Próximas Citas</h3>
                <div class="appointment-list" id="upcomingAppointmentsList">
                    <!-- Se llenará con JavaScript -->
                </div>
            </div>
        </div>
        
        <div class="main-calendar-section">
            <button id="createAppointment" class="btn btn-success">
                <i class="bi bi-plus-lg"></i> Nueva Cita
            </button>
            <div id="calendar-container">
                <div id="calendar"></div>
            </div>
        </div>
        
        <div class="calendar-legend">
            <div class="calendar-legend-item">
                <div class="calendar-legend-color calendar-legend-estetico"></div>
                <span>Estético</span>
            </div>
            <div class="calendar-legend-item">
                <div class="calendar-legend-color calendar-legend-veterinario"></div>
                <span>Veterinario</span>
            </div>
            <div class="calendar-legend-item">
                <div class="calendar-legend-color calendar-legend-general"></div>
                <span>General</span>
            </div>
        </div>
    </main>

    <!-- Estilos personalizados para eventos de todo el día -->
    <style>
        /* Mejorar la visibilidad de los eventos de todo el día */
        .fc-event-all-day {
            border-left-width: 5px !important;
        }
        
        /* Asegurar que los eventos de todo el día se muestren completos */
        .fc-daygrid-day-events {
            min-height: 2em;
        }
        
        /* Color de fondo y estilo para la sección de todo el día */
        .fc-timegrid-axis-cushion {
            font-weight: bold;
        }
        
        /* Hacer que los eventos de todo el día sean más visibles */
        .fc-timegrid-event.fc-event-all-day {
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
        }
        
        /* Destacar eventos de todo el día cuando se pasa el cursor */
        .fc-event-all-day:hover {
            opacity: 0.85;
            transform: translateY(-1px);
            transition: all 0.2s;
        }
    </style>

    <?php
    return ob_get_clean();
} 