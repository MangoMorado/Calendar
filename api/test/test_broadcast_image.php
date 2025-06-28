<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/BroadcastListModel.php';
require_once __DIR__ . '/../../models/BroadcastHistoryModel.php';

$status = 'ok';
$message = '';

// Verificar directorio de uploads
$uploadsDir = __DIR__ . '/../../uploads';
if (!is_dir($uploadsDir) || !is_writable($uploadsDir)) {
    $status = 'error';
    $message = 'El directorio uploads no existe o no es escribible.';
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

// Verificar funciones necesarias
if (!function_exists('mime_content_type') || !function_exists('curl_init') || !class_exists('CURLFile')) {
    $status = 'error';
    $message = 'Faltan funciones requeridas de PHP (mime_content_type, curl, CURLFile).';
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

// Crear imagen de prueba si no existe
$testImagePath = $uploadsDir . '/test_image.jpg';
if (!file_exists($testImagePath)) {
    if (function_exists('imagecreate')) {
        $image = imagecreate(100, 100);
        $bgColor = imagecolorallocate($image, 255, 255, 255);
        $textColor = imagecolorallocate($image, 0, 0, 0);
        imagestring($image, 5, 10, 40, 'TEST', $textColor);
        if (!imagejpeg($image, $testImagePath, 90)) {
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
if (!file_exists($testImagePath)) {
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

$status = 'ok';
$message = 'El sistema está listo para enviar difusiones con imágenes.';
echo json_encode(['status' => $status, 'message' => $message]); 