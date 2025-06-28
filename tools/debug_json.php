<?php
// Incluir archivos de configuración, funciones y autenticación
require_once __DIR__ . '/../config/database.php';
require_once 'includes/functions.php';

// Obtener citas para mostrar
$appointments = getAppointments(null, null, null);

// Mostrar datos de appointments
echo "<h2>Datos obtenidos de la base de datos:</h2>";
echo "<pre>";
print_r($appointments);
echo "</pre>";

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
        'borderColor' => $color,
        'calendarType' => $appointment['calendar_type']
    ];
}

// Mostrar el JSON de eventos
echo "<h2>JSON generado para el calendario:</h2>";
echo "<pre>";
echo json_encode($events, JSON_PRETTY_PRINT);
echo "</pre>";

// Verificar las fechas de cada evento para asegurarse de que están en formato correcto
echo "<h2>Verificación de formato de fechas:</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>start_time original</th><th>start en JSON</th><th>¿Válida para FullCalendar?</th></tr>";

foreach ($appointments as $index => $appointment) {
    $originalDate = $appointment['start_time'];
    $jsonDate = $events[$index]['start'];
    $isValid = (strtotime($originalDate) !== false) ? "✓ Válida" : "✗ Inválida";
    
    echo "<tr>";
    echo "<td>" . $appointment['id'] . "</td>";
    echo "<td>" . $originalDate . "</td>";
    echo "<td>" . $jsonDate . "</td>";
    echo "<td>" . $isValid . "</td>";
    echo "</tr>";
}

echo "</table>";
?> 