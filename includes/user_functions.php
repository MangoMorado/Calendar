<?php
// Incluir la configuración de la base de datos
require_once __DIR__ . '/../config/database.php';

// Establecer timezone global desde settings
$timezone = 'America/Bogota';
global $conn;
$sql = "SELECT setting_value FROM settings WHERE setting_key = 'timezone' LIMIT 1";
$result = mysqli_query($conn, $sql);
if ($result && $row = mysqli_fetch_assoc($result)) {
    $timezone = $row['setting_value'];
}
date_default_timezone_set($timezone);

/**
 * Registrar un nuevo usuario
 */
function registerUser($email, $password, $name, $role = 'user') {
    global $conn;
    
    // Verificar si el correo ya existe
    $checkSql = "SELECT id FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $checkSql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) > 0) {
        return ['success' => false, 'message' => 'El correo electrónico ya está registrado'];
    }
    
    // Encriptar la contraseña con password_hash (más seguro que MD5)
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insertar el nuevo usuario
    $sql = "INSERT INTO users (email, password, name, role) VALUES (?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssss", $email, $hashedPassword, $name, $role);
    
    if (mysqli_stmt_execute($stmt)) {
        return ['success' => true, 'message' => 'Usuario registrado correctamente', 'user_id' => mysqli_insert_id($conn)];
    } else {
        return ['success' => false, 'message' => 'Error al registrar el usuario: ' . mysqli_error($conn)];
    }
}

/**
 * Verificar credenciales de usuario para inicio de sesión
 */
function loginUser($email, $password) {
    global $conn;
    
    $sql = "SELECT id, email, password, name, role FROM users WHERE email = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($user = mysqli_fetch_assoc($result)) {
        // Verificar la contraseña
        if (password_verify($password, $user['password'])) {
            // No incluir la contraseña en la sesión
            unset($user['password']);
            
            // Registrar el inicio de sesión en el historial
            updateUserHistory($user['id'], 'Inicio de sesión');
            
            return ['success' => true, 'user' => $user];
        }
    }
    
    return ['success' => false, 'message' => 'Correo electrónico o contraseña incorrectos'];
}

/**
 * Actualizar historial de usuario
 */
function updateUserHistory($userId, $action, $details = null) {
    global $conn;
    
    // Obtener historial actual
    $sql = "SELECT history FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    
    // Formatear la nueva entrada del historial con más detalle si se proporcionan
    $timestamp = date('Y-m-d H:i:s');
    $newEntry = "[$timestamp] $action";
    
    // Añadir detalles adicionales si se proporcionan
    if ($details !== null && is_array($details)) {
        if (isset($details['id'])) {
            $newEntry .= " (ID: {$details['id']})";
        }
        
        if (isset($details['date'])) {
            $formattedDate = date('d/m/Y H:i', strtotime($details['date']));
            $newEntry .= " - Fecha: $formattedDate";
        }
        
        if (isset($details['extra']) && !empty($details['extra'])) {
            $newEntry .= " - {$details['extra']}";
        }
    }
    
    // Actualizar historial
    $history = $user['history'] ? $user['history'] . "\n" . $newEntry : $newEntry;
    
    // Limitar el historial a las últimas 100 entradas para evitar un crecimiento excesivo
    $historyLines = explode("\n", $history);
    if (count($historyLines) > 100) {
        $historyLines = array_slice($historyLines, -100);
        $history = implode("\n", $historyLines);
    }
    
    $sql = "UPDATE users SET history = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $history, $userId);
    
    return mysqli_stmt_execute($stmt);
}

/**
 * Obtener usuario por ID
 */
function getUserById($id) {
    global $conn;
    
    $sql = "SELECT id, email, name, role, history, created_at FROM users WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_fetch_assoc($result);
}

/**
 * Obtener usuario por email
 */
function getUserByEmail($email) {
    global $conn;
    
    $sql = "SELECT id, email, name, role, created_at FROM users WHERE email = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_fetch_assoc($result);
}

/**
 * Generar token de restablecimiento de contraseña
 */
function generateResetToken($email) {
    global $conn;
    
    // Verificar si el usuario existe
    $user = getUserByEmail($email);
    if (!$user) {
        return ['success' => false, 'message' => 'No existe un usuario con ese correo electrónico'];
    }
    
    // Generar token único
    $token = bin2hex(random_bytes(32));
    
    // Establecer fecha de caducidad (24 horas)
    $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Guardar token en la base de datos
    $sql = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $token, $expiry, $email);
    
    if (mysqli_stmt_execute($stmt)) {
        return [
            'success' => true, 
            'message' => 'Token generado correctamente', 
            'token' => $token,
            'user_id' => $user['id'],
            'email' => $email
        ];
    } else {
        return ['success' => false, 'message' => 'Error al generar el token: ' . mysqli_error($conn)];
    }
}

/**
 * Verificar token de restablecimiento
 */
function verifyResetToken($token, $email) {
    global $conn;
    
    $sql = "SELECT id FROM users WHERE reset_token = ? AND email = ? AND reset_token_expiry > NOW()";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $token, $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    return mysqli_stmt_num_rows($stmt) > 0;
}

/**
 * Cambiar contraseña
 */
function changePassword($userId, $newPassword) {
    global $conn;
    
    // Encriptar la nueva contraseña
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Actualizar la contraseña y limpiar el token
    $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $hashedPassword, $userId);
    
    if (mysqli_stmt_execute($stmt)) {
        updateUserHistory($userId, 'Cambio de contraseña');
        return ['success' => true, 'message' => 'Contraseña actualizada correctamente'];
    } else {
        return ['success' => false, 'message' => 'Error al actualizar la contraseña: ' . mysqli_error($conn)];
    }
}

/**
 * Resetear contraseña con token
 */
function resetPassword($token, $email, $newPassword) {
    global $conn;
    
    // Verificar si el token es válido
    if (!verifyResetToken($token, $email)) {
        return ['success' => false, 'message' => 'Token inválido o caducado'];
    }
    
    // Obtener el ID del usuario
    $user = getUserByEmail($email);
    if (!$user) {
        return ['success' => false, 'message' => 'Usuario no encontrado'];
    }
    
    // Cambiar la contraseña
    return changePassword($user['id'], $newPassword);
}
?> 