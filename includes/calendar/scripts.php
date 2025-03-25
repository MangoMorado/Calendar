<?php
/**
 * Calendar Scripts
 * Este archivo contiene los scripts JavaScript necesarios para el calendario
 */

// Función para generar los scripts del calendario
function getCalendarScripts($eventsJson, $settings, $calendarType) {
    ob_start();
    ?>
<!-- Dependencias externas -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/locales/es.global.min.js"></script>

<!-- Definir variables globales para los datos del calendario -->
<script>
    // Variables globales para ser utilizadas por los módulos
    const eventsJson = <?php echo $eventsJson; ?>;
    const calendarSettings = {
        slotMinTime: "<?php echo $settings['slotMinTime']; ?>",
        slotMaxTime: "<?php echo $settings['slotMaxTime']; ?>",
        slotDuration: "<?php echo $settings['slotDuration']; ?>"
    };
    const currentCalendarType = "<?php echo $calendarType; ?>";
</script>

<!-- Cargar módulos JS del calendario -->
<script src="includes/calendar/js-modules/utils.js"></script>
<script src="includes/calendar/js-modules/event-handlers.js"></script>
<script src="includes/calendar/js-modules/upcoming-appointments.js"></script>
<script src="includes/calendar/js-modules/modal-handlers.js"></script>
<script src="includes/calendar/js-modules/calendar-init.js"></script>
<script src="includes/calendar/js-modules/main.js"></script>
    <?php
    return ob_get_clean();
} 