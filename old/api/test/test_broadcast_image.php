<?php

header('Content-Type: application/json');
require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../models/BroadcastListModel.php';
require_once __DIR__.'/../../models/BroadcastHistoryModel.php';
require_once __DIR__.'/../../includes/evolution_api.php';

$status = 'ok';
$message = '';

// Verificar directorio de uploads
$uploadsDir = __DIR__.'/../../uploads';
if (! is_dir($uploadsDir) || ! is_writable($uploadsDir)) {
    $status = 'error';
    $message = 'El directorio uploads no existe o no es escribible.';
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

// Verificar funciones necesarias
if (! function_exists('mime_content_type') || ! function_exists('curl_init') || ! class_exists('CURLFile')) {
    $status = 'error';
    $message = 'Faltan funciones requeridas de PHP (mime_content_type, curl, CURLFile).';
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

// Verificar que las funciones de Evolution API existen
if (! function_exists('sendEvolutionText') || ! function_exists('sendEvolutionMedia')) {
    $status = 'error';
    $message = 'Las funciones de Evolution API no están disponibles.';
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

// Crear imagen de prueba si no existe
$testImagePath = $uploadsDir.'/test_image.jpg';
if (! file_exists($testImagePath)) {
    if (function_exists('imagecreate')) {
        $image = imagecreate(100, 100);
        $bgColor = imagecolorallocate($image, 255, 255, 255);
        $textColor = imagecolorallocate($image, 0, 0, 0);
        imagestring($image, 5, 10, 40, 'TEST', $textColor);
        if (! imagejpeg($image, $testImagePath, 90)) {
            $status = 'error';
            $message = 'No se pudo crear la imagen de prueba.';
            echo json_encode(['status' => $status, 'message' => $message]);
            exit;
        }
        imagedestroy($image);
    } else {
        $status = 'warning';
        $message = 'No se pudo crear imagen de prueba (falta GD), pero el directorio es escribible.';
        echo json_encode(['status' => $status, 'message' => $message]);
        exit;
    }
}

// Verificar que la imagen existe
if (! file_exists($testImagePath)) {
    $status = 'error';
    $message = 'La imagen de prueba no existe.';
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

// Verificar listas de difusión
$broadcastListModel = new BroadcastListModel($conn);
$lists = $broadcastListModel->getListsByUser(1); // Asumiendo usuario ID 1
if (empty($lists)) {
    $status = 'warning';
    $message = 'No hay listas de difusión disponibles para el usuario 1.';
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

// Verificar configuración de Evolution API
$config = [];
$sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('evolution_api_url', 'evolution_api_key', 'evolution_instance_name')";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $config[$row['setting_key']] = $row['setting_value'];
}

if (empty($config['evolution_api_url']) || empty($config['evolution_api_key']) || empty($config['evolution_instance_name'])) {
    $status = 'warning';
    $message = 'Configuración de Evolution API incompleta, pero el sistema está listo.';
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

$status = 'ok';
$message = 'El sistema está completamente listo para enviar difusiones con imágenes. Todas las funciones y configuraciones están correctas.';
echo json_encode(['status' => $status, 'message' => $message]);
