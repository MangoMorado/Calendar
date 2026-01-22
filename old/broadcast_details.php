<?php
// Incluir archivos de configuración, funciones y autenticación
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'models/BroadcastHistoryModel.php';
require_once 'models/BroadcastListModel.php';

// Verificar que el usuario esté autenticado
requireAuth();

// Obtener información del usuario actual
$currentUser = getCurrentUser();

// Inicializar modelos
$broadcastHistoryModel = new BroadcastHistoryModel($conn);
$broadcastListModel = new BroadcastListModel($conn);

// Obtener ID de la difusión
$broadcastId = (int) ($_GET['id'] ?? 0);

if (! $broadcastId) {
    header('Location: broadcast_lists.php');
    exit;
}

// Obtener información de la difusión
$broadcast = $broadcastHistoryModel->getBroadcastById($broadcastId, $currentUser['id']);

if (! $broadcast) {
    header('Location: broadcast_lists.php?error=Difusión no encontrada');
    exit;
}

// Obtener detalles de la difusión
$details = $broadcastHistoryModel->getBroadcastDetails($broadcastId);

// Calcular estadísticas
$totalContacts = count($details);
$sentSuccessfully = count(array_filter($details, function ($d) {
    return $d['status'] === 'sent';
}));
$sentFailed = count(array_filter($details, function ($d) {
    return $d['status'] === 'failed';
}));
$pending = count(array_filter($details, function ($d) {
    return $d['status'] === 'pending';
}));

// Definir título de la página
$pageTitle = 'Detalles de Difusión | Mundo Animal';

// Incluir el header
include 'includes/header.php';
?>

<div class="container">
    <div class="config-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1><i class="bi bi-megaphone"></i> Detalles de Difusión</h1>
                <p class="text-muted">Información detallada de la difusión enviada</p>
            </div>
            <a href="broadcast_lists.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver a Listas
            </a>
        </div>
    </div>

    <!-- Información general de la difusión -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Información de la Difusión</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Lista:</strong> <?php echo htmlspecialchars($broadcast['list_name']); ?></p>
                            <p><strong>Usuario:</strong> <?php echo htmlspecialchars($broadcast['user_name']); ?></p>
                            <p><strong>Estado:</strong> 
                                <?php
                                $statusColors = [
                                    'pending' => 'warning',
                                    'in_progress' => 'info',
                                    'completed' => 'success',
                                    'failed' => 'danger',
                                    'cancelled' => 'secondary',
                                ];
$statusLabels = [
    'pending' => 'Pendiente',
    'in_progress' => 'En Progreso',
    'completed' => 'Completada',
    'failed' => 'Fallida',
    'cancelled' => 'Cancelada',
];
$color = $statusColors[$broadcast['status']] ?? 'secondary';
$label = $statusLabels[$broadcast['status']] ?? 'Desconocido';
?>
                                <span class="badge bg-<?php echo $color; ?>"><?php echo $label; ?></span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Iniciada:</strong> <?php echo date('d/m/Y H:i:s', strtotime($broadcast['started_at'])); ?></p>
                            <?php if ($broadcast['completed_at']) { ?>
                                <p><strong>Completada:</strong> <?php echo date('d/m/Y H:i:s', strtotime($broadcast['completed_at'])); ?></p>
                            <?php } ?>
                            <p><strong>Duración:</strong> 
                                <?php
if ($broadcast['completed_at']) {
    $duration = strtotime($broadcast['completed_at']) - strtotime($broadcast['started_at']);
    echo gmdate('H:i:s', $duration);
} else {
    echo 'En progreso...';
}
?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if ($broadcast['message']) { ?>
                        <hr>
                        <h6>Mensaje:</h6>
                        <div class="alert alert-light">
                            <?php echo nl2br(htmlspecialchars($broadcast['message'])); ?>
                        </div>
                    <?php } ?>
                    
                    <?php if ($broadcast['image_path'] && file_exists($broadcast['image_path'])) { ?>
                        <hr>
                        <h6>Imagen:</h6>
                        <img src="<?php echo $broadcast['image_path']; ?>" class="img-fluid rounded" style="max-height: 200px;">
                    <?php } ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Estadísticas -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Estadísticas</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="border rounded p-3">
                                <h3 class="text-primary mb-1"><?php echo $totalContacts; ?></h3>
                                <small class="text-muted">Total</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="border rounded p-3">
                                <h3 class="text-success mb-1"><?php echo $sentSuccessfully; ?></h3>
                                <small class="text-muted">Enviados</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="border rounded p-3">
                                <h3 class="text-danger mb-1"><?php echo $sentFailed; ?></h3>
                                <small class="text-muted">Fallidos</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="border rounded p-3">
                                <h3 class="text-warning mb-1"><?php echo $pending; ?></h3>
                                <small class="text-muted">Pendientes</small>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($totalContacts > 0) { ?>
                        <div class="progress mb-2">
                            <?php
                            $successPercent = ($sentSuccessfully / $totalContacts) * 100;
                        $failedPercent = ($sentFailed / $totalContacts) * 100;
                        $pendingPercent = ($pending / $totalContacts) * 100;
                        ?>
                            <div class="progress-bar bg-success" style="width: <?php echo $successPercent; ?>%"></div>
                            <div class="progress-bar bg-danger" style="width: <?php echo $failedPercent; ?>%"></div>
                            <div class="progress-bar bg-warning" style="width: <?php echo $pendingPercent; ?>%"></div>
                        </div>
                        <small class="text-muted">
                            Éxito: <?php echo round($successPercent, 1); ?>% | 
                            Fallidos: <?php echo round($failedPercent, 1); ?>% | 
                            Pendientes: <?php echo round($pendingPercent, 1); ?>%
                        </small>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de contactos -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-people"></i> Detalles por Contacto</h5>
        </div>
        <div class="card-body">
            <?php if (empty($details)) { ?>
                <div class="text-center py-4">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <h4 class="text-muted mt-3">No hay detalles disponibles</h4>
                </div>
            <?php } else { ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Contacto</th>
                                <th>Número</th>
                                <th>Estado</th>
                                <th>Enviado</th>
                                <th>Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($details as $detail) { ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($detail['pushName'] ?: 'Sin nombre'); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($detail['number']); ?></td>
                                    <td>
                                        <?php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'sent' => 'success',
                                        'failed' => 'danger',
                                        'cancelled' => 'secondary',
                                    ];
                                $statusLabels = [
                                    'pending' => 'Pendiente',
                                    'sent' => 'Enviado',
                                    'failed' => 'Fallido',
                                    'cancelled' => 'Cancelado',
                                ];
                                $color = $statusColors[$detail['status']] ?? 'secondary';
                                $label = $statusLabels[$detail['status']] ?? 'Desconocido';
                                ?>
                                        <span class="badge bg-<?php echo $color; ?>"><?php echo $label; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($detail['sent_at']) { ?>
                                            <?php echo date('d/m/Y H:i:s', strtotime($detail['sent_at'])); ?>
                                        <?php } else { ?>
                                            <span class="text-muted">-</span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php if ($detail['error_message']) { ?>
                                            <span class="text-danger" title="<?php echo htmlspecialchars($detail['error_message']); ?>">
                                                <i class="bi bi-exclamation-triangle"></i> Ver error
                                            </span>
                                        <?php } else { ?>
                                            <span class="text-muted">-</span>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- Botones de acción -->
    <div class="mt-4">
        <a href="broadcast_lists.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver a Listas
        </a>
        <a href="broadcast_lists.php?action=edit&id=<?php echo $broadcast['list_id']; ?>" class="btn btn-primary">
            <i class="bi bi-pencil"></i> Editar Lista
        </a>
        <button type="button" class="btn btn-success" onclick="reenviarDifusion()">
            <i class="bi bi-arrow-repeat"></i> Reenviar Difusión
        </button>
    </div>
</div>

<!-- Modal para mostrar errores -->
<div class="modal fade" id="errorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles del Error</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="errorDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Función para reenviar la difusión
function reenviarDifusion() {
    if (confirm('¿Estás seguro de que quieres reenviar esta difusión? Esto enviará el mensaje nuevamente a todos los contactos.')) {
        // Aquí podrías implementar la lógica para reenviar
        alert('Función de reenvío en desarrollo');
    }
}

// Función para mostrar errores en modal
function showError(errorMessage) {
    document.getElementById('errorDetails').innerHTML = `<pre>${errorMessage}</pre>`;
    new bootstrap.Modal(document.getElementById('errorModal')).show();
}

// Agregar event listeners para mostrar errores
document.querySelectorAll('[title]').forEach(element => {
    if (element.textContent.includes('Ver error')) {
        element.addEventListener('click', function(e) {
            e.preventDefault();
            showError(this.getAttribute('title'));
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
