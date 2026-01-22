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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    
    <!-- Estilos -->
    <link rel="stylesheet" href="assets/css/main.css">
    
    <?php echo isset($extraStyles) ? $extraStyles : ''; ?>
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
            <?php if ($currentUser) { ?>
            <div class="user-menu">
                <button id="mobileMenuBtn" class="mobile-menu-btn" aria-label="Menú">
                    <i class="bi bi-list"></i>
                </button>
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
                            <a href="estadisticas.php" class="dropdown-item">
                                <i class="bi bi-graph-up"></i> Estadísticas
                            </a>
                            <?php if ($currentUser['role'] === 'admin') { ?>
                            <a href="historial.php" class="dropdown-item">
                                <i class="bi bi-clock-history"></i> Historial
                            </a>
                            <a href="users.php" class="dropdown-item">
                                <i class="bi bi-people"></i> Usuarios
                            </a>
                            <a href="config.php" class="dropdown-item">
                                <i class="bi bi-gear"></i> Configuración
                            </a>
                            <?php } ?>
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
                    <a href="notes.php" class="nav-item" title="Libreta de Notas">
                        <i class="bi bi-journal-text"></i> Libreta de Notas
                    </a>
                    <a href="broadcast_lists.php" class="nav-item" title="Listas de Difusión">
                        <i class="bi bi-megaphone"></i> Difusiones
                    </a>
                    <a href="chatbot.php" class="nav-item" title="Chatbot">
                        <i class="bi bi-robot"></i> Chatbot
                    </a>
                </nav>
            </div>
            <?php } ?>
        </div>
    </header>
    
    <!-- Menú móvil y overlay -->
    <div class="mobile-menu-overlay"></div>
    <div id="mobileMenu" class="mobile-menu">
        <nav class="mobile-nav">
            <a href="index.php" class="mobile-nav-item" title="Inicio">
                <i class="bi bi-house"></i> Inicio
            </a>
            <a href="notes.php" class="mobile-nav-item" title="Libreta de Notas">
                <i class="bi bi-journal-text"></i> Libreta de Notas
            </a>
            <a href="broadcast_lists.php" class="mobile-nav-item" title="Listas de Difusión">
                <i class="bi bi-megaphone"></i> Difusiones
            </a>
            <a href="chatbot.php" class="mobile-nav-item" title="Chatbot">
                <i class="bi bi-robot"></i> Chatbot
            </a>
            <a href="estadisticas.php" class="mobile-nav-item" title="Estadísticas">
                <i class="bi bi-graph-up"></i> Estadísticas
            </a>
        </nav>
    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html> 