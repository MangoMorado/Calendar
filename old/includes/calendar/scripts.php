<?php
/**
 * Calendar Scripts
 * Este archivo contiene los scripts JavaScript necesarios para el calendario
 */

// Función para generar los scripts del calendario
function getCalendarScripts($eventsJson, $settings, $calendarType, $users = [])
{
    ob_start();
    ?>
<!-- Dependencias externas -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.17/locales/es.global.min.js"></script>

<!-- Elemento para tooltips de eventos -->
<div id="eventTooltip" class="event-tooltip"></div>

<!-- Definir variables globales para los datos del calendario -->
<script>
    // Variables globales para ser utilizadas por los módulos
    window.eventsJson = <?php echo $eventsJson; ?>;
    window.calendarUsers = <?php echo json_encode($users ?? []); ?>;
    window.calendarSettings = {
        slotMinTime: "<?php echo $settings['slotMinTime']; ?>",
        slotMaxTime: "<?php echo $settings['slotMaxTime']; ?>",
        slotDuration: "<?php echo $settings['slotDuration']; ?>",
        timeFormat: "<?php echo $settings['timeFormat']; ?>",
        businessDays: <?php echo json_encode($settings['businessDays'] ?? [1, 2, 3, 4, 5]); ?>
    };
    window.currentCalendarType = "<?php echo $calendarType; ?>";
</script>

<!-- Cargar app principal usando módulos ES6 -->
<script type="module" src="assets/js/app.js"></script>

<!-- Botón para deshacer cambios -->
<button id="undoButton" class="btn-undo" title="Deshacer último cambio">
    <i class="bi bi-arrow-counterclockwise"></i>
</button>

<style>
    /* Estilos para el botón de deshacer */
    .btn-undo {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 46px;
        height: 46px;
        border-radius: 50%;
        background-color: #3498db;
        color: white;
        border: none;
        box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        opacity: 0;
        transform: scale(0.8);
        transition: all 0.3s ease;
        z-index: 1000;
    }
    
    .btn-undo.show {
        opacity: 1;
        transform: scale(1);
    }
    
    .btn-undo:hover {
        background-color: #2980b9;
    }
    
    .btn-undo i {
        font-size: 22px;
    }
    
    /* Estilos para tooltip de eventos */
    .event-tooltip {
        position: absolute;
        background: white;
        border-radius: 4px;
        box-shadow: 0 1px 8px rgba(0,0,0,0.2);
        padding: 10px;
        z-index: 10000;
        max-width: 300px;
        display: none;
    }
    
    .event-tooltip .tooltip-title {
        font-weight: bold;
        margin-bottom: 5px;
        color: #333;
    }
    
    .event-tooltip .tooltip-time,
    .event-tooltip .tooltip-date,
    .event-tooltip .tooltip-calendar {
        font-size: 0.9em;
        margin-bottom: 3px;
        color: #666;
    }
    
    .event-tooltip .tooltip-desc {
        font-size: 0.9em;
        margin-top: 8px;
        color: #555;
        border-top: 1px solid #eee;
        padding-top: 5px;
    }
</style>
    <?php
    return ob_get_clean();
}
