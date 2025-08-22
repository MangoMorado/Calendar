<?php
/**
 * Funciones de validación de números de teléfono
 */

/**
 * Valida si un número de teléfono es válido según indicativos de países
 * @param string $numero Número de teléfono a validar
 * @return bool True si es válido, False si no
 */
function validarNumeroTelefono($numero) {
    // Remover espacios, guiones y otros caracteres
    $numeroLimpio = preg_replace('/[\s\-\(\)]/', '', $numero);
    
    // Verificar que solo contenga dígitos
    if (!preg_match('/^\d+$/', $numeroLimpio)) {
        return false;
    }
    
    // Validar indicativos de países comunes y longitud
    $indicativos = [
        // Colombia
        '57' => ['minLength' => 10, 'maxLength' => 10],
        // México
        '52' => ['minLength' => 10, 'maxLength' => 10],
        // España
        '34' => ['minLength' => 9, 'maxLength' => 9],
        // Argentina
        '54' => ['minLength' => 10, 'maxLength' => 11],
        // Chile
        '56' => ['minLength' => 9, 'maxLength' => 9],
        // Perú
        '51' => ['minLength' => 9, 'maxLength' => 9],
        // Ecuador
        '593' => ['minLength' => 9, 'maxLength' => 9],
        // Venezuela
        '58' => ['minLength' => 10, 'maxLength' => 10],
        // Estados Unidos/Canadá
        '1' => ['minLength' => 10, 'maxLength' => 10]
    ];
    
    // Verificar indicativos de 1, 2 o 3 dígitos
    for ($i = 1; $i <= 3; $i++) {
        $indicativo = substr($numeroLimpio, 0, $i);
        if (isset($indicativos[$indicativo])) {
            $longitudRestante = strlen($numeroLimpio) - $i;
            $config = $indicativos[$indicativo];
            
            if ($longitudRestante >= $config['minLength'] && $longitudRestante <= $config['maxLength']) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Valida si un número de WhatsApp es válido (número + dominio)
 * @param string $numeroCompleto Número completo con dominio WhatsApp
 * @return bool True si es válido, False si no
 */
function validarNumeroWhatsApp($numeroCompleto) {
    // Verificar que tenga el formato correcto de WhatsApp
    if (!preg_match('/^(\d+)@s\.whatsapp\.net$/', $numeroCompleto, $matches)) {
        return false;
    }
    
    $numero = $matches[1];
    
    // Validar que el número sea un teléfono válido
    if (!validarNumeroTelefono($numero)) {
        return false;
    }
    
    // Validaciones adicionales de seguridad
    // 1. No puede empezar con 0 (excepto algunos países específicos)
    if (strlen($numero) > 1 && substr($numero, 0, 1) === '0') {
        // Solo permitir 0 al inicio para países específicos
        $indicativo = substr($numero, 1, 2);
        $paisesConCero = ['57', '52', '54', '58']; // Colombia, México, Argentina, Venezuela
        if (!in_array($indicativo, $paisesConCero)) {
            return false;
        }
    }
    
    // 2. No puede ser un número de prueba o inválido
    $numerosInvalidos = [
        '0000000000', '1111111111', '2222222222', '3333333333',
        '4444444444', '5555555555', '6666666666', '7777777777',
        '8888888888', '9999999999', '1234567890', '0987654321'
    ];
    
    if (in_array($numero, $numerosInvalidos)) {
        return false;
    }
    
    // 3. Verificar que no sea un número de sistema o interno
    if (strlen($numero) > 15 || strlen($numero) < 8) {
        return false;
    }
    
    return true;
}

/**
 * Limpia y valida un número de WhatsApp antes de procesarlo
 * @param string $numeroCompleto Número completo con dominio
 * @return array ['valid' => bool, 'clean_number' => string, 'error' => string]
 */
function limpiarYValidarNumeroWhatsApp($numeroCompleto) {
    $resultado = [
        'valid' => false,
        'clean_number' => '',
        'error' => ''
    ];
    
    // Verificar formato básico
    if (!str_contains($numeroCompleto, '@s.whatsapp.net')) {
        $resultado['error'] = 'Formato de WhatsApp inválido';
        return $resultado;
    }
    
    // Extraer solo el número
    $numero = explode('@', $numeroCompleto)[0];
    
    // Validar que sea un número válido
    if (!validarNumeroWhatsApp($numeroCompleto)) {
        $resultado['error'] = 'Número de teléfono inválido';
        return $resultado;
    }
    
    $resultado['valid'] = true;
    $resultado['clean_number'] = $numeroCompleto;
    return $resultado;
}

/**
 * Filtra un array de contactos y devuelve solo los que tienen números válidos
 * @param array $contactos Array de contactos
 * @return array Array con solo contactos válidos
 */
function filtrarContactosValidos($contactos) {
    $contactosValidos = [];
    
    foreach ($contactos as $contacto) {
        // Validar número completo de WhatsApp
        if (validarNumeroWhatsApp($contacto['number'])) {
            $contactosValidos[] = $contacto;
        }
    }
    
    return $contactosValidos;
}

/**
 * Obtiene estadísticas de validación de contactos
 * @param array $contactos Array de contactos
 * @return array Array con estadísticas
 */
function obtenerEstadisticasContactos($contactos) {
    $total = count($contactos);
    $validos = 0;
    $invalidos = 0;
    
    foreach ($contactos as $contacto) {
        if (validarNumeroWhatsApp($contacto['number'])) {
            $validos++;
        } else {
            $invalidos++;
        }
    }
    
    return [
        'total' => $total,
        'validos' => $validos,
        'invalidos' => $invalidos
    ];
}
?> 