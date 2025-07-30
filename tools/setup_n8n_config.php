<?php
/**
 * Script para configurar automáticamente la configuración de n8n
 * Ejecutar desde el navegador: tools/setup_n8n_config.php
 */

// Activar reporte de errores
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

echo "<h1>Configuración Automática de n8n</h1>";

try {
    require_once '../config/database.php';
    echo "✅ Conexión a base de datos: OK<br><br>";
    
    // Verificar configuración actual
    $sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('n8n_url', 'n8n_api_key', 'n8n_broadcast_webhook_url')";
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        echo "❌ Error al consultar configuración: " . mysqli_error($conn) . "<br>";
        exit;
    }
    
    $config = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $config[$row['setting_key']] = $row['setting_value'];
    }
    
    echo "<h2>Configuración Actual</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Configuración</th><th>Valor</th><th>Estado</th></tr>";
    
    $n8nUrl = $config['n8n_url'] ?? '';
    $n8nApiKey = $config['n8n_api_key'] ?? '';
    $n8nWebhookUrl = $config['n8n_broadcast_webhook_url'] ?? '';
    
    echo "<tr><td>n8n_url</td><td>" . htmlspecialchars($n8nUrl) . "</td><td>" . (!empty($n8nUrl) ? "✅" : "❌") . "</td></tr>";
    echo "<tr><td>n8n_api_key</td><td>" . (!empty($n8nApiKey) ? "Configurado" : "No configurado") . "</td><td>" . (!empty($n8nApiKey) ? "✅" : "❌") . "</td></tr>";
    echo "<tr><td>n8n_broadcast_webhook_url</td><td>" . htmlspecialchars($n8nWebhookUrl) . "</td><td>" . (!empty($n8nWebhookUrl) ? "✅" : "❌") . "</td></tr>";
    echo "</table><br>";
    
    // Si ya está configurado, mostrar opciones
    if (!empty($n8nUrl) && !empty($n8nApiKey) && !empty($n8nWebhookUrl)) {
        echo "✅ La configuración de n8n ya está completa.<br>";
        echo "<a href='../test/test_n8n_config.php'>Verificar conectividad</a><br><br>";
        exit;
    }
    
    // Formulario para configurar
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $newN8nUrl = trim($_POST['n8n_url'] ?? '');
        $newN8nApiKey = trim($_POST['n8n_api_key'] ?? '');
        $newN8nWebhookUrl = trim($_POST['n8n_broadcast_webhook_url'] ?? '');
        
        if (empty($newN8nUrl) || empty($newN8nApiKey) || empty($newN8nWebhookUrl)) {
            echo "❌ Todos los campos son requeridos.<br><br>";
        } else {
            // Guardar configuración
            $settings = [
                'n8n_url' => $newN8nUrl,
                'n8n_api_key' => $newN8nApiKey,
                'n8n_broadcast_webhook_url' => $newN8nWebhookUrl
            ];
            
            foreach ($settings as $key => $value) {
                $sql = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ss", $key, $value);
                
                if (mysqli_stmt_execute($stmt)) {
                    echo "✅ Configuración '$key' guardada correctamente.<br>";
                } else {
                    echo "❌ Error al guardar '$key': " . mysqli_error($conn) . "<br>";
                }
            }
            
            echo "<br>✅ Configuración completada. <a href='../test/test_n8n_config.php'>Verificar conectividad</a><br><br>";
            exit;
        }
    }
    
    // Mostrar formulario
    echo "<h2>Configurar n8n</h2>";
    echo "<form method='post' style='max-width: 600px;'>";
    echo "<div style='margin-bottom: 15px;'>";
    echo "<label for='n8n_url'><strong>URL de n8n:</strong></label><br>";
    echo "<input type='url' id='n8n_url' name='n8n_url' value='" . htmlspecialchars($n8nUrl) . "' style='width: 100%; padding: 8px;' placeholder='https://tu-n8n.com' required><br>";
    echo "<small>Ejemplo: https://n8n.tudominio.com</small>";
    echo "</div>";
    
    echo "<div style='margin-bottom: 15px;'>";
    echo "<label for='n8n_api_key'><strong>API Key de n8n:</strong></label><br>";
    echo "<input type='password' id='n8n_api_key' name='n8n_api_key' value='" . htmlspecialchars($n8nApiKey) . "' style='width: 100%; padding: 8px;' required><br>";
    echo "<small>Encuentra tu API key en n8n: Settings > API</small>";
    echo "</div>";
    
    echo "<div style='margin-bottom: 15px;'>";
    echo "<label for='n8n_broadcast_webhook_url'><strong>URL del Webhook de Difusiones:</strong></label><br>";
    echo "<input type='url' id='n8n_broadcast_webhook_url' name='n8n_broadcast_webhook_url' value='" . htmlspecialchars($n8nWebhookUrl) . "' style='width: 100%; padding: 8px;' placeholder='https://tu-n8n.com/webhook/broadcast' required><br>";
    echo "<small>URL del webhook de n8n para procesar difusiones</small>";
    echo "</div>";
    
    echo "<button type='submit' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'>Guardar Configuración</button>";
    echo "</form>";
    
    echo "<br><h3>Instrucciones para obtener la configuración:</h3>";
    echo "<ol>";
    echo "<li><strong>URL de n8n:</strong> Es la URL donde tienes instalado n8n (ej: https://n8n.tudominio.com)</li>";
    echo "<li><strong>API Key:</strong> Ve a n8n > Settings > API y genera una nueva API key</li>";
    echo "<li><strong>Webhook URL:</strong> En tu workflow de n8n, crea un nodo webhook y copia la URL generada</li>";
    echo "</ol>";
    
    echo "<br><h3>Ejemplo de workflow de n8n:</h3>";
    echo "<pre>";
    echo "Webhook (Trigger) → Procesar datos → Evolution API (Send Message) → Respuesta\n";
    echo "</pre>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
}

echo "<br><hr>";
echo "<p><strong>Nota:</strong> Después de configurar, verifica la conectividad con el script de prueba.</p>";
?> 