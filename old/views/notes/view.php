<?php
/**
 * Vista de detalle de una nota
 */
require_once 'includes/header.php';

// CSS específico para notas
$extraStyles = '
<link rel="stylesheet" href="assets/css/modules/notes.css">
';

// Información de la nota
$typeLabels = [
    'nota' => 'Nota',
    'sugerencia' => 'Sugerencia',
    'otro' => 'Otro',
];
$typeLabelText = $typeLabels[$note['type']] ?? 'Nota';

$visibilityIcon = $note['visibility'] === 'todos' ? 'bi-eye' : 'bi-eye-slash';
$visibilityText = $note['visibility'] === 'todos' ? 'Visible para todos' : 'Solo yo';
?>

<div class="container">
    <div class="page-header">
        <h2><i class="bi bi-journal-text"></i> <?php echo htmlspecialchars($note['title']); ?></h2>
        <div class="page-actions">
            <?php if ($canEdit) { ?>
                <a href="notes.php?action=edit&id=<?php echo $note['id']; ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Editar
                </a>
                <button class="btn btn-danger" id="deleteNoteBtn">
                    <i class="bi bi-trash"></i> Eliminar
                </button>
            <?php } ?>
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

    <div class="note-detail-container">
        <div class="note-meta-info">
            <div class="meta-item type <?php echo $note['type']; ?>">
                <i class="bi bi-tag"></i>
                <span><?php echo $typeLabelText; ?></span>
            </div>
            <div class="meta-item author">
                <i class="bi bi-person"></i>
                <span>Por: <?php echo htmlspecialchars($note['author_name']); ?></span>
            </div>
            <div class="meta-item date">
                <i class="bi bi-calendar"></i>
                <span>Creado: <?php echo date('d/m/Y H:i', strtotime($note['created_at'])); ?></span>
            </div>
            <?php if ($note['created_at'] !== $note['updated_at']) { ?>
                <div class="meta-item updated">
                    <i class="bi bi-clock-history"></i>
                    <span>Actualizado: <?php echo date('d/m/Y H:i', strtotime($note['updated_at'])); ?></span>
                </div>
            <?php } ?>
            <div class="meta-item visibility <?php echo $note['visibility']; ?>">
                <i class="bi <?php echo $visibilityIcon; ?>"></i>
                <span><?php echo $visibilityText; ?></span>
            </div>
        </div>
        
        <div class="note-content">
            <?php echo nl2br(htmlspecialchars($note['content'])); ?>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div class="delete-confirmation-modal" id="deleteNoteModal">
    <div class="modal-content">
        <h4>Confirmar eliminación</h4>
        <p>¿Estás seguro de que deseas eliminar esta nota? Esta acción no se puede deshacer.</p>
        <div class="modal-actions">
            <button class="btn btn-secondary" id="cancelDeleteBtn">Cancelar</button>
            <form action="notes.php?action=delete" method="post">
                <input type="hidden" name="id" value="<?php echo $note['id']; ?>">
                <button type="submit" class="btn btn-danger">Eliminar</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configurar modal de eliminación
    const deleteBtn = document.getElementById('deleteNoteBtn');
    const deleteModal = document.getElementById('deleteNoteModal');
    const cancelBtn = document.getElementById('cancelDeleteBtn');
    
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function(e) {
            e.preventDefault();
            deleteModal.style.display = 'block';
            // Usamos setTimeout para permitir que el navegador procese el cambio de display
            // antes de agregar la clase que activa la animación
            setTimeout(() => {
                deleteModal.classList.add('active');
            }, 10);
        });
    }
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            deleteModal.classList.remove('active');
            setTimeout(() => {
                deleteModal.style.display = 'none';
            }, 300);
        });
    }
    
    // Cerrar modal si se hace clic fuera de él
    window.addEventListener('click', function(e) {
        if (e.target === deleteModal) {
            deleteModal.classList.remove('active');
            setTimeout(() => {
                deleteModal.style.display = 'none';
            }, 300);
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 