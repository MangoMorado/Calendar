<?php
/**
 * Calendar Template
 * Este archivo contiene la estructura HTML del calendario
 */

// Función para generar la estructura HTML del calendario
function renderCalendarTemplate($calendarType = 'general') {
    global $conn;
    
    // Obtener el rango de fechas para mostrar
    $today = new DateTime();
    $weekStart = clone $today;
    $weekStart->modify('monday this week');
    $weekEnd = clone $weekStart;
    $weekEnd->modify('+6 days');
    $dateRange = $weekStart->format('d M') . ' - ' . $weekEnd->format('d M Y');
    
    // Obtener todos los usuarios para crear pestañas de calendario
    $sql = "SELECT id, name, color FROM users WHERE calendar_visible = 1 ORDER BY name";
    $result = mysqli_query($conn, $sql);
    $users = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
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
                
                <!-- Separador para los calendarios de usuarios -->
                <div class="calendar-tabs-divider"></div>
                
                <!-- Calendarios por usuario -->
                <?php foreach ($users as $user): ?>
                    <a href="index.php?calendar=user_<?php echo $user['id']; ?>" 
                       class="calendar-tab user-calendar-tab <?php echo $calendarType === 'user_' . $user['id'] ? 'active' : ''; ?>"
                       style="border-color: <?php echo $user['color']; ?>; <?php echo $calendarType === 'user_' . $user['id'] ? 'background-color: ' . $user['color'] . '; color: white;' : ''; ?>">
                        <i class="bi bi-person-fill"></i> <?php echo htmlspecialchars($user['name']); ?>
                    </a>
                <?php endforeach; ?>
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
        
        <div class="undo-section">
            <button id="undoButton" class="btn btn-warning align-items-center gap-2" style="display: none;">
                <i class="bi bi-arrow-counterclockwise"></i>
                <span>Deshacer último cambio</span>
            </button>
        </div>
    </main>

    <!-- Estilos personalizados -->
    <style>
        /* Estilos para eventos de todo el día */
        .fc-event-all-day {
            border-left-width: 5px !important;
        }
        
        .fc-daygrid-day-events {
            min-height: 2em;
        }
        
        .fc-timegrid-axis-cushion {
            font-weight: bold;
        }
        
        .fc-timegrid-event.fc-event-all-day {
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
        }
        
        .fc-event-all-day:hover {
            opacity: 0.85;
            transform: translateY(-1px);
            transition: all 0.2s;
        }

        /* Estilos para el botón de deshacer */
        .undo-section {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
        }

        #undoButton {
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            padding: 10px 20px;
            background-color: #ffc107;
            border: none;
            color: #000;
            font-weight: 500;
        }

        #undoButton:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            background-color: #ffb300;
        }

        #undoButton:active {
            transform: translateY(0);
            background-color: #ffa000;
        }

        #undoButton.show {
            display: flex !important;
            animation: slideIn 0.3s ease;
        }
        
        /* Estilos para los tabs de calendario de usuario */
        .calendar-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 15px;
        }
        
        .calendar-tabs-divider {
            width: 100%;
            border-top: 1px solid #ddd;
            margin: 10px 0;
        }
        
        .user-calendar-tab {
            border-left-width: 4px;
            border-left-style: solid;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .user-calendar-tab i {
            font-size: 14px;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>

    <!-- Scripts del calendario -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Inicialización de variables globales
        window.calendarColors = {
            estetico: "#8E44AD",
            veterinario: "#2E86C1",
            general: "#5D69F7"
        };
        window.calendarNames = {
            estetico: "Estético",
            veterinario: "Veterinario",
            general: "General"
        };
        
        // Agregar colores y nombres para los calendarios de usuarios
        <?php foreach ($users as $user): ?>
        window.calendarColors['user_<?php echo $user['id']; ?>'] = "<?php echo $user['color']; ?>";
        window.calendarNames['user_<?php echo $user['id']; ?>'] = "<?php echo addslashes($user['name']); ?>";
        <?php endforeach; ?>
        
        window.lastAction = null;
        window.lastEventState = null;
        window.elements = null;
        window.state = {
            isEditMode: false,
            currentAppointmentId: null,
            currentCalendarType: null
        };
    </script>
    <!-- Cargar app principal usando módulos ES6 -->
    <script type="module" src="assets/js/app.js"></script>

    <?php
    return ob_get_clean();
} 