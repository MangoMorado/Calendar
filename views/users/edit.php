<?php
$pageTitle = 'Editar Usuario | Mundo Animal';
include 'includes/header.php';
?>

<div class="container">
    <div class="users-header">
        <h1><i class="bi bi-person-gear"></i> Editar Usuario</h1>
        <a href="users.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post" class="user-form">
                <div class="form-group">
                    <label for="name">Nombre:</label>
                    <input type="text" id="name" name="name" class="form-control" 
                           value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Nueva Contraseña:</label>
                    <input type="password" id="password" name="password" class="form-control">
                    <small class="form-text text-muted">Deja en blanco para mantener la contraseña actual</small>
                </div>

                <div class="form-group">
                    <label for="role">Rol:</label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>
                            Usuario
                        </option>
                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>
                            Administrador
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="color">Color:</label>
                    <div class="color-input-group">
                        <input type="color" id="color" name="color" class="form-control form-control-color" 
                               value="<?php echo htmlspecialchars($user['color']); ?>" title="Elige un color">
                        <input type="text" id="colorHex" name="color" class="form-control" 
                               value="<?php echo htmlspecialchars($user['color']); ?>" 
                               pattern="^#[a-fA-F0-9]{6}$" required>
                    </div>
                    <small class="form-text text-muted">Color personalizado para el usuario</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save"></i> Guardar Cambios
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