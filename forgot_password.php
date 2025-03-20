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

// Procesar el formulario de solicitud de restablecimiento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
        
        // Validar que el correo no esté vacío
        if (empty($email)) {
            $message = 'Por favor, introduce tu correo electrónico';
            $messageType = 'error';
        } 
        // Validar formato de correo electrónico
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Por favor, introduce un correo electrónico válido';
            $messageType = 'error';
        }
        else {
            // Generar token de restablecimiento
            $result = generateResetToken($email);
            
            if ($result['success']) {
                // En un entorno real, aquí se enviaría un correo con el enlace de restablecimiento
                // Para fines de demostración, mostraremos el enlace directamente
                $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . 
                             "/reset_password.php?token=" . $result['token'] . "&email=" . urlencode($email);
                
                $message = "Se ha enviado un enlace de restablecimiento a tu correo electrónico.<br>
                            <small>(Para fines de demostración: <a href='$resetLink'>$resetLink</a>)</small>";
                $messageType = 'success';
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
    <title>Recuperar Contraseña | MangoCal</title>
    
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
            <p>Recuperar Contraseña</p>
        </div>
        
        <?php if (!empty($message)) : ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" novalidate>
            <p class="mb-4">Introduce tu correo electrónico y te enviaremos instrucciones para restablecer tu contraseña.</p>
            
            <div class="form-group">
                <label for="email"><i class="bi bi-envelope"></i> Correo Electrónico</label>
                <input type="email" id="email" name="email" class="form-control" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success btn-block">
                    <i class="bi bi-send"></i> Enviar Instrucciones
                </button>
            </div>
        </form>
        
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