<?php
/**
 * Script para actualizar la tabla contacts con las columnas faltantes
 */

// Incluir configuración de base de datos
require_once __DIR__ . '/../config/database.php';

echo "Verificando y actualizando tabla contacts...\n";

// Verificar si existe la columna user_id
$checkUserIdColumn = "SHOW COLUMNS FROM contacts LIKE 'user_id'";
$userIdResult = mysqli_query($conn, $checkUserIdColumn);

if (mysqli_num_rows($userIdResult) == 0) {
    echo "Añadiendo columna user_id...\n";
    $alterUserIdSql = "ALTER TABLE contacts ADD COLUMN user_id INT(11) DEFAULT NULL";
    if (mysqli_query($conn, $alterUserIdSql)) {
        echo "Columna user_id añadida correctamente\n";
    } else {
        echo "Error al añadir columna user_id: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "La columna user_id ya existe\n";
}

// Verificar si existe la columna created_at
$checkCreatedAtColumn = "SHOW COLUMNS FROM contacts LIKE 'created_at'";
$createdAtResult = mysqli_query($conn, $checkCreatedAtColumn);

if (mysqli_num_rows($createdAtResult) == 0) {
    echo "Añadiendo columna created_at...\n";
    $alterCreatedAtSql = "ALTER TABLE contacts ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP";
    if (mysqli_query($conn, $alterCreatedAtSql)) {
        echo "Columna created_at añadida correctamente\n";
    } else {
        echo "Error al añadir columna created_at: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "La columna created_at ya existe\n";
}

// Verificar si existe el índice user_id
$checkUserIdIndex = "SHOW INDEX FROM contacts WHERE Key_name = 'idx_user_id'";
$userIdIndexResult = mysqli_query($conn, $checkUserIdIndex);

if (mysqli_num_rows($userIdIndexResult) == 0) {
    echo "Añadiendo índice idx_user_id...\n";
    $addUserIdIndexSql = "ALTER TABLE contacts ADD INDEX idx_user_id (user_id)";
    if (mysqli_query($conn, $addUserIdIndexSql)) {
        echo "Índice idx_user_id añadido correctamente\n";
    } else {
        echo "Error al añadir índice idx_user_id: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "El índice idx_user_id ya existe\n";
}

// Verificar si existe el índice number
$checkNumberIndex = "SHOW INDEX FROM contacts WHERE Key_name = 'idx_number'";
$numberIndexResult = mysqli_query($conn, $checkNumberIndex);

if (mysqli_num_rows($numberIndexResult) == 0) {
    echo "Añadiendo índice idx_number...\n";
    $addNumberIndexSql = "ALTER TABLE contacts ADD INDEX idx_number (number)";
    if (mysqli_query($conn, $addNumberIndexSql)) {
        echo "Índice idx_number añadido correctamente\n";
    } else {
        echo "Error al añadir índice idx_number: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "El índice idx_number ya existe\n";
}

// Verificar si existe la foreign key
$checkForeignKey = "SELECT CONSTRAINT_NAME 
                   FROM information_schema.KEY_COLUMN_USAGE 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'contacts' 
                   AND COLUMN_NAME = 'user_id' 
                   AND REFERENCED_TABLE_NAME = 'users'";
$foreignKeyResult = mysqli_query($conn, $checkForeignKey);

if (mysqli_num_rows($foreignKeyResult) == 0) {
    echo "Añadiendo foreign key para user_id...\n";
    $addForeignKeySql = "ALTER TABLE contacts ADD CONSTRAINT fk_contacts_user_id 
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL";
    if (mysqli_query($conn, $addForeignKeySql)) {
        echo "Foreign key añadida correctamente\n";
    } else {
        echo "Error al añadir foreign key: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "La foreign key ya existe\n";
}

echo "Verificación completada.\n";
?> 