<?php
// Vista para listar las listas de difusión
$lists = $data['lists'];
$stats = $data['stats'];
?>

<div class="container">
    <div class="config-header">
        <h1><i class="bi bi-megaphone"></i> Listas de Difusión</h1>
        <p class="text-muted">Gestiona tus listas de contactos para envío de difusiones masivas.</p>
    </div>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Difusiones</h5>
                    <h2 id="cardTotalDifusiones"><?php echo $stats['total_broadcasts'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Completadas</h5>
                    <h2 id="cardCompletadas"><?php echo $stats['completed'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">En Progreso</h5>
                    <h2 id="cardEnProgreso"><?php echo $stats['in_progress'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Mensajes Enviados</h5>
                    <h2 id="cardMensajesEnviados"><?php echo $stats['total_sent'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Lista de listas -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-list"></i> Listas de Difusión</h5>
            <div class="d-flex gap-2">
                <form class="d-flex" method="GET">
                    <input type="hidden" name="action" value="list">
                    <input type="text" name="search" class="form-control me-2" placeholder="Buscar listas..." 
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    <button type="submit" class="btn btn-secondary">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
                <a href="?action=create" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Nueva Lista
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="d-flex gap-2 mb-3">
                <a href="?action=send" class="btn btn-primary">
                    <i class="bi bi-megaphone"></i> Enviar Difusión
                </a>
                <form method="POST" onsubmit="return confirmAutoCreate()">
                    <input type="hidden" name="auto_create_batches" value="1">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-collection"></i> Crear difusiones automáticas
                    </button>
                </form>
            </div>
            <?php if (empty($lists)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <h4 class="text-muted mt-3">No hay listas de difusión</h4>
                    <p class="text-muted">Crea tu primera lista para comenzar a enviar difusiones masivas.</p>
                    <a href="?action=create" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Crear Primera Lista
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Contactos</th>
                                <th>Estado</th>
                                <th>Creada</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lists as $list): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($list['name']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($list['description'] ?: 'Sin descripción'); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $list['contact_count']; ?> contactos</span>
                                    </td>
                                    <td>
                                        <?php if ($list['is_active']): ?>
                                            <span class="badge bg-success">Activa</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactiva</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y H:i', strtotime($list['created_at'])); ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="?action=send&list_id=<?php echo $list['id']; ?>" 
                                               class="btn btn-success btn-sm" title="Enviar Difusión">
                                                <i class="bi bi-megaphone"></i>
                                            </a>
                                            <a href="?action=edit&id=<?php echo $list['id']; ?>" 
                                               class="btn btn-light btn-sm" title="Editar">
                                                <i class="bi bi-pencil text-dark"></i>
                                            </a>
                                            <a href="?action=view&id=<?php echo $list['id']; ?>" 
                                               class="btn btn-light btn-sm" title="Ver">
                                                <i class="bi bi-eye text-dark"></i>
                                            </a>
                                            <button type="button" class="btn btn-light btn-sm" 
                                                    onclick="confirmDelete(<?php echo $list['id']; ?>, '<?php echo htmlspecialchars($list['name']); ?>')" 
                                                    title="Eliminar">
                                                <i class="bi bi-trash text-danger"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que quieres eliminar la lista "<span id="listName"></span>"?</p>
                <p class="text-danger"><strong>Esta acción no se puede deshacer.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="list_id" id="deleteListId">
                    <button type="submit" name="delete_list" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(listId, listName) {
    document.getElementById('deleteListId').value = listId;
    document.getElementById('listName').textContent = listName;
    const modalEl = document.getElementById('deleteModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    // Limpieza de backdrops antes y después
    setTimeout(() => (window.limpiarBackdrops && window.limpiarBackdrops()), 500);
    modalEl.addEventListener('hidden.bs.modal', () => (window.limpiarBackdrops && window.limpiarBackdrops()), { once: true });
}

// Refuerza el cierre del modal de eliminación para limpiar el estado visual
const deleteModalEl = document.getElementById('deleteModal');
if (deleteModalEl) {
    const safeClean = () => (window.limpiarBackdrops && window.limpiarBackdrops());
    deleteModalEl.addEventListener('hidden.bs.modal', safeClean);
    deleteModalEl.addEventListener('hide.bs.modal', safeClean);
}

// --- LIMPIEZA GLOBAL DE MODALES ATASCADOS (backdrop) ---
const safeGlobalClean = () => (window.limpiarBackdrops && window.limpiarBackdrops());
document.addEventListener('hidden.bs.modal', safeGlobalClean);
document.addEventListener('show.bs.modal', safeGlobalClean);
window.limpiarBackdrops = window.limpiarBackdrops || safeGlobalClean;

function confirmAutoCreate() {
    return confirm('¿Crear listas automáticas de 500 contactos sin repetir y con nombres secuenciales?');
}
</script> 