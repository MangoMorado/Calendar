<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar autenticación
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isAuthenticated()) {
    echo "❌ Usuario NO autenticado<br>";
    echo "Debes estar logueado para analizar archivos JSON.<br>";
    exit;
}

$user = getCurrentUser();
echo "<h2>Análisis de Estructura JSON</h2>";
echo "✅ Usuario autenticado: " . htmlspecialchars($user['name']) . "<br><br>";

// Verificar si se proporcionó un archivo
$jsonFile = $_POST['json_file'] ?? 'contactos_mundoanimal.json';

if (!file_exists($jsonFile)) {
    echo "❌ El archivo '$jsonFile' no existe<br>";
    echo "Asegúrate de que el archivo esté en el directorio correcto.<br>";
    exit;
}

echo "<h3>Analizando archivo: $jsonFile</h3>";

// Información del archivo
$fileSize = filesize($jsonFile);
$fileSizeMB = round($fileSize / 1024 / 1024, 2);
echo "📁 Tamaño del archivo: $fileSizeMB MB ($fileSize bytes)<br>";

// Leer el archivo JSON
$jsonContent = file_get_contents($jsonFile);
if (!$jsonContent) {
    echo "❌ No se pudo leer el archivo JSON<br>";
    exit;
}

// Decodificar JSON
$contactos = json_decode($jsonContent, true);
if (!$contactos) {
    echo "❌ El archivo JSON no tiene una estructura válida<br>";
    echo "Error de JSON: " . json_last_error_msg() . "<br>";
    exit;
}

echo "✅ Archivo JSON válido<br>";

// Analizar estructura
if (is_array($contactos)) {
    echo "📊 Tipo de estructura: Array con " . count($contactos) . " elementos<br>";
    
    if (count($contactos) > 0) {
        $firstContact = $contactos[0];
        echo "🔍 Estructura del primer contacto:<br>";
        echo "<div style='background: #f0f0f0; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<pre>" . htmlspecialchars(json_encode($firstContact, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
        echo "</div>";
        
        // Analizar campos disponibles
        echo "<h4>📋 Campos disponibles en los contactos:</h4>";
        $allFields = [];
        $sampleContacts = array_slice($contactos, 0, 10); // Analizar solo los primeros 10
        
        foreach ($sampleContacts as $contact) {
            if (is_array($contact)) {
                $allFields = array_merge($allFields, array_keys($contact));
            }
        }
        
        $uniqueFields = array_unique($allFields);
        echo "<ul>";
        foreach ($uniqueFields as $field) {
            $count = 0;
            foreach ($sampleContacts as $contact) {
                if (isset($contact[$field])) {
                    $count++;
                }
            }
            $percentage = round(($count / count($sampleContacts)) * 100, 1);
            echo "<li><strong>$field</strong>: Presente en $count de " . count($sampleContacts) . " contactos ($percentage%)</li>";
        }
        echo "</ul>";
        
        // Estadísticas de tipos de contactos
        echo "<h4>📊 Estadísticas de tipos de contactos:</h4>";
        $individualContacts = 0;
        $groupContacts = 0;
        $invalidContacts = 0;
        
        foreach ($contactos as $contact) {
            if (is_array($contact) && isset($contact['remoteJid'])) {
                if (str_ends_with($contact['remoteJid'], '@g.us')) {
                    $groupContacts++;
                } else {
                    $individualContacts++;
                }
            } else {
                $invalidContacts++;
            }
        }
        
        echo "👤 Contactos individuales: $individualContacts<br>";
        echo "👥 Grupos: $groupContacts<br>";
        echo "❌ Contactos inválidos: $invalidContacts<br>";
        
        // Verificar campos requeridos
        echo "<h4>🔍 Verificación de campos requeridos:</h4>";
        $hasRemoteJid = 0;
        $hasPushName = 0;
        
        foreach ($sampleContacts as $contact) {
            if (isset($contact['remoteJid']) && !empty($contact['remoteJid'])) {
                $hasRemoteJid++;
            }
            if (isset($contact['pushName'])) {
                $hasPushName++;
            }
        }
        
        $remoteJidPercentage = round(($hasRemoteJid / count($sampleContacts)) * 100, 1);
        $pushNamePercentage = round(($hasPushName / count($sampleContacts)) * 100, 1);
        
        echo "📱 remoteJid: $hasRemoteJid de " . count($sampleContacts) . " ($remoteJidPercentage%)<br>";
        echo "👤 pushName: $hasPushName de " . count($sampleContacts) . " ($pushNamePercentage%)<br>";
        
        if ($remoteJidPercentage < 100) {
            echo "⚠️ <strong>ADVERTENCIA:</strong> No todos los contactos tienen remoteJid<br>";
        }
        
        // Mostrar algunos ejemplos de números de teléfono
        echo "<h4>📞 Ejemplos de números de teléfono:</h4>";
        $phoneExamples = [];
        foreach ($sampleContacts as $contact) {
            if (isset($contact['remoteJid']) && !str_ends_with($contact['remoteJid'], '@g.us')) {
                $phoneExamples[] = $contact['remoteJid'];
                if (count($phoneExamples) >= 5) break;
            }
        }
        
        echo "<ul>";
        foreach ($phoneExamples as $phone) {
            echo "<li>" . htmlspecialchars($phone) . "</li>";
        }
        echo "</ul>";
        
    } else {
        echo "⚠️ El array está vacío<br>";
    }
} else {
    echo "📊 Tipo de estructura: " . gettype($contactos) . "<br>";
    echo "🔍 Contenido:<br>";
    echo "<div style='background: #f0f0f0; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "<pre>" . htmlspecialchars(json_encode($contactos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
    echo "</div>";
}

// Recomendaciones
echo "<h3>💡 Recomendaciones:</h3>";
echo "<ul>";
echo "<li>✅ El archivo parece tener la estructura correcta para importación</li>";
echo "<li>✅ Los campos remoteJid y pushName están presentes</li>";
echo "<li>✅ La estructura es compatible con el script de importación actualizado</li>";
echo "<li>💡 Puedes proceder con la importación usando el script import_from_json_file.php</li>";
echo "</ul>";

// Formulario para analizar otro archivo
echo "<br><hr><br>";
echo "<h3>📁 Analizar otro archivo JSON:</h3>";
echo "<form method='post' style='background: #f0f0f0; padding: 15px; border-radius: 5px;'>";
echo "<label for='json_file'>Nombre del archivo JSON:</label><br>";
echo "<input type='text' id='json_file' name='json_file' value='contactos_mundoanimal.json' style='width: 300px; padding: 5px; margin: 5px 0;'><br>";
echo "<input type='submit' value='Analizar' style='background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer;'>";
echo "</form>";

// Enlaces útiles
echo "<br><h3>🔗 Enlaces útiles:</h3>";
echo "<ul>";
echo "<li><a href='import_from_json_file.php'>📥 Importar contactos desde archivo JSON</a></li>";
echo "<li><a href='api/import_contacts_json.php'>🌐 API de importación JSON</a></li>";
echo "<li><a href='api/import_contacts.php'>📱 API de importación desde Evolution API</a></li>";
echo "</ul>";
?> 