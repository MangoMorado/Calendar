<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/chatbot/contactos-validation.php';

// Verificar autenticación
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (! isAuthenticated()) {
    echo '❌ Usuario NO autenticado<br>';
    echo 'Debes estar logueado para importar contactos.<br>';
    exit;
}

$user = getCurrentUser();
echo '<h2>Importación de Contactos desde Archivo JSON</h2>';
echo '✅ Usuario autenticado: '.htmlspecialchars($user['name']).'<br><br>';

// Verificar si se proporcionó un archivo
$jsonFile = $_POST['json_file'] ?? 'contactos_mundoanimal.json';

if (! file_exists($jsonFile)) {
    echo "❌ El archivo '$jsonFile' no existe<br>";
    echo 'Asegúrate de que el archivo esté en el directorio correcto.<br>';
    exit;
}

echo "<h3>Procesando archivo: $jsonFile</h3>";

// Leer el archivo JSON
$jsonContent = file_get_contents($jsonFile);
if (! $jsonContent) {
    echo '❌ No se pudo leer el archivo JSON<br>';
    exit;
}

// Decodificar JSON
$contactos = json_decode($jsonContent, true);
if (! $contactos || ! is_array($contactos)) {
    echo '❌ El archivo JSON no tiene una estructura válida<br>';
    echo 'Error de JSON: '.json_last_error_msg().'<br>';
    exit;
}

echo '✅ Archivo JSON leído correctamente<br>';
echo '📊 Total de contactos en el archivo: '.count($contactos).'<br><br>';

// Verificar si la tabla contacts existe, si no, crearla
$checkTable = "SHOW TABLES LIKE 'contacts'";
$result = mysqli_query($conn, $checkTable);
if (mysqli_num_rows($result) == 0) {
    echo '<h3>Creando tabla contacts...</h3>';
    $createTable = 'CREATE TABLE contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        number VARCHAR(50) UNIQUE NOT NULL,
        pushName VARCHAR(255),
        send TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )';

    if (mysqli_query($conn, $createTable)) {
        echo '✅ Tabla contacts creada exitosamente<br><br>';
    } else {
        echo '❌ Error creando tabla contacts: '.mysqli_error($conn).'<br>';
        exit;
    }
} else {
    echo '✅ Tabla contacts ya existe<br><br>';
}

// Procesar los contactos
$imported = 0;
$updated = 0;
$skipped = 0;
$errores = [];
$invalid_structure = 0;

echo '<h3>Procesando contactos...</h3>';
echo "<div style='max-height: 400px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; background: #f9f9f9;'>";

foreach ($contactos as $index => $contact) {
    // Validar estructura del contacto
    if (! is_array($contact)) {
        $errores[] = "Contacto en índice $index no es un objeto válido";
        $invalid_structure++;

        continue;
    }

    // Extraer datos del contacto
    $remoteJid = $contact['remoteJid'] ?? '';
    $pushName = $contact['pushName'] ?? null;

    // Validar que tenga remoteJid
    if (! $remoteJid) {
        $errores[] = "Contacto en índice $index no tiene remoteJid válido";
        $invalid_structure++;

        continue;
    }

    // Ignorar grupos de WhatsApp
    if (str_ends_with($remoteJid, '@g.us')) {
        $skipped++;
        echo '⏭️ Omitido (grupo): '.htmlspecialchars($remoteJid).'<br>';

        continue;
    }

    // Validación robusta del número de WhatsApp
    $validacion = limpiarYValidarNumeroWhatsApp($remoteJid);
    if (! $validacion['valid']) {
        $skipped++;
        echo '⏭️ Omitido (número inválido): '.htmlspecialchars($remoteJid).' - '.$validacion['error'].'<br>';

        continue;
    }

    // Verificar si el contacto ya existe
    $sql = 'SELECT id FROM contacts WHERE number = ?';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $remoteJid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($res)) {
        // Actualizar contacto existente
        $sql2 = 'UPDATE contacts SET pushName = ? WHERE number = ?';
        $stmt2 = mysqli_prepare($conn, $sql2);
        mysqli_stmt_bind_param($stmt2, 'ss', $pushName, $remoteJid);
        if (mysqli_stmt_execute($stmt2)) {
            $updated++;
            echo '✅ Actualizado: '.htmlspecialchars($remoteJid).' - '.htmlspecialchars($pushName).'<br>';
        } else {
            $errores[] = "Error actualizando $remoteJid: ".mysqli_error($conn);
            echo '❌ Error actualizando: '.htmlspecialchars($remoteJid).'<br>';
        }
    } else {
        // Insertar nuevo contacto
        $sql2 = 'INSERT INTO contacts (number, pushName, send) VALUES (?, ?, 0)';
        $stmt2 = mysqli_prepare($conn, $sql2);
        mysqli_stmt_bind_param($stmt2, 'ss', $remoteJid, $pushName);
        if (mysqli_stmt_execute($stmt2)) {
            $imported++;
            echo '✅ Insertado: '.htmlspecialchars($remoteJid).' - '.htmlspecialchars($pushName).'<br>';
        } else {
            $errores[] = "Error insertando $remoteJid: ".mysqli_error($conn);
            echo '❌ Error insertando: '.htmlspecialchars($remoteJid).'<br>';
        }
    }
}

echo '</div><br>';

// Resumen final
echo '<h3>📊 Resumen de la importación:</h3>';
echo "✅ Contactos importados: <strong>$imported</strong><br>";
echo "✅ Contactos actualizados: <strong>$updated</strong><br>";
echo "⏭️ Contactos omitidos (grupos): <strong>$skipped</strong><br>";
echo "❌ Estructuras inválidas: <strong>$invalid_structure</strong><br>";
echo '❌ Errores: <strong>'.count($errores).'</strong><br>';
echo '📋 Total procesados: <strong>'.count($contactos).'</strong><br>';

if (! empty($errores)) {
    echo '<br><h4>❌ Errores detallados:</h4>';
    echo "<div style='max-height: 200px; overflow-y: auto; border: 1px solid #ffcccc; padding: 10px; background: #fff5f5;'>";
    foreach ($errores as $error) {
        echo '• '.htmlspecialchars($error).'<br>';
    }
    echo '</div>';
}

// Verificación final
echo '<br><h3>🔍 Verificación final:</h3>';
$countSql = 'SELECT COUNT(*) as total FROM contacts';
$countResult = mysqli_query($conn, $countSql);
$countRow = mysqli_fetch_assoc($countResult);
echo 'Total de contactos en la base de datos: <strong>'.$countRow['total'].'</strong><br>';

echo '<br><strong>✅ Importación completada exitosamente</strong><br>';
echo "<br><em>Nota: Los contactos importados tienen 'send' = 0 por defecto. Puedes activarlos desde el panel de administración.</em><br>";

// Formulario para importar otro archivo
echo '<br><hr><br>';
echo '<h3>📁 Importar otro archivo JSON:</h3>';
echo "<form method='post' style='background: #f0f0f0; padding: 15px; border-radius: 5px;'>";
echo "<label for='json_file'>Nombre del archivo JSON:</label><br>";
echo "<input type='text' id='json_file' name='json_file' value='contactos_mundoanimal.json' style='width: 300px; padding: 5px; margin: 5px 0;'><br>";
echo "<input type='submit' value='Importar' style='background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer;'>";
echo '</form>';
?> 