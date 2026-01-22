<?php
// Incluir la configuración de la base de datos
require_once __DIR__.'/../config/database.php';

echo '<h1>Diagnóstico de base de datos</h1>';

// Verificar la conexión a la base de datos
if ($conn) {
    echo "<p style='color: green;'>✓ Conexión a la base de datos establecida correctamente</p>";
} else {
    echo "<p style='color: red;'>✗ Error de conexión a la base de datos: ".mysqli_connect_error().'</p>';
    exit;
}

// Verificar si existe la base de datos
$result = mysqli_query($conn, "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '".DB_NAME."'");
if (mysqli_num_rows($result) > 0) {
    echo "<p style='color: green;'>✓ Base de datos '".DB_NAME."' existe</p>";
} else {
    echo "<p style='color: red;'>✗ La base de datos '".DB_NAME."' no existe</p>";
    exit;
}

// Verificar si existen las tablas
$tables = ['appointments', 'users'];
foreach ($tables as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        echo "<p style='color: green;'>✓ Tabla '$table' existe</p>";
    } else {
        echo "<p style='color: red;'>✗ La tabla '$table' no existe</p>";
    }
}

// Verificar si hay datos en la tabla appointments
$sql = 'SELECT COUNT(*) as total FROM appointments';
$result = mysqli_query($conn, $sql);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo '<p>Total de citas en la base de datos: <strong>'.$row['total'].'</strong></p>';

    if ($row['total'] == 0) {
        echo "<p style='color: orange;'>⚠ No hay citas en la base de datos</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Error al consultar citas: ".mysqli_error($conn).'</p>';
}

// Mostrar las citas existentes
if ($row['total'] > 0) {
    echo '<h2>Citas existentes:</h2>';
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo '<tr><th>ID</th><th>Título</th><th>Fecha Inicio</th><th>Fecha Fin</th><th>Tipo</th></tr>';

    $sql = 'SELECT * FROM appointments ORDER BY start_time DESC';
    $result = mysqli_query($conn, $sql);

    while ($appointment = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<td>'.$appointment['id'].'</td>';
        echo '<td>'.$appointment['title'].'</td>';
        echo '<td>'.$appointment['start_time'].'</td>';
        echo '<td>'.$appointment['end_time'].'</td>';
        echo '<td>'.$appointment['calendar_type'].'</td>';
        echo '</tr>';
    }

    echo '</table>';
}

// Crear una cita de prueba si no hay ninguna
if ($row['total'] == 0) {
    echo '<h2>Creando cita de prueba...</h2>';

    $title = 'Cita de prueba';
    $description = 'Esta es una cita de prueba creada automáticamente';
    $startTime = date('Y-m-d H:i:s', strtotime('+1 day 10:00:00'));
    $endTime = date('Y-m-d H:i:s', strtotime('+1 day 11:00:00'));
    $calendarType = 'general';

    $sql = 'INSERT INTO appointments (title, description, start_time, end_time, calendar_type) 
            VALUES (?, ?, ?, ?, ?)';

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sssss', $title, $description, $startTime, $endTime, $calendarType);

    if (mysqli_stmt_execute($stmt)) {
        echo "<p style='color: green;'>✓ Cita de prueba creada correctamente con ID: ".mysqli_insert_id($conn).'</p>';
        echo "<p>Título: $title</p>";
        echo "<p>Fecha inicio: $startTime</p>";
        echo "<p>Fecha fin: $endTime</p>";
        echo "<p>Tipo: $calendarType</p>";
    } else {
        echo "<p style='color: red;'>✗ Error al crear cita de prueba: ".mysqli_error($conn).'</p>';
    }
}

// Verificar la estructura de la tabla appointments
echo '<h2>Estructura de la tabla appointments:</h2>';
$result = mysqli_query($conn, 'DESCRIBE appointments');
if ($result) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo '<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Defecto</th><th>Extra</th></tr>';

    while ($row = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<td>'.$row['Field'].'</td>';
        echo '<td>'.$row['Type'].'</td>';
        echo '<td>'.$row['Null'].'</td>';
        echo '<td>'.$row['Key'].'</td>';
        echo '<td>'.$row['Default'].'</td>';
        echo '<td>'.$row['Extra'].'</td>';
        echo '</tr>';
    }

    echo '</table>';
} else {
    echo "<p style='color: red;'>✗ Error al verificar estructura de tabla: ".mysqli_error($conn).'</p>';
}
?> 