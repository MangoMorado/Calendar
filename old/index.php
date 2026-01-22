<?php

// Activar registro de errores
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__).'/php_error.log');
error_reporting(E_ALL);

// Incluir archivos de configuración, funciones y autenticación
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/calendar/init.php';

// Verificar que el usuario esté autenticado
requireAuth();

// Obtener el tipo de calendario de la URL (valor por defecto: general)
$calendarType = isset($_GET['calendar']) ? $_GET['calendar'] : 'general';

// Si es un calendario de usuario, verificar que el usuario tenga el calendario visible
if (strpos($calendarType, 'user_') === 0) {
    $userId = (int) substr($calendarType, 5);

    // Verificar si el usuario existe y tiene el calendario visible
    $sql = 'SELECT calendar_visible FROM users WHERE id = ?';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    // Si el usuario no existe o su calendario no está visible, redirigir al calendario general
    if (! $user || $user['calendar_visible'] != 1) {
        header('Location: index.php');
        exit;
    }
}

// Validar el tipo de calendario
$validTypes = ['general', 'estetico', 'veterinario'];
// Permitir calendarios por usuario (formato: user_XX donde XX es el ID del usuario)
if (! in_array($calendarType, $validTypes) && strpos($calendarType, 'user_') !== 0) {
    $calendarType = 'general'; // Valor por defecto si no es válido
}

// Agregar bloque de depuración (solo si se pasa el parámetro debug=1)
$debug = isset($_GET['debug']) && $_GET['debug'] == 1;
if ($debug) {
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border: 1px solid #f5c6cb;'>";
    echo '<h3>Modo de depuración</h3>';

    // Verificar la conexión a la base de datos
    if ($conn) {
        echo "<p style='color: green;'>✓ Conexión a la base de datos OK</p>";
    } else {
        echo "<p style='color: red;'>✗ Error de conexión: ".mysqli_connect_error().'</p>';
    }

    // Verificar si hay datos en la tabla appointments
    $sql = 'SELECT COUNT(*) as total FROM appointments';
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo '<p>Total de citas: <strong>'.$row['total'].'</strong></p>';
    } else {
        echo "<p style='color: red;'>✗ Error al consultar citas: ".mysqli_error($conn).'</p>';
    }

    echo '<p>Nota: Este bloque de depuración solo se muestra cuando se agrega ?debug=1 a la URL</p>';
    echo '</div>';
}

// Obtener información del usuario actual
$currentUser = getCurrentUser();
$userDetails = getUserById($currentUser['id']);

// Estilos adicionales para el fullcalendar
$extraStyles = '
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.css" rel="stylesheet">
';

// Definir título de la página
$pageTitle = 'Calendario | Mundo Animal';

// Incluir el header
include 'includes/header.php';

// Inicializar y mostrar el calendario
// Esta función también configura las variables globales $modalHtml y $calendarScripts
$calendarHtml = initCalendar($calendarType);
echo $calendarHtml;

// Si estamos en modo debug, mostrar las citas que se obtuvieron
if ($debug) {
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border: 1px solid #f5c6cb;'>";
    echo '<h3>Citas obtenidas de la base de datos</h3>';

    // Obtenemos las citas directamente para la depuración
    $debugAppointments = getAppointments(null, null, $calendarType);

    if (empty($debugAppointments)) {
        echo '<p>No se encontraron citas en la base de datos.</p>';
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo '<tr><th>ID</th><th>Título</th><th>Fecha Inicio</th><th>Fecha Fin</th><th>Tipo</th><th>Usuario</th></tr>';

        foreach ($debugAppointments as $appointment) {
            echo '<tr>';
            echo '<td>'.$appointment['id'].'</td>';
            echo '<td>'.$appointment['title'].'</td>';
            echo '<td>'.$appointment['start_time'].'</td>';
            echo '<td>'.$appointment['end_time'].'</td>';
            echo '<td>'.$appointment['calendar_type'].'</td>';
            echo '<td>'.$appointment['user'].'</td>';
            echo '</tr>';
        }

        echo '</table>';
    }

    echo '</div>';
}

// Imprimir el HTML del modal
echo $modalHtml;

// Incluir el footer
include 'includes/footer.php';

// Imprimir los scripts del calendario
echo $calendarScripts;
