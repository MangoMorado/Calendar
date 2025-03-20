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
        
        // Validar que los campos no estén vacíos
        if (empty($email) || empty($password)) {
            $message = 'Por favor, completa todos los campos';
            $messageType = 'error';
        } else {
            // Intentar iniciar sesión
            $result = loginUser($email, $password);
            
            if ($result['success']) {
                // Almacenar usuario en sesión
                authenticateUser($result['user']);
                
                // Redirigir a la página solicitada o a la principal
                $redirect = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : 'index.php';
                unset($_SESSION['redirect_url']);
                header("Location: $redirect");
                exit();
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
    <title>Iniciar Sesión | Mundo Animal</title>
    
    <!-- Fuentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Estilos CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        .auth-container {
            max-width: 450px;
            margin: 80px auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .auth-header h1 {
            color: #5D69F7;
            margin-bottom: 10px;
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9eaf3;
        }
        
        .message {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            text-align: center;
        }
        
        .message.error {
            background-color: #FEEFEF;
            color: #EF4444;
            border: 1px solid #FCDEDE;
        }
        
        .message.success {
            background-color: #EFF8F6;
            color: #10B981;
            border: 1px solid #D1ECEA;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1>Mundo Animal</h1>
            <p>Ingresa a tu cuenta para gestionar citas</p>
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
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success btn-block">
                    <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
                </button>
            </div>
        </form>
        
        <div class="auth-footer">
            <p><a href="forgot_password.php">¿Olvidaste tu contraseña?</a></p>
            <p>¿No tienes una cuenta? <a href="register.php">Regístrate</a></p>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Hecho por 🥭 Mango Morado para Mundo Animal. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html> 