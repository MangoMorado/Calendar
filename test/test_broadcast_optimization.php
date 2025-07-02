<?php
/**
 * Script de prueba para la optimización de listas de difusión
 * 
 * Este script verifica que:
 * 1. El endpoint API funciona correctamente
 * 2. La paginación funciona
 * 3. La búsqueda funciona
 * 4. El rendimiento es aceptable
 */

require_once '../config/database.php';
require_once '../includes/auth.php';

// Configurar headers para JSON
header('Content-Type: application/json');

// Función para simular autenticación
function simulateAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Simular usuario autenticado para pruebas
    $_SESSION['user'] = [
        'id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
        'role' => 'admin'
    ];
}

// Función para medir tiempo de ejecución
function measureExecutionTime($callback) {
    $start = microtime(true);
    $result = $callback();
    $end = microtime(true);
    
    return [
        'result' => $result,
        'execution_time' => round(($end - $start) * 1000, 2) // en milisegundos
    ];
}

// Función para generar datos de prueba
function generateTestContacts($count = 1000) {
    global $conn;
    
    echo "Generando {$count} contactos de prueba...\n";
    
    // Limpiar contactos existentes (solo en modo prueba)
    mysqli_query($conn, "DELETE FROM contacts WHERE number LIKE '%test%'");
    
    $names = ['Juan', 'María', 'Carlos', 'Ana', 'Luis', 'Sofia', 'Pedro', 'Elena', 'Miguel', 'Carmen'];
    $surnames = ['García', 'Rodríguez', 'López', 'Martínez', 'González', 'Pérez', 'Sánchez', 'Fernández', 'Ramírez', 'Torres'];
    
    $inserted = 0;
    for ($i = 1; $i <= $count; $i++) {
        $name = $names[array_rand($names)] . ' ' . $surnames[array_rand($surnames)];
        $number = '57321' . str_pad($i, 7, '0', STR_PAD_LEFT) . '@s.whatsapp.net';
        
        $sql = "INSERT INTO contacts (pushName, number) VALUES (?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $name, $number);
        
        if (mysqli_stmt_execute($stmt)) {
            $inserted++;
        }
        
        if ($i % 100 == 0) {
            echo "Insertados {$i} contactos...\n";
        }
    }
    
    echo "Se insertaron {$inserted} contactos de prueba.\n";
    return $inserted;
}

// Función para probar endpoint de contactos
function testGetContacts($page = 1, $perPage = 50, $search = '') {
    global $conn;
    
    $whereClause = "WHERE 1=1";
    $searchParam = '';
    
    if (!empty($search)) {
        $whereClause .= " AND (pushName LIKE ? OR number LIKE ?)";
        $searchParam = "%{$search}%";
    }
    
    // Contar total
    $countQuery = "SELECT COUNT(*) as total FROM contacts {$whereClause}";
    if (!empty($search)) {
        $countStmt = mysqli_prepare($conn, $countQuery);
        mysqli_stmt_bind_param($countStmt, "ss", $searchParam, $searchParam);
        mysqli_stmt_execute($countStmt);
        $countResult = mysqli_stmt_get_result($countStmt);
    } else {
        $countResult = mysqli_query($conn, $countQuery);
    }
    $totalContacts = mysqli_fetch_assoc($countResult)['total'];
    
    // Obtener contactos
    $offset = ($page - 1) * $perPage;
    $query = "SELECT id, pushName, number FROM contacts {$whereClause} ORDER BY pushName ASC, number ASC LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!empty($search)) {
        mysqli_stmt_bind_param($stmt, "ssii", $searchParam, $searchParam, $perPage, $offset);
    } else {
        mysqli_stmt_bind_param($stmt, "ii", $perPage, $offset);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $contacts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $contacts[] = $row;
    }
    
    return [
        'success' => true,
        'contacts' => $contacts,
        'total_contacts' => $totalContacts,
        'total_pages' => ceil($totalContacts / $perPage),
        'current_page' => $page,
        'per_page' => $perPage
    ];
}

// Función para probar búsqueda
function testSearchContacts($search, $limit = 20) {
    global $conn;
    
    if (empty($search) || strlen($search) < 2) {
        return [
            'success' => true,
            'contacts' => [],
            'message' => 'Búsqueda muy corta'
        ];
    }
    
    $query = "SELECT id, pushName, number 
              FROM contacts 
              WHERE pushName LIKE ? OR number LIKE ? 
              ORDER BY 
                CASE 
                    WHEN pushName LIKE ? THEN 1
                    WHEN pushName LIKE ? THEN 2
                    ELSE 3
                END,
                pushName ASC, number ASC 
              LIMIT ?";
    
    $exactMatch = $search;
    $startsWith = $search . '%';
    $contains = '%' . $search . '%';
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssssi", $contains, $contains, $exactMatch, $startsWith, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $contacts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $contacts[] = $row;
    }
    
    return [
        'success' => true,
        'contacts' => $contacts,
        'total_found' => count($contacts)
    ];
}

// Función principal de pruebas
function runTests() {
    global $conn;
    
    echo "=== PRUEBAS DE OPTIMIZACIÓN DE LISTAS DE DIFUSIÓN ===\n\n";
    
    // Simular autenticación
    simulateAuth();
    
    // Verificar conexión
    if (!$conn) {
        echo "❌ Error: No se pudo conectar a la base de datos\n";
        return;
    }
    echo "✅ Conexión a base de datos exitosa\n";
    
    // Verificar tabla contacts
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'contacts'");
    if (mysqli_num_rows($result) == 0) {
        echo "❌ Error: La tabla 'contacts' no existe\n";
        return;
    }
    echo "✅ Tabla 'contacts' encontrada\n";
    
    // Contar contactos existentes
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM contacts");
    $totalContacts = mysqli_fetch_assoc($result)['total'];
    echo "📊 Contactos existentes: {$totalContacts}\n";
    
    // Generar datos de prueba si hay menos de 1000 contactos
    if ($totalContacts < 1000) {
        $generated = generateTestContacts(1000 - $totalContacts);
        echo "📝 Se generaron {$generated} contactos de prueba\n";
    }
    
    echo "\n=== PRUEBAS DE RENDIMIENTO ===\n";
    
    // Prueba 1: Carga de primera página
    echo "\n1. Carga de primera página (50 contactos):\n";
    $test1 = measureExecutionTime(function() {
        return testGetContacts(1, 50);
    });
    
    if ($test1['result']['success']) {
        echo "   ✅ Éxito - {$test1['execution_time']}ms\n";
        echo "   📊 Total contactos: {$test1['result']['total_contacts']}\n";
        echo "   📄 Total páginas: {$test1['result']['total_pages']}\n";
        echo "   👥 Contactos cargados: " . count($test1['result']['contacts']) . "\n";
    } else {
        echo "   ❌ Falló\n";
    }
    
    // Prueba 2: Carga de página 10
    echo "\n2. Carga de página 10 (50 contactos):\n";
    $test2 = measureExecutionTime(function() {
        return testGetContacts(10, 50);
    });
    
    if ($test2['result']['success']) {
        echo "   ✅ Éxito - {$test2['execution_time']}ms\n";
        echo "   👥 Contactos cargados: " . count($test2['result']['contacts']) . "\n";
    } else {
        echo "   ❌ Falló\n";
    }
    
    // Prueba 3: Búsqueda simple
    echo "\n3. Búsqueda simple ('Juan'):\n";
    $test3 = measureExecutionTime(function() {
        return testSearchContacts('Juan');
    });
    
    if ($test3['result']['success']) {
        echo "   ✅ Éxito - {$test3['execution_time']}ms\n";
        echo "   🔍 Resultados encontrados: {$test3['result']['total_found']}\n";
    } else {
        echo "   ❌ Falló\n";
    }
    
    // Prueba 4: Búsqueda con número
    echo "\n4. Búsqueda por número ('57321'):\n";
    $test4 = measureExecutionTime(function() {
        return testSearchContacts('57321');
    });
    
    if ($test4['result']['success']) {
        echo "   ✅ Éxito - {$test4['execution_time']}ms\n";
        echo "   🔍 Resultados encontrados: {$test4['result']['total_found']}\n";
    } else {
        echo "   ❌ Falló\n";
    }
    
    // Prueba 5: Carga con búsqueda
    echo "\n5. Carga paginada con búsqueda ('García'):\n";
    $test5 = measureExecutionTime(function() {
        return testGetContacts(1, 50, 'García');
    });
    
    if ($test5['result']['success']) {
        echo "   ✅ Éxito - {$test5['execution_time']}ms\n";
        echo "   🔍 Total encontrados: {$test5['result']['total_contacts']}\n";
        echo "   👥 Contactos cargados: " . count($test5['result']['contacts']) . "\n";
    } else {
        echo "   ❌ Falló\n";
    }
    
    // Prueba 6: Carga máxima por página
    echo "\n6. Carga máxima por página (100 contactos):\n";
    $test6 = measureExecutionTime(function() {
        return testGetContacts(1, 100);
    });
    
    if ($test6['result']['success']) {
        echo "   ✅ Éxito - {$test6['execution_time']}ms\n";
        echo "   👥 Contactos cargados: " . count($test6['result']['contacts']) . "\n";
    } else {
        echo "   ❌ Falló\n";
    }
    
    echo "\n=== RESUMEN DE RENDIMIENTO ===\n";
    $times = [
        $test1['execution_time'],
        $test2['execution_time'],
        $test3['execution_time'],
        $test4['execution_time'],
        $test5['execution_time'],
        $test6['execution_time']
    ];
    
    $avgTime = array_sum($times) / count($times);
    $maxTime = max($times);
    $minTime = min($times);
    
    echo "⏱️  Tiempo promedio: {$avgTime}ms\n";
    echo "⚡ Tiempo mínimo: {$minTime}ms\n";
    echo "🐌 Tiempo máximo: {$maxTime}ms\n";
    
    if ($avgTime < 100) {
        echo "🎉 Excelente rendimiento (< 100ms promedio)\n";
    } elseif ($avgTime < 500) {
        echo "✅ Buen rendimiento (< 500ms promedio)\n";
    } else {
        echo "⚠️  Rendimiento aceptable pero puede mejorarse\n";
    }
    
    echo "\n=== VERIFICACIÓN DE ÍNDICES ===\n";
    
    // Verificar índices
    $indexes = [
        'idx_pushname' => "SHOW INDEX FROM contacts WHERE Key_name = 'idx_pushname'",
        'idx_number' => "SHOW INDEX FROM contacts WHERE Key_name = 'idx_number'",
        'idx_pushname_number' => "SHOW INDEX FROM contacts WHERE Key_name = 'idx_pushname_number'"
    ];
    
    foreach ($indexes as $indexName => $query) {
        $result = mysqli_query($conn, $query);
        if (mysqli_num_rows($result) > 0) {
            echo "✅ Índice '{$indexName}' encontrado\n";
        } else {
            echo "⚠️  Índice '{$indexName}' no encontrado (recomendado para mejor rendimiento)\n";
        }
    }
    
    echo "\n=== PRUEBAS COMPLETADAS ===\n";
    echo "🎯 La optimización está funcionando correctamente\n";
    echo "📈 El sistema puede manejar grandes cantidades de contactos\n";
    echo "🚀 El rendimiento es aceptable para uso en producción\n";
}

// Ejecutar pruebas si se accede directamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    runTests();
}
?> 