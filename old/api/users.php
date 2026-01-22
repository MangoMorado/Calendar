<?php

/**
 * API para obtener usuarios
 * Devuelve la lista de usuarios activos (con calendar_visible=1) en formato JSON
 */

// Activar registro de errores
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__).'/../php_error.log');
error_reporting(E_ALL);

// Configurar cabeceras para JSON
header('Content-Type: application/json');

// Incluir archivos de configuración y autenticación
require_once '../config/database.php';
require_once '../includes/auth.php';

// Función para verificar la conexión a la base de datos
function checkDatabaseConnection()
{
    global $conn;
    if (! $conn) {
        error_log('Error de conexión a la base de datos: '.mysqli_connect_error());

        return false;
    }

    return true;
}

// Función para obtener usuarios
function getUsers()
{
    global $conn;

    // Verificar la conexión a la base de datos
    if (! checkDatabaseConnection()) {
        return [
            'error' => 'Error de conexión',
            'message' => 'No se pudo conectar a la base de datos',
        ];
    }

    // Obtener todos los usuarios con calendario visible
    $query = "SELECT id, name, COALESCE(color, '#3788d8') as color FROM users WHERE calendar_visible = 1";
    $result = mysqli_query($conn, $query);

    if (! $result) {
        error_log('Error en la consulta de usuarios: '.mysqli_error($conn));

        return [
            'error' => 'Error de consulta',
            'message' => 'Ocurrió un error al consultar la base de datos',
        ];
    }

    // Si no hay usuarios, realizar una consulta de depuración para ver cuántos usuarios hay en total
    if (mysqli_num_rows($result) === 0) {
        error_log('No se encontraron usuarios con calendar_visible=1');
        $debugQuery = 'SELECT COUNT(*) as total FROM users';
        $debugResult = mysqli_query($conn, $debugQuery);
        if ($debugResult) {
            $row = mysqli_fetch_assoc($debugResult);
            error_log('Total de usuarios en la base de datos: '.$row['total']);
        }

        // Consulta para ver el estado de calendar_visible en todos los usuarios
        $statusQuery = 'SELECT id, name, calendar_visible FROM users';
        $statusResult = mysqli_query($conn, $statusQuery);
        if ($statusResult) {
            while ($user = mysqli_fetch_assoc($statusResult)) {
                error_log('Usuario ID: '.$user['id'].', Nombre: '.$user['name'].
                          ', calendar_visible: '.$user['calendar_visible']);
            }
        }
    }

    // Preparar array de usuarios
    $users = [];
    while ($user = mysqli_fetch_assoc($result)) {
        // Asegurar que el usuario tiene todos los campos necesarios
        if (! isset($user['id']) || ! isset($user['name'])) {
            error_log('Usuario incompleto: '.json_encode($user));

            continue; // Saltar este usuario
        }

        // Asegurar que el id sea un entero
        $user['id'] = (int) $user['id'];

        // Asegurar que hay un color
        if (! isset($user['color']) || empty($user['color'])) {
            $user['color'] = '#3788d8'; // Color por defecto
        }

        $users[] = $user;
    }

    error_log('API: Se encontraron '.count($users).' usuarios válidos con calendar_visible=1');

    return $users;
}

// Verificar que el usuario esté autenticado
if (sessionExists()) {
    // Obtener y devolver usuarios
    $result = getUsers();

    // Si hay un error, devolver mensaje de error
    if (isset($result['error'])) {
        http_response_code(500);
    }

    // Devolver resultado como JSON
    echo json_encode($result);
} else {
    // El usuario no está autenticado
    http_response_code(401);
    echo json_encode([
        'error' => 'No autorizado',
        'message' => 'Debe iniciar sesión para acceder a esta API',
    ]);
}
