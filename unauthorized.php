<?php
// Incluir archivos necesarios
require_once 'includes/auth.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso No Autorizado | Calendario MangaMorado</title>
    
    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Estilos -->
    <link rel="stylesheet" href="assets/css/main.css">
    
    <style>
        .error-container {
            max-width: 550px;
            margin: 100px auto;
            padding: 40px;
            text-align: center;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        
        .error-icon {
            font-size: 4rem;
            color: #EF4444;
            margin-bottom: 20px;
        }
        
        .error-title {
            font-size: 1.8rem;
            color: #2D3748;
            margin-bottom: 15px;
        }
        
        .error-message {
            color: #4A5568;
            margin-bottom: 30px;
        }
        
        .btn-group {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="bi bi-shield-exclamation"></i>
        </div>
        <h1 class="error-title">Acceso No Autorizado</h1>
        <p class="error-message">
            No tienes permisos para acceder a esta sección.
            <?php if (isAuthenticated()): ?>
                Tu cuenta no tiene los privilegios necesarios para ver este contenido.
            <?php else: ?>
                Por favor, inicia sesión para continuar.
            <?php endif; ?>
        </p>
        <div class="btn-group">
            <?php if (isAuthenticated()): ?>
                <a href="index.php" class="btn btn-success">
                    <i class="bi bi-house"></i> Ir al Inicio
                </a>
                <a href="logout.php" class="btn btn-outline">
                    <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-success">
                    <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
                </a>
                <a href="register.php" class="btn btn-outline">
                    <i class="bi bi-person-plus"></i> Registrarse
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> MangoCal. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html> 