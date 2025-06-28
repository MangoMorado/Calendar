<?php
// Vista para editar una lista de difusión
$currentList = $data['currentList'];
$contactsInList = $data['contactsInList'];
$availableContacts = $data['availableContacts'];
$action = $_GET['action'] ?? 'edit';
?>

<div class="container">
    <div class="config-header">
        <h1><i class="bi bi-megaphone"></i> Listas de Difusión</h1>
        <p class="text-muted">Gestiona tus listas de contactos para envío de difusiones masivas.</p>
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

    <!-- Formulario para editar lista -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-pencil"></i> 
                        <?php echo $action === 'edit' ? 'Editar' : 'Ver'; ?> Lista
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="list_id" value="<?php echo $currentList['id']; ?>">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre de la lista *</label>
                            <input type="text" class="form-control" id="name" name="name" required 
                                   value="<?php echo htmlspecialchars($currentList['name']); ?>"
                                   <?php echo $action === 'view' ? 'readonly' : ''; ?>>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Descripción</label>
                            <textarea class="form-control" id="description" name="description" rows="3"
                                      <?php echo $action === 'view' ? 'readonly' : ''; ?>><?php echo htmlspecialchars($currentList['description']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                                       <?php echo $currentList['is_active'] ? 'checked' : ''; ?>
                                       <?php echo $action === 'view' ? 'disabled' : ''; ?>>
                                <label class="form-check-label" for="is_active">
                                    Lista activa
                                </label>
                            </div>
                        </div>
                        
                        <?php if ($action === 'edit'): ?>
                            <div class="d-flex gap-2">
                                <button type="submit" name="update_list" class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> Actualizar
                                </button>
                                <a href="?action=list" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Volver
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="d-flex gap-2">
                                <a href="?action=edit&id=<?php echo $currentList['id']; ?>" class="btn btn-primary">
                                    <i class="bi bi-pencil"></i> Editar
                                </a>
                                <a href="?action=list" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Volver
                                </a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <!-- Gestión de contactos -->
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
                            <table class="table table-sm align-middle" id="contactsInListTable">
                                <thead>
                                    <tr>
                                        <th>NOMBRE</th>
                                        <th>NÚMERO</th>
                                        <th class="text-center">ELIMINAR</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contactsInList as $contact): ?>
                                        <tr data-contact-id="<?php echo $contact['id']; ?>">
                                            <td><?php echo htmlspecialchars($contact['pushName'] ?: 'Sin nombre'); ?></td>
                                            <td><?php echo htmlspecialchars($contact['number']); ?></td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-link text-danger btn-sm delete-contact-btn" title="Eliminar contacto">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <script>
                        // Eliminar contacto de la lista vía AJAX
                        document.querySelectorAll('.delete-contact-btn').forEach(btn => {
                            btn.addEventListener('click', function() {
                                if (!confirm('¿Seguro que deseas eliminar este contacto de la lista?')) return;
                                const row = this.closest('tr');
                                const contactId = row.getAttribute('data-contact-id');
                                const listId = <?php echo (int)$currentList['id']; ?>;
                                const formData = new FormData();
                                formData.append('list_id', listId);
                                formData.append('contact_ids[]', contactId);
                                formData.append('remove_contacts', '1');
                                fetch(window.location.pathname + window.location.search, {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(r => r.text())
                                .then(() => {
                                    row.remove();
                                });
                            });
                        });
                        </script>
                    <?php else: ?>
                        <p class="text-muted">No hay contactos en esta lista.</p>
                    <?php endif; ?>
                    
                    <?php if ($action === 'edit'): ?>
                        <hr>
                        <h6>Agregar contactos</h6>
                        <form id="addContactsForm" method="POST">
                            <input type="hidden" name="list_id" value="<?php echo $currentList['id']; ?>">
                            <div class="mb-3">
                                <label for="contact_search" class="form-label">Buscar contacto</label>
                                <input type="text" class="form-control mb-2" id="contact_search" placeholder="Buscar por nombre o número...">
                                <div class="mb-2">
                                    <button type="button" class="btn btn-success btn-sm me-2" id="select_all_contacts">Seleccionar todos</button>
                                    <button type="button" class="btn btn-secondary btn-sm" id="deselect_all_contacts">Deseleccionar todos</button>
                                </div>
                                <label for="contact_ids" class="form-label">Seleccionar contactos</label>
                                <select class="form-select" id="contact_ids" name="contact_ids[]" multiple size="5">
                                    <?php foreach ($availableContacts as $contact): ?>
                                        <option value="<?php echo $contact['id']; ?>">
                                            <?php echo htmlspecialchars($contact['pushName'] ?: 'Sin nombre'); ?> 
                                            (<?php echo htmlspecialchars($contact['number']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">
                                    Mantén presionado Ctrl (Cmd en Mac) para seleccionar múltiples contactos
                                </small>
                            </div>
                            <div class="mb-3" id="progressContainer" style="display:none;">
                                <label class="form-label">Progreso de carga</label>
                                <div class="progress">
                                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%">0%</div>
                                </div>
                                <div id="progressText" class="mt-2 text-center text-muted"></div>
                            </div>
                            <button type="submit" name="add_contacts" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-circle"></i> Agregar Contactos
                            </button>
                        </form>
                        <script>
                        // Filtro en tiempo real para el select de contactos
                        document.getElementById('contact_search').addEventListener('input', function() {
                            const search = this.value.toLowerCase();
                            const select = document.getElementById('contact_ids');
                            for (let option of select.options) {
                                const text = option.text.toLowerCase();
                                option.style.display = text.includes(search) ? '' : 'none';
                            }
                        });
                        // Seleccionar todos los contactos visibles
                        document.getElementById('select_all_contacts').addEventListener('click', function() {
                            const select = document.getElementById('contact_ids');
                            for (let option of select.options) {
                                if (option.style.display !== 'none') {
                                    option.selected = true;
                                }
                            }
                        });
                        // Deseleccionar todos los contactos visibles
                        document.getElementById('deselect_all_contacts').addEventListener('click', function() {
                            const select = document.getElementById('contact_ids');
                            for (let option of select.options) {
                                if (option.style.display !== 'none') {
                                    option.selected = false;
                                }
                            }
                        });
                        // Procesamiento en lotes al enviar el formulario
                        document.getElementById('addContactsForm').addEventListener('submit', function(e) {
                            e.preventDefault();
                            const select = document.getElementById('contact_ids');
                            const selected = Array.from(select.options).filter(opt => opt.selected).map(opt => opt.value);
                            if (selected.length === 0) {
                                alert('Debes seleccionar al menos un contacto.');
                                return;
                            }
                            const listId = this.list_id.value;
                            const batchSize = 500;
                            let current = 0;
                            document.getElementById('progressContainer').style.display = '';
                            function sendBatch() {
                                const batch = selected.slice(current, current + batchSize);
                                const formData = new FormData();
                                formData.append('list_id', listId);
                                batch.forEach(id => formData.append('contact_ids[]', id));
                                formData.append('add_contacts', '1');
                                fetch(window.location.pathname + window.location.search, {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(r => r.text())
                                .then(() => {
                                    current += batchSize;
                                    const percent = Math.min(100, Math.round((current / selected.length) * 100));
                                    document.getElementById('progressBar').style.width = percent + '%';
                                    document.getElementById('progressBar').textContent = percent + '%';
                                    document.getElementById('progressText').textContent = `Cargando ${Math.min(current, selected.length)} de ${selected.length} contactos...`;
                                    if (current < selected.length) {
                                        sendBatch();
                                    } else {
                                        document.getElementById('progressText').textContent = '¡Carga completada!';
                                        setTimeout(() => window.location.reload(), 1000);
                                    }
                                });
                            }
                            // Iniciar el proceso
                            document.getElementById('progressBar').style.width = '0%';
                            document.getElementById('progressBar').textContent = '0%';
                            document.getElementById('progressText').textContent = 'Iniciando carga...';
                            sendBatch();
                        });
                        </script>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div> 