<?php
// Script para actualizar la estructura de la tabla appointments
require_once 'config/database.php';

// Verificar la conexión
if (mysqli_connect_errno()) {
    die("Error de conexión a la base de datos: " . mysqli_connect_error());
}

echo "Conectado a la base de datos...<br>";

// Verificar si la columna all_day ya existe en la tabla appointments
$checkColumn = "SHOW COLUMNS FROM appointments LIKE 'all_day'";
$columnExists = mysqli_query($conn, $checkColumn);

if (mysqli_num_rows($columnExists) > 0) {
    echo "La columna 'all_day' ya existe en la tabla appointments.<br>";
} else {
    // Añadir la columna all_day a la tabla appointments
    $alterTable = "ALTER TABLE appointments ADD COLUMN all_day TINYINT(1) NOT NULL DEFAULT 0";
    
    if (mysqli_query($conn, $alterTable)) {
        echo "La columna 'all_day' ha sido añadida con éxito a la tabla appointments.<br>";
    } else {
        echo "Error al añadir la columna: " . mysqli_error($conn) . "<br>";
    }
}

// Cerrar la conexión
mysqli_close($conn);
echo "Operación completada.";
?> 