<?php
$pageTitle = 'Crear Usuario | Mundo Animal';
include 'includes/header.php';
?>

<div class="container">
    <div class="users-header">
        <h1><i class="bi bi-person-plus"></i> Crear Nuevo Usuario</h1>
        <a href="users.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    <?php if (isset($error)) { ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
    <?php } ?>

    <div class="card">
        <div class="card-body">
            <form method="post" class="user-form">
                <div class="form-group">
                    <label for="name">Nombre:</label>
                    <input type="text" id="name" name="name" class="form-control" 
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="phone">Teléfono (con indicativo):</label>
                    <input type="tel" id="phone" name="phone" class="form-control" 
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '57'; ?>" 
                           pattern="^\d{7,20}$" required>
                    <small class="form-text text-muted">Ej: 57XXXXXXXXXX (Colombia por defecto)</small>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                    <small class="form-text text-muted">La contraseña debe tener al menos 6 caracteres</small>
                </div>

                <div class="form-group">
                    <label for="role">Rol:</label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="user" <?php echo (isset($_POST['role']) && $_POST['role'] === 'user') ? 'selected' : ''; ?>>
                            Usuario
                        </option>
                        <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>
                            Administrador
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="color">Color:</label>
                    <div class="color-input-group">
                        <input type="color" id="color" name="color" class="form-control form-control-color" 
                               value="<?php echo isset($_POST['color']) ? htmlspecialchars($_POST['color']) : '#0d6efd'; ?>" 
                               title="Elige un color">
                        <input type="text" id="colorHex" name="color" class="form-control" 
                               value="<?php echo isset($_POST['color']) ? htmlspecialchars($_POST['color']) : '#0d6efd'; ?>" 
                               pattern="^#[a-fA-F0-9]{6}$" required>
                    </div>
                    <small class="form-text text-muted">Color personalizado para el usuario</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save"></i> Crear Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const colorInput = document.getElementById('color');
    const colorHex = document.getElementById('colorHex');
    
    colorInput.addEventListener('input', function() {
        colorHex.value = this.value;
    });
    
    colorHex.addEventListener('input', function() {
        if (this.value.match(/^#[a-fA-F0-9]{6}$/)) {
            colorInput.value = this.value;
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?> 