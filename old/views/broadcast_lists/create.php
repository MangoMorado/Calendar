<?php
// Vista para crear una nueva lista de difusión
?>

<div class="container">
    <div class="config-header">
        <h1><i class="bi bi-megaphone"></i> Listas de Difusión</h1>
        <p class="text-muted">Gestiona tus listas de contactos para envío de difusiones masivas.</p>
    </div>

    <!-- Mensajes -->
    <?php if ($message) { ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php } ?>

    <?php if ($error) { ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php } ?>

    <!-- Formulario para crear nueva lista -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Crear Nueva Lista de Difusión</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label">Nombre de la lista *</label>
                    <input type="text" class="form-control" id="name" name="name" required 
                           placeholder="Ej: Clientes VIP, Promociones, Recordatorios">
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Descripción</label>
                    <textarea class="form-control" id="description" name="description" rows="3" 
                              placeholder="Describe el propósito de esta lista..."></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" name="create_list" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Crear Lista
                    </button>
                    <a href="?action=list" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div> 