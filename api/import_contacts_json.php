<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/chatbot/contactos-validation.php';

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// Obtener el contenido JSON del input
$input = json_decode(file_get_contents('php://input'), true);

// Verificar si se recibió un archivo JSON válido
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se recibió un JSON válido']);
    exit;
}

// Verificar si es un array directo o si tiene la estructura esperada
$contactos = null;
if (is_array($input)) {
    // Si es un array directo (como en contactos_mundoanimal.json)
    if (isset($input[0]) && is_array($input[0])) {
        $contactos = $input;
    } 
    // Si tiene la estructura con clave 'contactos'
    elseif (isset($input['contactos']) && is_array($input['contactos'])) {
        $contactos = $input['contactos'];
    }
    // Si es un objeto único, convertirlo en array
    elseif (isset($input['remoteJid']) || isset($input['id'])) {
        $contactos = [$input];
    }
}

if (!$contactos || !is_array($contactos)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Estructura JSON no válida. Se espera un array de contactos o un objeto con clave "contactos"',
        'received_structure' => array_keys($input)
    ]);
    exit;
}

$imported = 0;
$updated = 0;
$skipped = 0;
$errores = [];
$invalid_structure = 0;

foreach ($contactos as $index => $contact) {
    // Validar estructura del contacto
    if (!is_array($contact)) {
        $errores[] = "Contacto en índice $index no es un objeto válido";
        $invalid_structure++;
        continue;
    }

    // Extraer datos del contacto con múltiples formatos posibles
    $remoteJid = $contact['remoteJid'] ?? $contact['number'] ?? $contact['phone'] ?? '';
    $pushName = $contact['pushName'] ?? $contact['name'] ?? $contact['displayName'] ?? null;
    
    // Validar que tenga al menos un identificador válido
    if (!$remoteJid) {
        $errores[] = "Contacto en índice $index no tiene remoteJid válido";
        $invalid_structure++;
        continue;
    }

    // Ignorar grupos de WhatsApp
    if (str_ends_with($remoteJid, '@g.us')) {
        $skipped++;
        continue;
    }

    // Validar número de WhatsApp usando la nueva función robusta
    $validacion = limpiarYValidarNumeroWhatsApp($remoteJid);
    if (!$validacion['valid']) {
        $errores[] = "Número de WhatsApp inválido: $remoteJid - " . $validacion['error'];
        $invalid_structure++;
        continue;
    }

    // Verificar si el contacto ya existe
    $sql = "SELECT id FROM contacts WHERE number = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $remoteJid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($res)) {
        // Actualizar contacto existente
        $sql2 = "UPDATE contacts SET pushName = ? WHERE number = ?";
        $stmt2 = mysqli_prepare($conn, $sql2);
        mysqli_stmt_bind_param($stmt2, "ss", $pushName, $remoteJid);
        if (mysqli_stmt_execute($stmt2)) {
            $updated++;
        } else {
            $errores[] = "Error actualizando $remoteJid: " . mysqli_error($conn);
        }
    } else {
        // Insertar nuevo contacto
        $sql2 = "INSERT INTO contacts (number, pushName, send) VALUES (?, ?, 0)";
        $stmt2 = mysqli_prepare($conn, $sql2);
        mysqli_stmt_bind_param($stmt2, "ss", $remoteJid, $pushName);
        if (mysqli_stmt_execute($stmt2)) {
            $imported++;
        } else {
            $errores[] = "Error insertando $remoteJid: " . mysqli_error($conn);
        }
    }
}

// Verificar si la tabla contacts existe, si no, crearla
$checkTable = "SHOW TABLES LIKE 'contacts'";
$result = mysqli_query($conn, $checkTable);
if (mysqli_num_rows($result) == 0) {
    // Crear la tabla contacts si no existe
    $createTable = "CREATE TABLE contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        number VARCHAR(50) UNIQUE NOT NULL,
        pushName VARCHAR(255),
        send TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($conn, $createTable)) {
        echo json_encode([
            'success' => true,
            'message' => 'Tabla contacts creada automáticamente',
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'invalid_structure' => $invalid_structure,
            'errores' => $errores
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error creando tabla contacts: ' . mysqli_error($conn),
            'errores' => $errores
        ]);
    }
} else {
    echo json_encode([
        'success' => true,
        'imported' => $imported,
        'updated' => $updated,
        'skipped' => $skipped,
        'invalid_structure' => $invalid_structure,
        'errores' => $errores,
        'total_processed' => count($contactos)
    ]);
} 