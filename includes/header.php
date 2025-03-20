<?php
// Verificar si hay usuario actual
$currentUser = isset($currentUser) ? $currentUser : (function_exists('getCurrentUser') ? getCurrentUser() : null);
// Definir el título de la página si no está definido
$pageTitle = isset($pageTitle) ? $pageTitle : 'Agenda de Citas | Mundo Animal';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Fuentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Estilos CSS básicos -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <?php if (isset($extraStyles)) echo $extraStyles; ?>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo-link">
                    <img src="assets/img/logo.svg" alt="Mundo Animal" class="logo">
                    <div class="site-title">
                        <h1>Mundo Animal</h1>
                        <p class="tagline">Agenda de Citas</p>
                    </div>
                </a>
            </div>
            <?php if ($currentUser): ?>
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-dropdown">
                        <button class="dropdown-toggle">
                            <i class="bi bi-person-circle user-icon"></i>
                            <span class="user-name"><?php echo htmlspecialchars($currentUser['name']); ?></span>
                            <span class="user-role"><?php echo $currentUser['role'] === 'admin' ? 'Administrador' : 'Usuario'; ?></span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a href="profile.php" class="dropdown-item">
                                <i class="bi bi-person"></i> Mi Perfil
                            </a>
                            <?php if ($currentUser['role'] === 'admin') : ?>
                            <a href="historial.php" class="dropdown-item">
                                <i class="bi bi-clock-history"></i> Historial
                            </a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item">
                                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                            </a>
                        </div>
                    </div>
                </div>
                <nav class="main-nav">
                    <a href="index.php" class="nav-item" title="Inicio">
                        <i class="bi bi-house"></i> Inicio
                    </a>
                    <?php if ($currentUser['role'] === 'admin') : ?>
                    <a href="historial.php" class="nav-item" title="Historial de Actividades">
                        <i class="bi bi-clock-history"></i> Historial
                    </a>
                    <?php endif; ?>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </header>
</body>
</html> 