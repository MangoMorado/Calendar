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
$action = $_GET['action'] ?? 'select_list';
$message = '';
$error = '';

// Obtener listas activas del usuario
$activeLists = $broadcastListModel->getListsByUser($currentUser['id']);
$activeLists = array_filter($activeLists, function($list) {
    return $list['is_active'] && $list['contact_count'] > 0;
});

// Procesar envío de difusión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_broadcast'])) {
    $listId = (int)($_POST['list_id'] ?? 0);
    $messageText = trim($_POST['message'] ?? '');
    $image = $_FILES['image'] ?? null;
    
    if (empty($listId)) {
        $error = 'Debes seleccionar una lista';
    } elseif (empty($messageText) && !$image) {
        $error = 'Debes escribir un mensaje o seleccionar una imagen';
    } else {
        // Verificar que la lista existe y pertenece al usuario
        $selectedList = $broadcastListModel->getListById($listId, $currentUser['id']);
        if (!$selectedList || !$selectedList['is_active']) {
            $error = 'Lista no válida o inactiva';
        } else {
            // Obtener contactos de la lista
            $contacts = $broadcastListModel->getContactsInList($listId);
            if (empty($contacts)) {
                $error = 'La lista seleccionada no tiene contactos';
            } else {
                // Procesar imagen si se subió
                $imagePath = null;
                if ($image && $image['tmp_name']) {
                    // Validar tipo de archivo
                    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    $fileType = mime_content_type($image['tmp_name']);
                    
                    if (!in_array($fileType, $allowedTypes)) {
                        $error = 'Tipo de archivo no válido. Solo se permiten imágenes JPG, PNG y GIF.';
                    } elseif ($image['size'] > 5 * 1024 * 1024) { // 5MB
                        $error = 'El archivo es demasiado grande. Máximo 5MB permitido.';
                    } elseif ($image['error'] !== UPLOAD_ERR_OK) {
                        $error = 'Error al subir el archivo: ' . $image['error'];
                    } else {
                        $uploadsDir = __DIR__ . '/uploads';
                        if (!is_dir($uploadsDir)) {
                            if (!mkdir($uploadsDir, 0777, true)) {
                                $error = 'Error al crear el directorio de uploads';
                            }
                        }
                        
                        if (!$error) {
                            $ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
                            $filename = 'broadcast_' . uniqid() . '.' . $ext;
                            $imagePath = 'uploads/' . $filename;
                            $fullPath = __DIR__ . '/' . $imagePath;
                            
                            if (!move_uploaded_file($image['tmp_name'], $fullPath)) {
                                $error = 'Error al subir la imagen al servidor';
                            } else {
                                // Verificar que el archivo se subió correctamente
                                if (!file_exists($fullPath)) {
                                    $error = 'Error: El archivo no se guardó correctamente';
                                }
                            }
                        }
                    }
                }
                if (!$error) {
                    // Crear registro de difusión
                    $broadcastData = [
                        'list_id' => $listId,
                        'message' => $messageText,
                        'image_path' => $imagePath,
                        'total_contacts' => count($contacts),
                        'user_id' => $currentUser['id'],
                        'status' => 'pending'
                    ];
                    $broadcastId = $broadcastHistoryModel->createBroadcast($broadcastData);
                    if ($broadcastId) {
                        // Crear detalles de envío para cada contacto
                        foreach ($contacts as $contact) {
                            $detailData = [
                                'broadcast_id' => $broadcastId,
                                'contact_id' => $contact['id'],
                                'contact_number' => $contact['number'],
                                'status' => 'pending',
                                'error_message' => null,
                                'sent_at' => null
                            ];
                            $broadcastHistoryModel->addBroadcastDetail($detailData);
                        }
                        // Redirigir al proceso de envío
                        header("Location: process_broadcast.php?id=" . $broadcastId);
                        exit;
                    } else {
                        $error = 'Error al crear el registro de difusión';
                    }
                }
            }
        }
    }
}

// Definir título de la página
$pageTitle = 'Enviar Difusión | Mundo Animal';
include 'includes/header.php';
?>
<div class="container">
    <div class="config-header">
        <h1><i class="bi bi-send"></i> Enviar Difusión</h1>
        <p class="text-muted">Envía mensajes masivos a tus listas de contactos.</p>
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
    <!-- Formulario de envío -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-megaphone"></i> Nueva Difusión</h5>
        </div>
        <div class="card-body">
            <?php if (empty($activeLists)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <h4 class="text-muted mt-3">No hay listas disponibles</h4>
                    <p class="text-muted">Necesitas crear listas de difusión con contactos para poder enviar mensajes.</p>
                    <a href="broadcast_lists.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Gestionar Listas
                    </a>
                </div>
            <?php else: ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="list_id" class="form-label">Seleccionar Lista *</label>
                                <select class="form-select" id="list_id" name="list_id" required>
                                    <option value="">Selecciona una lista...</option>
                                    <?php foreach ($activeLists as $list): ?>
                                        <option value="<?php echo $list['id']; ?>">
                                            <?php echo htmlspecialchars($list['name']); ?> (<?php echo $list['contact_count']; ?> contactos)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Solo se muestran listas activas con contactos</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Información de la Lista</label>
                                <div id="list-info" class="alert alert-info" style="display: none;">
                                    <div id="list-details"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Mensaje</label>
                        <textarea class="form-control" id="message" name="message" rows="4" placeholder="Escribe tu mensaje aquí..."></textarea>
                        <small class="form-text text-muted">El mensaje es opcional si vas a enviar solo una imagen</small>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">Imagen (opcional)</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <small class="form-text text-muted">Formatos soportados: JPG, PNG, GIF. Máximo 5MB</small>
                    </div>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Importante:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Los mensajes se enviarán a todos los contactos de la lista seleccionada</li>
                            <li>El proceso puede tomar varios minutos dependiendo del número de contactos</li>
                            <li>Se mostrará el progreso en tiempo real</li>
                            <li>Puedes cancelar el envío en cualquier momento</li>
                        </ul>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" name="send_broadcast" class="btn btn-success">
                            <i class="bi bi-send"></i> Iniciar Envío
                        </button>
                        <a href="broadcast_lists.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver a Listas
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <!-- Historial de difusiones recientes -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Difusiones Recientes</h5>
        </div>
        <div class="card-body">
            <?php $recentBroadcasts = $broadcastHistoryModel->getRecentBroadcasts($currentUser['id'], 24); ?>
            <?php if (empty($recentBroadcasts)): ?>
                <p class="text-muted">No hay difusiones recientes.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Lista</th>
                                <th>Mensaje</th>
                                <th>Estado</th>
                                <th>Progreso</th>
                                <th>Iniciada</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentBroadcasts as $broadcast): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($broadcast['list_name'] ?: 'Sin lista'); ?></strong></td>
                                    <td><?php $messagePreview = htmlspecialchars($broadcast['message']); echo strlen($messagePreview) > 50 ? substr($messagePreview, 0, 50) . '...' : $messagePreview; ?></td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        $statusText = '';
                                        switch ($broadcast['status']) {
                                            case 'pending': $statusClass = 'bg-secondary'; $statusText = 'Pendiente'; break;
                                            case 'in_progress': $statusClass = 'bg-warning'; $statusText = 'En Progreso'; break;
                                            case 'completed': $statusClass = 'bg-success'; $statusText = 'Completada'; break;
                                            case 'failed': $statusClass = 'bg-danger'; $statusText = 'Fallida'; break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($broadcast['total_contacts'] > 0): ?>
                                            <?php $sent = $broadcast['sent_successfully'] + $broadcast['sent_failed']; $percentage = round(($sent / $broadcast['total_contacts']) * 100); ?>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar" style="width: <?php echo $percentage; ?>%">
                                                    <?php echo $percentage; ?>%
                                                </div>
                                            </div>
                                            <small class="text-muted"><?php echo $sent; ?>/<?php echo $broadcast['total_contacts']; ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($broadcast['started_at'])); ?></td>
                                    <td>
                                        <a href="broadcast_details.php?id=<?php echo $broadcast['id']; ?>" class="btn btn-sm btn-outline-info" title="Ver Detalles">
                                            <i class="bi bi-eye"></i>
                                        </a>
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
<script>
// Mostrar información de la lista seleccionada
document.getElementById('list_id').addEventListener('change', function() {
    const listId = this.value;
    const listInfo = document.getElementById('list-info');
    const listDetails = document.getElementById('list-details');
    if (listId) {
        const selectedOption = this.options[this.selectedIndex];
        listDetails.innerHTML = `<strong>Lista seleccionada:</strong> ${selectedOption.text}<br><small class="text-muted">Los mensajes se enviarán a todos los contactos de esta lista</small>`;
        listInfo.style.display = 'block';
    } else {
        listInfo.style.display = 'none';
    }
});
// Validación del formulario
document.querySelector('form').addEventListener('submit', function(e) {
    const message = document.getElementById('message').value.trim();
    const image = document.getElementById('image').files[0];
    if (!message && !image) {
        e.preventDefault();
        alert('Debes escribir un mensaje o seleccionar una imagen');
        return false;
    }
    if (image && image.size > 5 * 1024 * 1024) { // 5MB
        e.preventDefault();
        alert('La imagen no puede ser mayor a 5MB');
        return false;
    }
    return true;
});
</script>
<?php include 'includes/footer.php'; ?>
