<?php
// Incluir archivos de configuración, funciones y autenticación
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/calendar/init.php';

// Verificar que el usuario esté autenticado
requireAuth();

// Tipo de calendario fijo para esta página
$calendarType = 'veterinario';

// Inicializar el calendario
$calendarData = initializeCalendar($calendarType);

// Obtener información del usuario actual
$currentUser = getCurrentUser();
$userDetails = getUserById($currentUser['id']);

// Estilos adicionales para el fullcalendar
$extraStyles = '
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.css" rel="stylesheet">
';

// Definir título de la página
$pageTitle = $calendarData['pageTitle'];

// Incluir el header
include 'includes/header.php';

// Renderizar componentes del calendario
$renderedCalendar = renderCalendar($calendarData);

// Imprimir el HTML del calendario
echo $renderedCalendar['calendarHtml'];

// Imprimir el HTML del modal
echo $renderedCalendar['modalHtml'];

// Incluir el footer
include 'includes/footer.php';

// Imprimir los scripts del calendario
echo $renderedCalendar['calendarScripts'];
?> 