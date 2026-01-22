<?php
// Script de prueba para verificar el envío de difusiones con imágenes
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../models/BroadcastListModel.php';
require_once __DIR__.'/../models/BroadcastHistoryModel.php';

echo '<h1>Prueba de Difusiones con Imágenes</h1>';

// Verificar configuración de Evolution API
$config = [];
$sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('evolution_api_url', 'evolution_api_key', 'evolution_instance_name')";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $config[$row['setting_key']] = $row['setting_value'];
}

echo '<h2>Configuración de Evolution API:</h2>';
echo '<ul>';
foreach ($config as $key => $value) {
    echo "<li><strong>$key:</strong> ".(strlen($value) > 20 ? substr($value, 0, 20).'...' : $value).'</li>';
}
echo '</ul>';

// Verificar directorio de uploads
$uploadsDir = __DIR__.'/../uploads';
echo '<h2>Directorio de Uploads:</h2>';
echo '<ul>';
echo "<li><strong>Ruta:</strong> $uploadsDir</li>";
echo '<li><strong>Existe:</strong> '.(is_dir($uploadsDir) ? 'Sí' : 'No').'</li>';
echo '<li><strong>Permisos:</strong> '.(is_dir($uploadsDir) ? substr(sprintf('%o', fileperms($uploadsDir)), -4) : 'N/A').'</li>';
echo '<li><strong>Escribible:</strong> '.(is_dir($uploadsDir) && is_writable($uploadsDir) ? 'Sí' : 'No').'</li>';
echo '</ul>';

// Verificar listas de difusión
$broadcastListModel = new BroadcastListModel($conn);
$lists = $broadcastListModel->getListsByUser(1); // Asumiendo usuario ID 1

echo '<h2>Listas de Difusión Disponibles:</h2>';
if (empty($lists)) {
    echo '<p>No hay listas de difusión disponibles.</p>';
} else {
    echo '<ul>';
    foreach ($lists as $list) {
        echo "<li><strong>{$list['name']}</strong> - {$list['contact_count']} contactos - ".
             ($list['is_active'] ? 'Activa' : 'Inactiva').'</li>';
    }
    echo '</ul>';
}

// Verificar difusiones recientes
$broadcastHistoryModel = new BroadcastHistoryModel($conn);
$recentBroadcasts = $broadcastHistoryModel->getRecentBroadcasts(1, 24);

echo '<h2>Difusiones Recientes (últimas 24h):</h2>';
if (empty($recentBroadcasts)) {
    echo '<p>No hay difusiones recientes.</p>';
} else {
    echo '<ul>';
    foreach ($recentBroadcasts as $broadcast) {
        echo "<li><strong>ID: {$broadcast['id']}</strong> - {$broadcast['list_name']} - ".
             "Estado: {$broadcast['status']} - ".
             "Contactos: {$broadcast['total_contacts']}";
        if ($broadcast['image_path']) {
            echo " - <strong>Con imagen: {$broadcast['image_path']}</strong>";
        }
        echo '</li>';
    }
    echo '</ul>';
}

// Verificar función mime_content_type
echo '<h2>Verificación de Funciones:</h2>';
echo '<ul>';
echo '<li><strong>mime_content_type:</strong> '.(function_exists('mime_content_type') ? 'Disponible' : 'No disponible').'</li>';
echo '<li><strong>CURL:</strong> '.(function_exists('curl_init') ? 'Disponible' : 'No disponible').'</li>';
echo '<li><strong>CURLFile:</strong> '.(class_exists('CURLFile') ? 'Disponible' : 'No disponible').'</li>';
echo '</ul>';

// Crear imagen de prueba si no existe
$testImagePath = $uploadsDir.'/test_image.jpg';
if (! file_exists($testImagePath)) {
    echo '<h2>Creando imagen de prueba:</h2>';

    // Crear una imagen simple de prueba
    $image = imagecreate(100, 100);
    $bgColor = imagecolorallocate($image, 255, 255, 255);
    $textColor = imagecolorallocate($image, 0, 0, 0);
    imagestring($image, 5, 10, 40, 'TEST', $textColor);

    if (imagejpeg($image, $testImagePath, 90)) {
        echo "<p>✅ Imagen de prueba creada: $testImagePath</p>";
    } else {
        echo '<p>❌ Error al crear imagen de prueba</p>';
    }

    imagedestroy($image);
} else {
    echo "<p>✅ Imagen de prueba ya existe: $testImagePath</p>";
}

echo '<h2>Prueba completada.</h2>';
?> 