<?php
/**
 * Script de prueba para el sistema de difusiones con n8n
 * Este script verifica que todos los componentes funcionen correctamente
 */

require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../models/BroadcastHistoryModel.php';
require_once __DIR__.'/../models/BroadcastListModel.php';

echo "=== PRUEBA DEL SISTEMA DE DIFUSIONES CON N8N ===\n\n";

// 1. Verificar conexión a base de datos
echo "1. Verificando conexión a base de datos...\n";
if ($conn) {
    echo "✅ Conexión a base de datos exitosa\n";
} else {
    echo "❌ Error de conexión a base de datos\n";
    exit(1);
}

// 2. Verificar configuración de n8n
echo "\n2. Verificando configuración de n8n...\n";
$config = [];
$sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('n8n_url', 'n8n_api_key', 'n8n_broadcast_webhook_url', 'n8n_evolution_api_url', 'n8n_evolution_api_key', 'n8n_evolution_instance_name')";
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $config[$row['setting_key']] = $row['setting_value'];
    }

    $requiredConfigs = [
        'n8n_url' => 'URL de n8n',
        'n8n_api_key' => 'API Key de n8n',
        'n8n_broadcast_webhook_url' => 'Webhook URL de n8n',
        'n8n_evolution_api_url' => 'URL de Evolution API',
        'n8n_evolution_api_key' => 'API Key de Evolution API',
        'n8n_evolution_instance_name' => 'Nombre de instancia',
    ];

    $configOk = true;
    foreach ($requiredConfigs as $key => $description) {
        if (empty($config[$key])) {
            echo "❌ Falta configuración: $description ($key)\n";
            $configOk = false;
        } else {
            echo "✅ $description configurado\n";
        }
    }

    if (! $configOk) {
        echo "\n⚠️  Algunas configuraciones están faltando. Ejecuta el script SQL de actualización.\n";
    }
} else {
    echo "❌ Error al obtener configuración de n8n\n";
}

// 3. Verificar estructura de tablas
echo "\n3. Verificando estructura de tablas...\n";
$tables = ['broadcast_history', 'broadcast_details', 'n8n_broadcast_logs', 'broadcast_rate_limits'];
foreach ($tables as $table) {
    $sql = "SHOW TABLES LIKE '$table'";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
        echo "✅ Tabla $table existe\n";
    } else {
        echo "❌ Tabla $table no existe\n";
    }
}

// 4. Verificar estados de difusiones
echo "\n4. Verificando estados de difusiones...\n";
$sql = "SHOW COLUMNS FROM broadcast_history LIKE 'status'";
$result = mysqli_query($conn, $sql);
if ($result && $row = mysqli_fetch_assoc($result)) {
    $type = $row['Type'];
    if (strpos($type, 'queued') !== false) {
        echo "✅ Estados de difusiones actualizados (incluye 'queued')\n";
    } else {
        echo "❌ Estados de difusiones no actualizados\n";
    }
} else {
    echo "❌ Error al verificar estados de difusiones\n";
}

// 5. Verificar endpoints
echo "\n5. Verificando endpoints...\n";
$endpoints = [
    'api/send_broadcast_n8n.php',
    'api/broadcast_callback.php',
    'api/get_broadcast_status.php',
];

foreach ($endpoints as $endpoint) {
    if (file_exists(__DIR__.'/../'.$endpoint)) {
        echo "✅ Endpoint $endpoint existe\n";
    } else {
        echo "❌ Endpoint $endpoint no existe\n";
    }
}

// 6. Verificar workflow de n8n
echo "\n6. Verificando workflow de n8n...\n";
$workflowFile = __DIR__.'/../docs/n8n/BroadcastManager.json';
if (file_exists($workflowFile)) {
    $workflowContent = file_get_contents($workflowFile);
    $workflow = json_decode($workflowContent, true);

    if ($workflow && isset($workflow['name'])) {
        echo '✅ Workflow de n8n encontrado: '.$workflow['name']."\n";

        // Verificar nodos importantes
        $requiredNodes = ['webhook-receiver', 'validate-input', 'create-batches', 'send-text', 'send-media'];
        $nodes = array_column($workflow['nodes'], 'id');

        foreach ($requiredNodes as $node) {
            if (in_array($node, $nodes)) {
                echo "  ✅ Nodo $node presente\n";
            } else {
                echo "  ❌ Nodo $node faltante\n";
            }
        }
    } else {
        echo "❌ Workflow de n8n inválido\n";
    }
} else {
    echo "❌ Archivo de workflow de n8n no encontrado\n";
}

// 7. Verificar conectividad con n8n
echo "\n7. Verificando conectividad con n8n...\n";
if (! empty($config['n8n_url'])) {
    $n8nUrl = rtrim($config['n8n_url'], '/').'/healthz';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $n8nUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        echo "✅ n8n está respondiendo correctamente\n";
    } else {
        echo "❌ n8n no responde (HTTP $httpCode)\n";
    }
} else {
    echo "⚠️  URL de n8n no configurada\n";
}

// 8. Verificar conectividad con Evolution API
echo "\n8. Verificando conectividad con Evolution API...\n";
if (! empty($config['n8n_evolution_api_url']) && ! empty($config['n8n_evolution_api_key'])) {
    $evolutionUrl = rtrim($config['n8n_evolution_api_url'], '/').'/instance/fetchInstances';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $evolutionUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['apikey: '.$config['n8n_evolution_api_key']]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        echo "✅ Evolution API está respondiendo correctamente\n";

        // Verificar instancia específica
        if (! empty($config['n8n_evolution_instance_name'])) {
            $instanceName = $config['n8n_evolution_instance_name'];
            $instances = json_decode($response, true);

            $instanceFound = false;
            if (is_array($instances)) {
                foreach ($instances as $instance) {
                    if (isset($instance['instance']) && $instance['instance'] === $instanceName) {
                        $instanceFound = true;
                        $state = $instance['connectionStatus'] ?? 'unknown';
                        echo "✅ Instancia '$instanceName' encontrada (Estado: $state)\n";
                        break;
                    }
                }
            }

            if (! $instanceFound) {
                echo "❌ Instancia '$instanceName' no encontrada\n";
            }
        }
    } else {
        echo "❌ Evolution API no responde (HTTP $httpCode)\n";
    }
} else {
    echo "⚠️  Configuración de Evolution API incompleta\n";
}

// 9. Verificar modelos
echo "\n9. Verificando modelos...\n";
try {
    $broadcastHistoryModel = new BroadcastHistoryModel($conn);
    echo "✅ BroadcastHistoryModel cargado correctamente\n";
} catch (Exception $e) {
    echo '❌ Error al cargar BroadcastHistoryModel: '.$e->getMessage()."\n";
}

try {
    $broadcastListModel = new BroadcastListModel($conn);
    echo "✅ BroadcastListModel cargado correctamente\n";
} catch (Exception $e) {
    echo '❌ Error al cargar BroadcastListModel: '.$e->getMessage()."\n";
}

// 10. Resumen final
echo "\n=== RESUMEN DE LA PRUEBA ===\n";
echo "Para completar la configuración:\n\n";

echo "1. Ejecutar el script SQL de actualización:\n";
echo "   mysql -u [usuario] -p [base_datos] < config/update_broadcast_system.sql\n\n";

echo "2. Configurar n8n:\n";
echo "   - Importar el workflow: docs/n8n/BroadcastManager.json\n";
echo "   - Configurar credenciales de Evolution API\n";
echo "   - Activar el workflow\n";
echo "   - Copiar la URL del webhook\n\n";

echo "3. Actualizar configuración en la base de datos:\n";
echo "   - n8n_url\n";
echo "   - n8n_api_key\n";
echo "   - n8n_broadcast_webhook_url\n";
echo "   - n8n_evolution_api_url\n";
echo "   - n8n_evolution_api_key\n";
echo "   - n8n_evolution_instance_name\n\n";

echo "4. Probar el sistema:\n";
echo "   - Crear una difusión de prueba\n";
echo "   - Verificar que se envíe a n8n\n";
echo "   - Monitorear el progreso\n\n";

echo "Documentación completa: docs/N8N_BROADCAST_SYSTEM.md\n";
echo "¡Sistema listo para usar!\n";
?> 