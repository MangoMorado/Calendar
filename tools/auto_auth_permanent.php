<?php
// Script de autenticación automática para sesiones permanentes
// Ejecutar este script para autenticar automáticamente a los usuarios

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Autenticar usuario: Sergio Veloza
$userData1 = [
    'id' => 1,
    'name' => 'Sergio Veloza',
    'email' => 'velozasergio@gmail.com',
    'role' => 'admin'
];

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user'] = $userData1;
echo '✅ Usuario autenticado: Sergio Veloza\n';

echo '✅ Todos los usuarios están autenticados\n';
echo 'Ahora puedes probar el endpoint api/import_contacts.php\n';
?>