<?php
// Incluir archivos necesarios
require_once 'includes/auth.php';

// Redirigir si ya está autenticado
if (isAuthenticated()) {
    header('Location: index.php');
    exit();
}

$message = '';
$messageType = '';

// Procesar el formulario de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['name']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['confirm_password'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];

        // Validar que los campos no estén vacíos
        if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
            $message = 'Por favor, completa todos los campos';
            $messageType = 'error';
        }
        // Validar formato de correo electrónico
        elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Por favor, introduce un correo electrónico válido';
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
        } else {
            // Intentar registrar al usuario
            $result = registerUser($email, $password, $name);

            if ($result['success']) {
                // Auto login después del registro
                $loginResult = loginUser($email, $password);

                if ($loginResult['success']) {
                    authenticateUser($loginResult['user']);
                    header('Location: index.php');
                    exit();
                } else {
                    // Mostrar mensaje de éxito y redirigir a login
                    $message = 'Registro exitoso. Por favor, inicia sesión.';
                    $messageType = 'success';

                    // Redirigir a la página de inicio de sesión después de 2 segundos
                    header('Refresh: 2; URL=login.php');
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
    <title>Registro | Calendario MangaMorado</title>
    
    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Estilos -->
    <link rel="stylesheet" href="assets/css/main.css">
    
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
            <p>Regístrate para gestionar tus citas</p>
        </div>
        
        <?php if (! empty($message)) { ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php } ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" novalidate>
            <div class="form-group">
                <label for="name"><i class="bi bi-person"></i> Nombre Completo</label>
                <input type="text" id="name" name="name" class="form-control" required 
                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="email"><i class="bi bi-envelope"></i> Correo Electrónico</label>
                <input type="email" id="email" name="email" class="form-control" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password"><i class="bi bi-lock"></i> Contraseña</label>
                <input type="password" id="password" name="password" class="form-control" required 
                       placeholder="Mínimo 6 caracteres">
            </div>
            
            <div class="form-group">
                <label for="confirm_password"><i class="bi bi-shield-lock"></i> Confirmar Contraseña</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success btn-block">
                    <i class="bi bi-person-plus"></i> Crear Cuenta
                </button>
            </div>
        </form>
        
        <div class="auth-footer">
            <p>¿Ya tienes una cuenta? <a href="login.php">Iniciar Sesión</a></p>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p class="text-muted text-center">&copy; <?php echo date('Y'); ?> Hecho por 🥭 Mango Morado para Mundo Animal. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html> 