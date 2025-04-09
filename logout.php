<?php
// Incluir archivos necesarios
require_once 'includes/auth.php';

// Limpiar el token JWT del localStorage antes de cerrar sesión
echo '<script>
    if (localStorage.getItem("jwt_token")) {
        localStorage.removeItem("jwt_token");
        console.log("Token JWT eliminado al cerrar sesión");
    }
</script>';

// Cerrar sesión PHP
logoutUser();

// Redirigir a la página de inicio de sesión
header("Location: login.php");
exit();
?> 