<?php
// Incluir archivos de configuración, funciones y autenticación
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/calendar/init.php';

// Verificar que el usuario esté autenticado
requireAuth();

// Agregar bloque de depuración (solo si se pasa el parámetro debug=1)
$debug = isset($_GET['debug']) && $_GET['debug'] == 1;
if ($debug) {
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border: 1px solid #f5c6cb;'>";
    echo "<h3>Modo de depuración</h3>";
    
    // Verificar la conexión a la base de datos
    if ($conn) {
        echo "<p style='color: green;'>✓ Conexión a la base de datos OK</p>";
    } else {
        echo "<p style='color: red;'>✗ Error de conexión: " . mysqli_connect_error() . "</p>";
    }
    
    // Verificar si hay datos en la tabla appointments
    $sql = "SELECT COUNT(*) as total FROM appointments";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "<p>Total de citas: <strong>" . $row['total'] . "</strong></p>";
    } else {
        echo "<p style='color: red;'>✗ Error al consultar citas: " . mysqli_error($conn) . "</p>";
    }
    
    echo "<p>Nota: Este bloque de depuración solo se muestra cuando se agrega ?debug=1 a la URL</p>";
    echo "</div>";
}

// Obtener los parámetros de navegación
$calendarType = isset($_GET['calendar']) ? $_GET['calendar'] : 'general';

// Inicializar el calendario
$calendarData = initializeCalendar($calendarType);

// Si estamos en modo debug, mostrar las citas que se obtuvieron
if ($debug) {
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border: 1px solid #f5c6cb;'>";
    echo "<h3>Citas obtenidas de la base de datos</h3>";
    
    if (empty($calendarData['events'])) {
        echo "<p>No se encontraron citas en la base de datos.</p>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Título</th><th>Fecha Inicio</th><th>Fecha Fin</th><th>Tipo</th></tr>";
        
        foreach ($calendarData['events'] as $event) {
            echo "<tr>";
            echo "<td>" . $event['id'] . "</td>";
            echo "<td>" . $event['title'] . "</td>";
            echo "<td>" . $event['start'] . "</td>";
            echo "<td>" . $event['end'] . "</td>";
            echo "<td>" . $event['calendarType'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    echo "</div>";
}

// Obtener información del usuario actual
$currentUser = getCurrentUser();
$userDetails = getUserById($currentUser['id']);

// Estilos adicionales para el fullcalendar
$extraStyles = '
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
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