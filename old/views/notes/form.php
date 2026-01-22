<?php
/**
 * Vista de formulario para crear/editar notas
 */
require_once 'includes/header.php';

// Determinar si estamos editando o creando
$isEditing = isset($note);
$formTitle = $isEditing ? 'Editar Nota' : 'Nueva Nota';
$formIcon = $isEditing ? 'bi-pencil-square' : 'bi-journal-plus';

// Recuperar datos del formulario si hay error
$formData = [];
if (isset($_SESSION['form_data'])) {
    $formData = $_SESSION['form_data'];
    unset($_SESSION['form_data']);
} elseif ($isEditing) {
    $formData = $note;
}

// Errores del formulario
$errors = isset($_SESSION['form_errors']) ? $_SESSION['form_errors'] : [];
if (isset($_SESSION['form_errors'])) {
    unset($_SESSION['form_errors']);
}

// CSS específico para notas
$extraStyles = '
<link rel="stylesheet" href="assets/css/modules/notes.css">
';
?>

<div class="container">
    <div class="page-header">
        <h2><i class="bi <?php echo $formIcon; ?>"></i> <?php echo $formTitle; ?></h2>
        <div class="page-actions">
            <a href="notes.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['error_message'])) { ?>
        <div class="alert alert-danger">
            <?php
                echo $_SESSION['error_message'];
        unset($_SESSION['error_message']);
        ?>
        </div>
    <?php } ?>

    <div class="notes-form-container">
        <form action="notes.php?action=<?php echo $isEditing ? 'update' : 'store'; ?>" method="post" class="notes-form">
            <?php if ($isEditing) { ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($note['id']); ?>">
            <?php } ?>
            
            <div class="form-group">
                <label for="title">Título <span class="required">*</span></label>
                <input type="text" name="title" id="title" class="form-control <?php echo isset($errors['title']) ? 'is-invalid' : ''; ?>" 
                    value="<?php echo htmlspecialchars($formData['title'] ?? ''); ?>" required>
                <?php if (isset($errors['title'])) { ?>
                    <div class="form-error"><?php echo $errors['title']; ?></div>
                <?php } ?>
            </div>
            
            <div class="form-group">
                <label for="content">Contenido <span class="required">*</span></label>
                <textarea name="content" id="content" rows="10" class="form-control <?php echo isset($errors['content']) ? 'is-invalid' : ''; ?>" 
                    required><?php echo htmlspecialchars($formData['content'] ?? ''); ?></textarea>
                <?php if (isset($errors['content'])) { ?>
                    <div class="form-error"><?php echo $errors['content']; ?></div>
                <?php } ?>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="type">Tipo de nota</label>
                    <select name="type" id="type" class="form-control <?php echo isset($errors['type']) ? 'is-invalid' : ''; ?>">
                        <option value="nota" <?php echo (isset($formData['type']) && $formData['type'] === 'nota') ? 'selected' : ''; ?>>Nota</option>
                        <option value="sugerencia" <?php echo (isset($formData['type']) && $formData['type'] === 'sugerencia') ? 'selected' : ''; ?>>Sugerencia</option>
                        <option value="otro" <?php echo (isset($formData['type']) && $formData['type'] === 'otro') ? 'selected' : ''; ?>>Otro</option>
                    </select>
                    <?php if (isset($errors['type'])) { ?>
                        <div class="form-error"><?php echo $errors['type']; ?></div>
                    <?php } ?>
                </div>
                
                <div class="form-group">
                    <label for="visibility">Visibilidad</label>
                    <select name="visibility" id="visibility" class="form-control <?php echo isset($errors['visibility']) ? 'is-invalid' : ''; ?>">
                        <option value="solo_yo" <?php echo (isset($formData['visibility']) && $formData['visibility'] === 'solo_yo') ? 'selected' : ''; ?>>Solo yo</option>
                        <option value="todos" <?php echo (isset($formData['visibility']) && $formData['visibility'] === 'todos') ? 'selected' : ''; ?>>Todos los usuarios</option>
                    </select>
                    <?php if (isset($errors['visibility'])) { ?>
                        <div class="form-error"><?php echo $errors['visibility']; ?></div>
                    <?php } ?>
                </div>
            </div>
            
            <div class="notes-form-footer">
                <a href="notes.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <?php echo $isEditing ? 'Guardar cambios' : 'Crear nota'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enfocar el campo de título al cargar
    document.getElementById('title').focus();
});
</script>

<?php require_once 'includes/footer.php'; ?> 