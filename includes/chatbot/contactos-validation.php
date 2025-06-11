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
 * Filtra un array de contactos y devuelve solo los que tienen números válidos
 * @param array $contactos Array de contactos
 * @return array Array con solo contactos válidos
 */
function filtrarContactosValidos($contactos) {
    $contactosValidos = [];
    
    foreach ($contactos as $contacto) {
        // Extraer número sin dominio WhatsApp
        $numero = explode('@', $contacto['number'])[0];
        
        if (validarNumeroTelefono($numero)) {
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
        $numero = explode('@', $contacto['number'])[0];
        if (validarNumeroTelefono($numero)) {
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