<?php
// Incluir archivos necesarios
require_once 'includes/auth.php';

// Redirigir si ya está autenticado
if (isAuthenticated()) {
    header("Location: index.php");
    exit();
}

$message = '';
$messageType = '';
$showForm = true;

// Verificar token y email
if (!isset($_GET['token']) || !isset($_GET['email'])) {
    $message = 'Enlace de restablecimiento no válido';
    $messageType = 'error';
    $showForm = false;
} else {
    $token = $_GET['token'];
    $email = $_GET['email'];
    
    // Verificar si el token es válido
    if (!verifyResetToken($token, $email)) {
        $message = 'El enlace de restablecimiento ha caducado o no es válido';
        $messageType = 'error';
        $showForm = false;
    }
}

// Procesar el formulario de restablecimiento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $showForm) {
    if (isset($_POST['password']) && isset($_POST['confirm_password']) && isset($_POST['token']) && isset($_POST['email'])) {
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];
        $token = $_POST['token'];
        $email = $_POST['email'];
        
        // Validar que los campos no estén vacíos
        if (empty($password) || empty($confirmPassword)) {
            $message = 'Por favor, completa todos los campos';
            $messageType = 'error';
        } 
        // Validar que las contraseñas coincidan
        elseif ($password !== $confirmPassword) {
            $message = 'Las contraseñas no coinciden';
            $messageType = 'error';
        }
        // Validar longitud de la contraseña
        elseif (strlen($password) < 6) {
            $message = 'La contraseña debe tener al menos 6 caracteres';
            $messageType = 'error';
        }
        else {
            // Restablecer la contraseña
            $result = resetPassword($token, $email, $password);
            
            if ($result['success']) {
                $message = 'Contraseña restablecida con éxito. Ahora puedes iniciar sesión con tu nueva contraseña.';
                $messageType = 'success';
                $showForm = false;
                
                // Redirigir a la página de inicio de sesión después de 3 segundos
                header("Refresh: 3; URL=login.php");
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
    <title>Restablecer Contraseña | MangoCal</title>
    
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
            <p>Restablecer Contraseña</p>
        </div>
        
        <?php if (!empty($message)) : ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($showForm) : ?>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" novalidate>
                <p class="mb-4">Introduce tu nueva contraseña para restablecer el acceso a tu cuenta.</p>
                
                <div class="form-group">
                    <label for="password"><i class="bi bi-lock"></i> Nueva Contraseña</label>
                    <input type="password" id="password" name="password" class="form-control" required 
                           placeholder="Mínimo 6 caracteres">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password"><i class="bi bi-shield-lock"></i> Confirmar Contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>
                
                <!-- Campos ocultos para token y email -->
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token ?? ''); ?>">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>">
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success btn-block">
                        <i class="bi bi-check-lg"></i> Restablecer Contraseña
                    </button>
                </div>
            </form>
        <?php endif; ?>
        
        <div class="auth-footer">
            <p><a href="login.php">Volver a Inicio de Sesión</a></p>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> MangoCal. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html> 