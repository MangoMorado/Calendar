<?php
// Incluir archivos de configuración, funciones y autenticación
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'models/BroadcastListModel.php';
require_once 'models/BroadcastHistoryModel.php';

// Verificar que el usuario esté autenticado
requireAuth();

// Obtener información del usuario actual
$currentUser = getCurrentUser();

// Inicializar modelos
$broadcastListModel = new BroadcastListModel($conn);
$broadcastHistoryModel = new BroadcastHistoryModel($conn);

// Procesar acciones
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Obtener estadísticas
$stats = $broadcastHistoryModel->getBroadcastStats($currentUser['id']);

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_list'])) {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name)) {
            $error = 'El nombre de la lista es requerido';
        } else {
            $listData = [
                'name' => $name,
                'description' => $description,
                'user_id' => $currentUser['id']
            ];
            
            $listId = $broadcastListModel->createList($listData);
            if ($listId) {
                $message = 'Lista creada correctamente';
                $action = 'edit';
                $_GET['id'] = $listId;
            } else {
                $error = 'Error al crear la lista';
            }
        }
    } elseif (isset($_POST['update_list'])) {
        $listId = (int)($_POST['list_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name)) {
            $error = 'El nombre de la lista es requerido';
        } elseif (!$broadcastListModel->canAccessList($listId, $currentUser['id'])) {
            $error = 'No tienes permisos para editar esta lista';
        } else {
            $listData = [
                'name' => $name,
                'description' => $description,
                'is_active' => $isActive
            ];
            
            if ($broadcastListModel->updateList($listId, $listData, $currentUser['id'])) {
                $message = 'Lista actualizada correctamente';
            } else {
                $error = 'Error al actualizar la lista';
            }
        }
    } elseif (isset($_POST['delete_list'])) {
        $listId = (int)($_POST['list_id'] ?? 0);
        
        if (!$broadcastListModel->canAccessList($listId, $currentUser['id'])) {
            $error = 'No tienes permisos para eliminar esta lista';
        } else {
            if ($broadcastListModel->deleteList($listId, $currentUser['id'])) {
                $message = 'Lista eliminada correctamente';
                $action = 'list';
            } else {
                $error = 'Error al eliminar la lista';
            }
        }
    } elseif (isset($_POST['add_contacts'])) {
        $listId = (int)($_POST['list_id'] ?? 0);
        $contactIds = $_POST['contact_ids'] ?? [];
        
        if (!$broadcastListModel->canAccessList($listId, $currentUser['id'])) {
            $error = 'No tienes permisos para modificar esta lista';
        } else {
            if ($broadcastListModel->addContactsToList($listId, $contactIds)) {
                $message = 'Contactos agregados correctamente';
            } else {
                $error = 'Error al agregar contactos';
            }
        }
    } elseif (isset($_POST['remove_contacts'])) {
        $listId = (int)($_POST['list_id'] ?? 0);
        $contactIds = $_POST['contact_ids'] ?? [];
        
        if (!$broadcastListModel->canAccessList($listId, $currentUser['id'])) {
            $error = 'No tienes permisos para modificar esta lista';
        } else {
            if ($broadcastListModel->removeContactsFromList($listId, $contactIds)) {
                $message = 'Contactos removidos correctamente';
            } else {
                $error = 'Error al remover contactos';
            }
        }
    }
}

// Obtener datos según la acción
$lists = [];
$currentList = null;
$contactsInList = [];
$availableContacts = [];

if ($action === 'list') {
    $searchTerm = $_GET['search'] ?? '';
    if (!empty($searchTerm)) {
        $lists = $broadcastListModel->searchLists($currentUser['id'], $searchTerm);
    } else {
        $lists = $broadcastListModel->getListsByUser($currentUser['id']);
    }
} elseif ($action === 'create') {
    // No necesitamos datos adicionales para crear
} elseif ($action === 'edit' || $action === 'view') {
    $listId = (int)($_GET['id'] ?? 0);
    $currentList = $broadcastListModel->getListById($listId, $currentUser['id']);
    
    if (!$currentList) {
        $error = 'Lista no encontrada o no tienes permisos para acceder';
        $action = 'list';
    } else {
        $contactsInList = $broadcastListModel->getContactsInList($listId);
        $availableContacts = $broadcastListModel->getAvailableContacts($listId);
    }
}

// Definir título de la página
$pageTitle = 'Listas de Difusión | Mundo Animal';

// Incluir el header
include 'includes/header.php';
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
                    <h2><?php echo $stats['total_broadcasts'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Completadas</h5>
                    <h2><?php echo $stats['completed'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">En Progreso</h5>
                    <h2><?php echo $stats['in_progress'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Mensajes Enviados</h5>
                    <h2><?php echo $stats['total_sent'] ?? 0; ?></h2>
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

    <!-- Contenido según la acción -->
    <?php if ($action === 'list'): ?>
        <!-- Lista de listas -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list"></i> Mis Listas de Difusión</h5>
                <div class="d-flex gap-2">
                    <form class="d-flex" method="GET">
                        <input type="hidden" name="action" value="list">
                        <input type="text" name="search" class="form-control me-2" placeholder="Buscar listas..." 
                               value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        <button type="submit" class="btn btn-outline-secondary">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>
                    <a href="?action=create" class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> Nueva Lista
                    </a>
                </div>
            </div>
            <div class="card-body">
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

    <?php elseif ($action === 'create'): ?>
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

    <?php elseif ($action === 'edit' || $action === 'view'): ?>
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
    <?php endif; ?>

    <br>

    <!-- NUEVA CARD: Enviar Difusión -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-megaphone"></i> Enviar Difusión</h5>
        </div>
        <div class="card-body">
            <form id="formDifusion" enctype="multipart/form-data" autocomplete="off">
                <div class="mb-3">
                    <label for="listaDifusion" class="form-label">Lista de difusión</label>
                    <select id="listaDifusion" name="listaDifusion" class="form-select" required>
                        <option value="">Selecciona una lista...</option>
                        <?php foreach ($lists as $list): ?>
                            <option value="<?php echo $list['id']; ?>"> 
                                <?php echo htmlspecialchars($list['name']); ?> (<?php echo $list['contact_count']; ?> contactos)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3" id="contactosDifusionContainer" style="display:none;">
                    <label class="form-label">Contactos de la lista</label>
                    <div id="contactosDifusionLista"></div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-success btn-sm me-2" id="selectAllContacts">
                            <i class="bi bi-check-all"></i> Seleccionar todos
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm me-2" id="deselectAllContacts">
                            <i class="bi bi-x-lg"></i> Deseleccionar todos
                        </button>
                        <span class="text-muted" id="contactosSeleccionados">0 contactos seleccionados</span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="mensajeDifusion" class="form-label">Mensaje de difusión</label>
                    <textarea id="mensajeDifusion" name="mensaje" class="form-control" rows="4" 
                              placeholder="Escribe tu mensaje aquí..."></textarea>
                    <div class="form-text">
                        <span id="caracteresRestantes">Sin límite de caracteres</span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="imagenDifusion" class="form-label">Imagen (opcional)</label>
                    <input type="file" id="imagenDifusion" name="imagen" class="form-control" accept="image/*">
                    <div class="form-text">
                        Formatos soportados: JPG, PNG, GIF. Tamaño máximo: 5MB
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success" id="btnEnviarDifusion">
                        <i class="bi bi-send"></i> Enviar difusión
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="btnVistaPrevia">
                        <i class="bi bi-eye"></i> Vista previa
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de progreso de envío de difusión -->
    <div class="modal fade" id="modalProgresoEnvio" tabindex="-1" aria-labelledby="modalProgresoEnvioLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalProgresoEnvioLabel">
                        <i class="bi bi-send"></i> Enviando difusión
                    </h5>
                </div>
                <div class="modal-body">
                    <div class="progress mb-3">
                        <div id="barraProgresoEnvio" class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%">0%</div>
                    </div>
                    <div id="estadoEnvioDifusion" class="text-center text-muted">Preparando envío...</div>
                    <div id="detallesEnvio" class="mt-3 small text-muted" style="display:none;">
                        <div class="row">
                            <div class="col-6">
                                <i class="bi bi-check-circle text-success"></i> 
                                <span id="enviadosExitosos">0</span> enviados
                            </div>
                            <div class="col-6">
                                <i class="bi bi-x-circle text-danger"></i> 
                                <span id="enviadosFallidos">0</span> fallidos
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" id="modalFooter" style="display:none;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <a href="broadcast_details.php?id=" id="btnVerDetalles" class="btn btn-primary">
                        <i class="bi bi-list-ul"></i> Ver detalles
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de vista previa -->
    <div class="modal fade" id="modalVistaPrevia" tabindex="-1" aria-labelledby="modalVistaPreviaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVistaPreviaLabel">
                        <i class="bi bi-eye"></i> Vista previa del mensaje
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="card">
                        <div class="card-body">
                            <div id="vistaPreviaContenido">
                                <!-- El contenido se cargará dinámicamente -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
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
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Manejo de selección de lista y carga de contactos
const listas = <?php echo json_encode($lists); ?>;
const contactosPorLista = {};
<?php foreach ($lists as $list): ?>
    contactosPorLista[<?php echo $list['id']; ?>] = <?php echo json_encode($broadcastListModel->getContactsInList($list['id'])); ?>;
<?php endforeach; ?>

const listaSelect = document.getElementById('listaDifusion');
const contactosContainer = document.getElementById('contactosDifusionContainer');
const contactosListaDiv = document.getElementById('contactosDifusionLista');

listaSelect.addEventListener('change', function() {
    const listId = this.value;
    if (!listId || !contactosPorLista[listId] || contactosPorLista[listId].length === 0) {
        contactosContainer.style.display = 'none';
        contactosListaDiv.innerHTML = '<div class="alert alert-warning">No hay contactos en esta lista.</div>';
        return;
    }
    
    contactosContainer.style.display = '';
    let html = '<div class="table-responsive" style="max-height:200px;overflow-y:auto;">';
    html += '<table class="table table-sm"><thead><tr><th><input type="checkbox" id="selectAllCheckbox" checked></th><th>Nombre</th><th>Número</th></tr></thead><tbody>';
    
    contactosPorLista[listId].forEach(c => {
        html += `<tr>
            <td><input type='checkbox' class='chk-contacto-difusion' value='${c.number}' checked></td>
            <td>${c.pushName ? c.pushName : '<span class="text-muted">Sin nombre</span>'}</td>
            <td>${c.number}</td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    contactosListaDiv.innerHTML = html;
    
    // Actualizar contador de contactos seleccionados
    updateContactosSeleccionados();
    
    // Manejar checkbox "seleccionar todos"
    document.getElementById('selectAllCheckbox').addEventListener('change', function() {
        document.querySelectorAll('.chk-contacto-difusion').forEach(chk => {
            chk.checked = this.checked;
        });
        updateContactosSeleccionados();
    });
    
    // Manejar cambios en checkboxes individuales
    document.querySelectorAll('.chk-contacto-difusion').forEach(chk => {
        chk.addEventListener('change', updateContactosSeleccionados);
    });
});

function updateContactosSeleccionados() {
    const seleccionados = document.querySelectorAll('.chk-contacto-difusion:checked').length;
    const total = document.querySelectorAll('.chk-contacto-difusion').length;
    document.getElementById('contactosSeleccionados').textContent = `${seleccionados} de ${total} contactos seleccionados`;
}

document.getElementById('selectAllContacts').onclick = function() {
    document.querySelectorAll('.chk-contacto-difusion').forEach(chk => chk.checked = true);
    document.getElementById('selectAllCheckbox').checked = true;
    updateContactosSeleccionados();
};

document.getElementById('deselectAllContacts').onclick = function() {
    document.querySelectorAll('.chk-contacto-difusion').forEach(chk => chk.checked = false);
    document.getElementById('selectAllCheckbox').checked = false;
    updateContactosSeleccionados();
};

// Contador de caracteres
document.getElementById('mensajeDifusion').addEventListener('input', function() {
    const maxChars = 4096; // Límite de WhatsApp
    const currentChars = this.value.length;
    const remaining = maxChars - currentChars;
    
    if (remaining < 0) {
        document.getElementById('caracteresRestantes').innerHTML = 
            `<span class="text-danger">${Math.abs(remaining)} caracteres de más</span>`;
    } else if (remaining < 100) {
        document.getElementById('caracteresRestantes').innerHTML = 
            `<span class="text-warning">${remaining} caracteres restantes</span>`;
    } else {
        document.getElementById('caracteresRestantes').textContent = `${remaining} caracteres restantes`;
    }
});

// Vista previa del mensaje
document.getElementById('btnVistaPrevia').addEventListener('click', function() {
    const mensaje = document.getElementById('mensajeDifusion').value.trim();
    const imagen = document.getElementById('imagenDifusion').files[0];
    const listId = document.getElementById('listaDifusion').value;
    
    if (!listId) {
        showNotification('Debes seleccionar una lista de difusión.', 'error');
        return;
    }
    
    if (!mensaje && !imagen) {
        showNotification('Debes ingresar un mensaje o seleccionar una imagen.', 'error');
        return;
    }
    
    let html = '<div class="whatsapp-message-preview">';
    
    if (imagen) {
        const reader = new FileReader();
        reader.onload = function(e) {
            html += `<div class="text-center mb-2">
                <img src="${e.target.result}" class="img-fluid rounded" style="max-height: 200px;">
            </div>`;
            
            if (mensaje) {
                html += `<div class="message-text">${mensaje.replace(/\n/g, '<br>')}</div>`;
            }
            
            html += '</div>';
            document.getElementById('vistaPreviaContenido').innerHTML = html;
            new bootstrap.Modal(document.getElementById('modalVistaPrevia')).show();
        };
        reader.readAsDataURL(imagen);
    } else {
        html += `<div class="message-text">${mensaje.replace(/\n/g, '<br>')}</div></div>`;
        document.getElementById('vistaPreviaContenido').innerHTML = html;
        new bootstrap.Modal(document.getElementById('modalVistaPrevia')).show();
    }
});

// Envío de difusión mejorado
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} notification-toast`;
    notification.style.cssText = `
        position: fixed; top: 20px; right: 20px; z-index: 9999; 
        min-width: 300px; animation: slideIn 0.3s ease;
    `;
    notification.innerHTML = `<i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i> ${message}`;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => { 
            if (notification.parentNode) notification.parentNode.removeChild(notification); 
        }, 300);
    }, 5000);
}

// --- LIMPIEZA GLOBAL DE MODALES ATASCADOS (backdrop) ---
function limpiarBackdrops() {
    document.querySelectorAll('.modal-backdrop').forEach(e => e.remove());
    document.body.classList.remove('modal-open');
}
document.addEventListener('hidden.bs.modal', limpiarBackdrops);
document.addEventListener('show.bs.modal', limpiarBackdrops);
window.limpiarBackdrops = limpiarBackdrops;

// --- CIERRE FORZADO DEL MODAL DE PROGRESO EN CASO DE ERROR ---
function cerrarModalProgreso() {
    const modalEl = document.getElementById('modalProgresoEnvio');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }
    limpiarBackdrops();
}

document.getElementById('formDifusion').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const mensaje = document.getElementById('mensajeDifusion').value.trim();
    const imagen = document.getElementById('imagenDifusion').files[0];
    const listId = document.getElementById('listaDifusion').value;
    const contactos = Array.from(document.querySelectorAll('.chk-contacto-difusion:checked')).map(chk => chk.value);
    
    // Validaciones
    if (!listId) {
        cerrarModalProgreso();
        showNotification('Debes seleccionar una lista de difusión.', 'error');
        return;
    }
    
    if (contactos.length === 0) {
        cerrarModalProgreso();
        showNotification('Debes seleccionar al menos un contacto.', 'error');
        return;
    }
    
    if (!mensaje && !imagen) {
        cerrarModalProgreso();
        showNotification('Debes ingresar un mensaje o seleccionar una imagen.', 'error');
        return;
    }
    
    // Confirmar envío
    if (!confirm(`¿Estás seguro de que quieres enviar la difusión a ${contactos.length} contactos?`)) {
        cerrarModalProgreso();
        return;
    }
    
    // Preparar datos
    const formData = new FormData();
    formData.append('list_id', listId);
    formData.append('message', mensaje);
    if (imagen) {
        formData.append('image', imagen);
    }
    contactos.forEach(contacto => {
        formData.append('selected_contacts[]', contacto);
    });
    
    // Mostrar modal de progreso
    const modal = new bootstrap.Modal(document.getElementById('modalProgresoEnvio'));
    document.getElementById('barraProgresoEnvio').style.width = '0%';
    document.getElementById('barraProgresoEnvio').textContent = '0%';
    document.getElementById('estadoEnvioDifusion').textContent = 'Iniciando envío...';
    document.getElementById('detallesEnvio').style.display = 'none';
    document.getElementById('modalFooter').style.display = 'none';
    modal.show();
    
    // Enviar difusión
    fetch('api/send_broadcast_bulk.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar progreso
            document.getElementById('barraProgresoEnvio').style.width = '100%';
            document.getElementById('barraProgresoEnvio').textContent = '100%';
            document.getElementById('estadoEnvioDifusion').textContent = '¡Difusión completada!';
            
            // Mostrar detalles
            document.getElementById('enviadosExitosos').textContent = data.data.sent_successfully;
            document.getElementById('enviadosFallidos').textContent = data.data.sent_failed;
            document.getElementById('detallesEnvio').style.display = 'block';
            
            // Configurar botón de detalles
            document.getElementById('btnVerDetalles').href = `broadcast_details.php?id=${data.data.broadcast_id}`;
            document.getElementById('modalFooter').style.display = 'block';
            
            // Mostrar notificación
            const mensaje = `Difusión completada. Enviados: ${data.data.sent_successfully}, Fallidos: ${data.data.sent_failed}`;
            showNotification(mensaje, data.data.sent_failed === 0 ? 'success' : 'warning');
            
            // Limpiar formulario si todo fue exitoso
            if (data.data.sent_failed === 0) {
                document.getElementById('formDifusion').reset();
                contactosContainer.style.display = 'none';
            }
        } else {
            cerrarModalProgreso();
            document.getElementById('estadoEnvioDifusion').textContent = 'Error: ' + data.message;
            showNotification('Error al enviar la difusión: ' + data.message, 'error');
        }
    })
    .catch(error => {
        cerrarModalProgreso();
        document.getElementById('estadoEnvioDifusion').textContent = 'Error de conexión';
        showNotification('Error de conexión: ' + error.message, 'error');
    });
});

// Estilos CSS para las notificaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    .whatsapp-message-preview {
        background: #f0f0f0;
        border-radius: 8px;
        padding: 15px;
        max-width: 300px;
        margin: 0 auto;
    }
    .message-text {
        background: white;
        padding: 10px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
`;
document.head.appendChild(style);
</script>

<?php include 'includes/footer.php'; ?>
