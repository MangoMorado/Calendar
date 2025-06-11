<?php
// Incluir archivos necesarios
require_once 'includes/auth.php';

// Redirigir si ya está autenticado
if (isAuthenticated()) {
    $redirect = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : 'index.php';
    unset($_SESSION['redirect_url']);
    header("Location: $redirect");
    exit();
}

$message = '';
$messageType = '';

// Procesar el formulario de inicio de sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $rememberMe = isset($_POST['remember_me']) && $_POST['remember_me'] === 'on';
        
        // Validar que los campos no estén vacíos
        if (empty($email) || empty($password)) {
            $message = 'Por favor, completa todos los campos';
            $messageType = 'error';
        } else {
            // Intentar iniciar sesión
            $result = loginUser($email, $password);
            
            if ($result['success']) {
                // Almacenar usuario en sesión con opción de recordar equipo
                $redirect = authenticateUser($result['user'], $rememberMe);
                
                if ($redirect) {
                    // Redirigir a la página solicitada o a la principal
                    header("Location: $redirect");
                    exit();
                } else {
                    $message = 'Error al crear la sesión';
                    $messageType = 'error';
                }
            } else {
                $message = $result['message'];
                $messageType = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | Calendario MangaMorado</title>
    
    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Estilos -->
    <link rel="stylesheet" href="assets/css/main.css">
    
    <style>
        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        
        .auth-container {
            max-width: 450px;
            margin: 2rem auto;
            padding: 2rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            background-color: white;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-header h1 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }
        
        .message {
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--radius);
            text-align: center;
        }
        
        .message.error {
            background-color: #FEEFEF;
            color: var(--danger-color);
            border: 1px solid #FCDEDE;
        }
        
        .message.success {
            background-color: #EFF8F6;
            color: var(--success-color);
            border: 1px solid #D1ECEA;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            margin: 1rem 0;
        }
        
        .form-check-input {
            margin-right: 0.5rem;
        }
        
        .form-check-label {
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        
        .device-info {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: var(--radius);
            padding: 0.75rem;
            margin-top: 1rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        
        .device-info i {
            margin-right: 0.5rem;
        }
        
        footer {
            margin-top: auto;
        }
    </style>
</head>
<body class="bg-light">
    <div class="auth-container">
        <div class="auth-header">
            <img src="assets/img/logo.svg" alt="Logo Mundo Animal" class="auth-logo">
            <h1 class="text-primary font-bold">Mundo Animal</h1>
            <p class="text-muted">Ingresa a tu cuenta para gestionar citas</p>
        </div>
        
        <?php if (!empty($message)) : ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" novalidate>
            <div class="form-group">
                <label for="email"><i class="bi bi-envelope"></i> Correo Electrónico</label>
                <input type="email" id="email" name="email" class="form-control" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password"><i class="bi bi-lock"></i> Contraseña</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <div class="form-check">
                <input type="checkbox" id="remember_me" name="remember_me" class="form-check-input">
                <label for="remember_me" class="form-check-label">
                    <i class="bi bi-device-hdd"></i> Recordar este equipo
                </label>
            </div>
            
            <div class="device-info">
                <i class="bi bi-info-circle"></i>
                <strong>Información del equipo:</strong><br>
                <?php
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';
                $deviceInfo = 'Desconocido';
                
                if (preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent)) {
                    $deviceInfo = 'Móvil';
                } elseif (preg_match('/Windows/i', $userAgent)) {
                    $deviceInfo = 'Windows';
                } elseif (preg_match('/Mac/i', $userAgent)) {
                    $deviceInfo = 'Mac';
                } elseif (preg_match('/Linux/i', $userAgent)) {
                    $deviceInfo = 'Linux';
                }
                
                echo "Dispositivo: $deviceInfo<br>";
                echo "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Desconocida');
                ?>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success btn-block">
                    <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
                </button>
            </div>
        </form>
        
        <div class="auth-footer">
            <p><a href="forgot_password.php" class="text-primary">¿Olvidaste tu contraseña?</a></p>
            <p>¿No tienes una cuenta? <a href="register.php" class="text-primary">Regístrate</a></p>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p class="text-muted text-center">&copy; <?php echo date('Y'); ?> Hecho por 🥭 Mango Morado para Mundo Animal. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html> 