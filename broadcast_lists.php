<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'controllers/BroadcastListController.php';

// Verificar autenticación
requireAuth();

// Instanciar el controlador y manejar la petición
$controller = new BroadcastListController($conn);
$controller->handleRequest();
