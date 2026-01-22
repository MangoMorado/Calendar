<?php
require_once __DIR__.'/../config/database.php';

echo '<h2>Corrigiendo Configuración de Sesiones Problemática</h2>';

// Configuración CORRECTA para sesiones en producción
$correctSessionSettings = [
    'session_timeout' => '3600',           // 1 hora en segundos (sesiones normales)
    'remember_me_timeout' => '604800',     // 7 días en segundos (recordar equipo)
    'max_sessions_per_user' => '5',        // Máximo 5 sesiones por usuario
    'require_login_on_visit' => '1',       // 1 = sí, requiere login en cada visita
    'session_cleanup_interval' => '86400',  // 24 horas en segundos (limpieza automática)
];

echo '<h3>Configuración Actual (Problemática):</h3>';
$currentSettings = 'SELECT setting_key, setting_value FROM session_settings ORDER BY setting_key';
$result = mysqli_query($conn, $currentSettings);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo '<tr><th>Configuración</th><th>Valor Actual</th><th>Estado</th></tr>';
    while ($row = mysqli_fetch_assoc($result)) {
        $key = $row['setting_key'];
        $value = $row['setting_value'];
        $status = '';

        if ($key === 'session_timeout' && $value === '0') {
            $status = '❌ PROBLEMÁTICO - Sin expiración';
        } elseif ($key === 'remember_me_timeout' && $value === '-1') {
            $status = '❌ PROBLEMÁTICO - Inválido';
        } elseif ($key === 'max_sessions_per_user' && $value === '0') {
            $status = '❌ PROBLEMÁTICO - Sin límite';
        } else {
            $status = '✅ Correcto';
        }

        echo '<tr>';
        echo '<td>'.htmlspecialchars($key).'</td>';
        echo '<td>'.htmlspecialchars($value).'</td>';
        echo '<td>'.$status.'</td>';
        echo '</tr>';
    }
    echo '</table>';
}

echo '<h3>Configuración Nueva (Corregida):</h3>';
echo "<table border='1' style='border-collapse: collapse;'>";
echo '<tr><th>Configuración</th><th>Valor Nuevo</th><th>Descripción</th></tr>';
foreach ($correctSessionSettings as $key => $value) {
    $description = '';
    switch ($key) {
        case 'session_timeout':
            $description = 'Tiempo de expiración de sesiones normales (1 hora)';
            break;
        case 'remember_me_timeout':
            $description = 'Tiempo de expiración para recordar equipo (7 días)';
            break;
        case 'max_sessions_per_user':
            $description = 'Máximo número de sesiones activas por usuario';
            break;
        case 'require_login_on_visit':
            $description = 'Requiere login en cada visita (1 = sí, 0 = no)';
            break;
        case 'session_cleanup_interval':
            $description = 'Intervalo de limpieza automática de sesiones (24 horas)';
            break;
    }

    echo '<tr>';
    echo '<td>'.htmlspecialchars($key).'</td>';
    echo '<td>'.htmlspecialchars($value).'</td>';
    echo '<td>'.$description.'</td>';
    echo '</tr>';
}
echo '</table>';

echo '<h3>Actualizando configuración:</h3>';

$updateCount = 0;
$errorCount = 0;

foreach ($correctSessionSettings as $key => $value) {
    $updateSql = 'UPDATE session_settings SET setting_value = ? WHERE setting_key = ?';
    $stmt = mysqli_prepare($conn, $updateSql);
    mysqli_stmt_bind_param($stmt, 'ss', $value, $key);

    if (mysqli_stmt_execute($stmt)) {
        echo "✅ $key actualizado a: $value<br>";
        $updateCount++;
    } else {
        echo "❌ Error actualizando $key: ".mysqli_error($conn).'<br>';
        $errorCount++;
    }
}

// Verificar que se actualizó correctamente
echo '<h3>Verificando configuración actualizada:</h3>';
$verifySql = 'SELECT setting_key, setting_value FROM session_settings ORDER BY setting_key';
$result = mysqli_query($conn, $verifySql);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo '<tr><th>Configuración</th><th>Valor</th><th>Estado</th></tr>';
    while ($row = mysqli_fetch_assoc($result)) {
        $key = $row['setting_key'];
        $value = $row['setting_value'];
        $status = '';

        if (isset($correctSessionSettings[$key])) {
            if ($value === $correctSessionSettings[$key]) {
                $status = '✅ Correcto';
            } else {
                $status = '❌ No se actualizó correctamente';
            }
        } else {
            $status = '⚠️ Configuración adicional';
        }

        echo '<tr>';
        echo '<td>'.htmlspecialchars($key).'</td>';
        echo '<td>'.htmlspecialchars($value).'</td>';
        echo '<td>'.$status.'</td>';
        echo '</tr>';
    }
    echo '</table>';
}

echo '<h3>Resumen de la corrección:</h3>';
echo "✅ Configuraciones actualizadas: $updateCount<br>";
if ($errorCount > 0) {
    echo "❌ Errores encontrados: $errorCount<br>";
} else {
    echo '✅ No se encontraron errores<br>';
}

echo '<h3>Beneficios de la nueva configuración:</h3>';
echo '1. <strong>Seguridad mejorada</strong>: Las sesiones expiran automáticamente<br>';
echo '2. <strong>Control de sesiones</strong>: Límite de 5 sesiones por usuario<br>';
echo '3. <strong>Limpieza automática</strong>: Sesiones expiradas se eliminan cada 24 horas<br>';
echo '4. <strong>Flexibilidad</strong>: Opción de recordar equipo por 7 días<br>';
echo '5. <strong>Estabilidad</strong>: No más sesiones infinitas o inválidas<br>';

echo '<h3>Próximos pasos:</h3>';
echo '1. Ejecuta este script para aplicar los cambios<br>';
echo '2. Prueba el sistema de autenticación<br>';
echo '3. Verifica que las sesiones funcionan correctamente<br>';
echo '4. Si hay problemas, puedes ajustar los valores según tus necesidades<br>';

echo '<h3>Valores recomendados para diferentes entornos:</h3>';
echo '<strong>Desarrollo:</strong> session_timeout = 7200 (2 horas), remember_me_timeout = 1209600 (14 días)<br>';
echo '<strong>Producción:</strong> session_timeout = 3600 (1 hora), remember_me_timeout = 604800 (7 días)<br>';
echo '<strong>Alta seguridad:</strong> session_timeout = 1800 (30 min), remember_me_timeout = 259200 (3 días)<br>';
?> 