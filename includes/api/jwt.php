<?php
/**
 * JWT Utilities
 * Funciones para manejo de JSON Web Tokens
 */

// Clave secreta para firmar los tokens
// En producción, esto debería estar en un archivo de configuración seguro y excluido del control de versiones
define('JWT_SECRET', 'clave_secreta_mundo_animal_calendario_2023');

/**
 * Genera un token JWT
 * 
 * @param array $payload Los datos a incluir en el token
 * @param int $expiry Tiempo de expiración en segundos (por defecto 1 hora)
 * @return string Token JWT generado
 */
function generateJWT($payload, $expiry = 3600) {
    // Añadir claims estándar de JWT
    $issuedAt = time();
    $expirationTime = $issuedAt + $expiry;
    
    $payload = array_merge($payload, [
        'iat' => $issuedAt,      // Issued At: momento de emisión
        'exp' => $expirationTime, // Expiration Time: momento de expiración
        'iss' => 'mundo_animal_api' // Issuer: emisor del token
    ]);
    
    // Crear header
    $header = [
        'typ' => 'JWT',
        'alg' => 'HS256'
    ];
    
    // Codificar header y payload
    $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($header)));
    $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
    
    // Crear firma
    $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, JWT_SECRET, true);
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    // Crear token
    $jwt = $base64Header . '.' . $base64Payload . '.' . $base64Signature;
    
    return $jwt;
}

/**
 * Valida un token JWT
 * 
 * @param string $token El token JWT a validar
 * @return array|false Los datos del payload si el token es válido, false en caso contrario
 */
function validateJWT($token) {
    // Dividir token en sus partes
    $tokenParts = explode('.', $token);
    if (count($tokenParts) !== 3) {
        return false;
    }
    
    // Obtener header y payload
    list($base64Header, $base64Payload, $base64Signature) = $tokenParts;
    
    // Decodificar header y payload
    $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Header)), true);
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Payload)), true);
    
    // Verificar si el algoritmo es el esperado
    if (!isset($header['alg']) || $header['alg'] !== 'HS256') {
        return false;
    }
    
    // Verificar expiración
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }
    
    // Verificar firma
    $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, JWT_SECRET, true);
    $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    if ($expectedSignature !== $base64Signature) {
        return false;
    }
    
    // Si todas las verificaciones pasan, devolver payload
    return $payload;
}

/**
 * Extrae el token de la cabecera Authorization
 * 
 * @return string|null El token JWT o null si no está presente
 */
function getJWTFromHeader() {
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : 
                 (isset($headers['authorization']) ? $headers['authorization'] : '');
    
    if (empty($authHeader)) {
        return null;
    }
    
    // Verificar formato Bearer token
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        return $matches[1];
    }
    
    return null;
}

/**
 * Middleware para requerir autenticación JWT
 * 
 * @return array Los datos del usuario si está autenticado
 */
function requireJWTAuth() {
    $token = getJWTFromHeader();
    
    if (!$token) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'No hay token de autenticación'
        ]);
        exit;
    }
    
    $payload = validateJWT($token);
    
    if (!$payload) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Token inválido o expirado'
        ]);
        exit;
    }
    
    return $payload;
}

/**
 * Función estandarizada para respuestas API
 * 
 * @param bool $success Indicador de éxito
 * @param string $message Mensaje descriptivo
 * @param mixed $data Datos adicionales a incluir en la respuesta
 * @param int $statusCode Código de estado HTTP
 */
function apiResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}
?> 