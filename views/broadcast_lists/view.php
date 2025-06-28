<?php
// Vista para ver una lista de difusión (solo lectura)
$currentList = $data['currentList'];
$contactsInList = $data['contactsInList'];
?>

<div class="container">
    <div class="config-header">
        <h1><i class="bi bi-megaphone"></i> Listas de Difusión</h1>
        <p class="text-muted">Vista de la lista seleccionada.</p>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-eye"></i> Ver Lista</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre de la lista</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentList['name']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" rows="3" readonly><?php echo htmlspecialchars($currentList['description']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado</label><br>
                        <?php if ($currentList['is_active']): ?>
                            <span class="badge bg-success">Activa</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactiva</span>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Creada</label>
                        <input type="text" class="form-control" value="<?php echo date('d/m/Y H:i', strtotime($currentList['created_at'])); ?>" readonly>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="?action=edit&id=<?php echo $currentList['id']; ?>" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Editar
                        </a>
                        <a href="?action=list" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-people"></i> Contactos en la Lista</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Total: <?php echo count($contactsInList); ?> contactos</strong>
                    </div>
                    <?php if (!empty($contactsInList)): ?>
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>NOMBRE</th>
                                        <th>NÚMERO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contactsInList as $contact): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($contact['pushName'] ?: 'Sin nombre'); ?></td>
                                            <td><?php echo htmlspecialchars($contact['number']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No hay contactos en esta lista.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div> 