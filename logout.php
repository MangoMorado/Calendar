<?php
// Incluir archivos necesarios
require_once 'includes/auth.php';

// Cerrar la sesión
logoutUser();

// Redirigir a la página de inicio de sesión
header("Location: login.php");
exit();
?> 