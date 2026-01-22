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

// Obtener ID de la difusión
$broadcastId = (int) ($_GET['id'] ?? 0);

if (! $broadcastId) {
    header('Location: send_broadcast.php');
    exit;
}

// Obtener información de la difusión
$broadcast = $broadcastHistoryModel->getBroadcastById($broadcastId, $currentUser['id']);

if (! $broadcast) {
    header('Location: send_broadcast.php');
    exit;
}

// Procesar acción AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'];

    if ($action === 'start_broadcast') {
        // Iniciar el proceso de envío
        $broadcastHistoryModel->updateBroadcastStatus($broadcastId, 'in_progress');

        echo json_encode([
            'success' => true,
            'message' => 'Difusión iniciada',
            'broadcast_id' => $broadcastId,
        ]);
        exit;

    } elseif ($action === 'send_message') {
        // Enviar mensaje individual
        $detailId = (int) ($_POST['detail_id'] ?? 0);
        $contactNumber = $_POST['contact_number'] ?? '';
        $message = $_POST['message'] ?? '';
        $imagePath = $_POST['image_path'] ?? '';

        // Obtener configuración de Evolution API
        $config = [];
        $sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('evolution_api_url', 'evolution_api_key', 'evolution_instance_name')";
        $result = mysqli_query($conn, $sql);
        while ($row = mysqli_fetch_assoc($result)) {
            $config[$row['setting_key']] = $row['setting_value'];
        }

        $evolutionApiUrl = $config['evolution_api_url'] ?? '';
        $evolutionApiKey = $config['evolution_api_key'] ?? '';
        $evolutionInstanceName = $config['evolution_instance_name'] ?? '';

        $response = ['success' => false, 'message' => 'Error desconocido'];

        if (empty($evolutionApiUrl) || empty($evolutionApiKey) || empty($evolutionInstanceName)) {
            $response = ['success' => false, 'message' => 'Configuración de Evolution API incompleta'];
        } else {
            // Verificar estado de la instancia
            $checkApiUrl = rtrim($evolutionApiUrl, '/').'/instance/connectionState/'.rawurlencode($evolutionInstanceName);
            $checkHeaders = ['apikey: '.$evolutionApiKey];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $checkApiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $checkHeaders);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $checkResponse = curl_exec($ch);
            $checkHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $instanceState = 'unknown';
            if ($checkHttpCode === 200) {
                $checkData = json_decode($checkResponse, true);
                $instanceState = $checkData['state'] ?? 'unknown';
            }

            if ($instanceState !== 'open') {
                $response = ['success' => false, 'message' => 'Instancia no conectada'];
            } else {
                // Enviar mensaje
                if ($imagePath && file_exists(__DIR__.'/'.$imagePath)) {
                    // Enviar imagen con mensaje
                    $apiUrl = rtrim($evolutionApiUrl, '/').'/message/sendMedia/'.rawurlencode($evolutionInstanceName);
                    $headers = ['apikey: '.$evolutionApiKey];

                    $postfields = [
                        'number' => $contactNumber,
                        'file' => new CURLFile(__DIR__.'/'.$imagePath),
                        'caption' => $message,
                        'mediatype' => 'image',
                    ];

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $apiUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                    $apiResponse = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlError = curl_error($ch);
                    curl_close($ch);

                    if ($httpCode === 200 || $httpCode === 201) {
                        $response = ['success' => true, 'message' => 'Mensaje enviado'];
                        $broadcastHistoryModel->updateBroadcastDetail($detailId, 'sent', null, date('Y-m-d H:i:s'));
                    } else {
                        $errorMsg = 'Error al enviar mensaje';
                        if ($curlError) {
                            $errorMsg .= ' (CURL: '.$curlError.')';
                        } else {
                            $errorMsg .= ' (HTTP: '.$httpCode.')';
                            if ($apiResponse) {
                                $errorData = json_decode($apiResponse, true);
                                if ($errorData && isset($errorData['message'])) {
                                    $errorMsg .= ' - '.$errorData['message'];
                                }
                            }
                        }
                        $response = ['success' => false, 'message' => $errorMsg];
                        $broadcastHistoryModel->updateBroadcastDetail($detailId, 'failed', $errorMsg, null);
                    }
                } else {
                    // Verificar si se esperaba una imagen pero no existe
                    if ($imagePath && ! file_exists(__DIR__.'/'.$imagePath)) {
                        $errorMsg = 'Error: La imagen no se encuentra en el servidor';
                        $response = ['success' => false, 'message' => $errorMsg];
                        $broadcastHistoryModel->updateBroadcastDetail($detailId, 'failed', $errorMsg, null);
                    } else {
                        // Enviar solo texto
                        $apiUrl = rtrim($evolutionApiUrl, '/').'/message/sendText/'.rawurlencode($evolutionInstanceName);
                        $headers = [
                            'apikey: '.$evolutionApiKey,
                            'Content-Type: application/json',
                        ];

                        $payload = [
                            'number' => $contactNumber,
                            'text' => $message,
                        ];

                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $apiUrl);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                        $apiResponse = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        $curlError = curl_error($ch);
                        curl_close($ch);

                        if ($httpCode === 200 || $httpCode === 201) {
                            $response = ['success' => true, 'message' => 'Mensaje enviado'];
                            $broadcastHistoryModel->updateBroadcastDetail($detailId, 'sent', null, date('Y-m-d H:i:s'));
                        } else {
                            $errorMsg = 'Error al enviar mensaje';
                            if ($curlError) {
                                $errorMsg .= ' (CURL: '.$curlError.')';
                            } else {
                                $errorMsg .= ' (HTTP: '.$httpCode.')';
                                if ($apiResponse) {
                                    $errorData = json_decode($apiResponse, true);
                                    if ($errorData && isset($errorData['message'])) {
                                        $errorMsg .= ' - '.$errorData['message'];
                                    }
                                }
                            }
                            $response = ['success' => false, 'message' => $errorMsg];
                            $broadcastHistoryModel->updateBroadcastDetail($detailId, 'failed', $errorMsg, null);
                        }
                    }
                }
            }
        }

        echo json_encode($response);
        exit;

    } elseif ($action === 'get_progress') {
        // Obtener progreso actual
        $details = $broadcastHistoryModel->getBroadcastDetails($broadcastId);

        $total = count($details);
        $sent = 0;
        $failed = 0;
        $pending = 0;

        foreach ($details as $detail) {
            switch ($detail['status']) {
                case 'sent':
                    $sent++;
                    break;
                case 'failed':
                    $failed++;
                    break;
                default:
                    $pending++;
                    break;
            }
        }

        $percentage = $total > 0 ? round((($sent + $failed) / $total) * 100) : 0;
        $status = $pending === 0 ? 'completed' : 'in_progress';

        if ($status === 'completed') {
            $broadcastHistoryModel->updateBroadcastStatus($broadcastId, 'completed', $sent, $failed);
        }

        echo json_encode([
            'success' => true,
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
            'pending' => $pending,
            'percentage' => $percentage,
            'status' => $status,
        ]);
        exit;

    } elseif ($action === 'get_pending_details') {
        // Obtener detalles pendientes para envío
        $details = $broadcastHistoryModel->getBroadcastDetails($broadcastId);
        $pendingDetails = array_filter($details, function ($detail) {
            return $detail['status'] === 'pending';
        });

        echo json_encode([
            'success' => true,
            'details' => array_values($pendingDetails),
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    exit;
}

// Definir título de la página
$pageTitle = 'Procesando Difusión | Mundo Animal';

// Incluir el header
include 'includes/header.php';
?>

<div class="container">
    <div class="config-header">
        <h1><i class="bi bi-send"></i> Procesando Difusión</h1>
        <p class="text-muted">Enviando mensajes a los contactos de la lista.</p>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-megaphone"></i> 
                Difusión #<?php echo $broadcastId; ?>
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Información de la Difusión</h6>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Lista:</strong></td>
                            <td><?php echo htmlspecialchars($broadcast['list_name'] ?: 'Sin lista'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Total Contactos:</strong></td>
                            <td><?php echo $broadcast['total_contacts']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Estado:</strong></td>
                            <td>
                                <span class="badge bg-secondary" id="status-badge">Pendiente</span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Mensaje:</strong></td>
                            <td><?php echo htmlspecialchars($broadcast['message'] ?: 'Sin mensaje'); ?></td>
                        </tr>
                        <?php if ($broadcast['image_path']) { ?>
                        <tr>
                            <td><strong>Imagen:</strong></td>
                            <td>
                                <img src="<?php echo htmlspecialchars($broadcast['image_path']); ?>" 
                                     alt="Imagen de difusión" style="max-width: 100px; max-height: 100px;">
                            </td>
                        </tr>
                        <?php } ?>
                    </table>
                </div>
                
                <div class="col-md-6">
                    <h6>Progreso de Envío</h6>
                    <div class="progress mb-3" style="height: 30px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             id="progress-bar" role="progressbar" style="width: 0%">
                            0%
                        </div>
                    </div>
                    
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="card bg-success text-white">
                                <div class="card-body py-2">
                                    <h6 class="mb-0" id="sent-count">0</h6>
                                    <small>Enviados</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card bg-danger text-white">
                                <div class="card-body py-2">
                                    <h6 class="mb-0" id="failed-count">0</h6>
                                    <small>Fallidos</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card bg-warning text-white">
                                <div class="card-body py-2">
                                    <h6 class="mb-0" id="pending-count">0</h6>
                                    <small>Pendientes</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button type="button" class="btn btn-success" id="start-btn" onclick="startBroadcast()">
                            <i class="bi bi-play-circle"></i> Iniciar Envío
                        </button>
                        <button type="button" class="btn btn-danger" id="stop-btn" onclick="stopBroadcast()" style="display: none;">
                            <i class="bi bi-stop-circle"></i> Detener Envío
                        </button>
                        <a href="send_broadcast.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
            
            <hr>
            
            <h6>Log de Envío</h6>
            <div id="log-container" class="border p-3" style="height: 300px; overflow-y: auto; background-color: #f8f9fa;">
                <div class="text-muted">Esperando inicio del envío...</div>
            </div>
        </div>
    </div>
</div>

<script>
let isRunning = false;
let currentIndex = 0;
let pendingDetails = [];
let broadcastId = <?php echo $broadcastId; ?>;

// Función para iniciar la difusión
function startBroadcast() {
    fetch('process_broadcast.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=start_broadcast'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('start-btn').style.display = 'none';
            document.getElementById('stop-btn').style.display = 'inline-block';
            document.getElementById('status-badge').textContent = 'En Progreso';
            document.getElementById('status-badge').className = 'badge bg-warning';
            
            isRunning = true;
            loadPendingDetails();
        }
    });
}

// Función para detener la difusión
function stopBroadcast() {
    isRunning = false;
    document.getElementById('start-btn').style.display = 'inline-block';
    document.getElementById('stop-btn').style.display = 'none';
    document.getElementById('status-badge').textContent = 'Detenida';
    document.getElementById('status-badge').className = 'badge bg-secondary';
    
    addLog('Envío detenido por el usuario', 'warning');
}

// Función para cargar detalles pendientes
function loadPendingDetails() {
    fetch('process_broadcast.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_progress'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateProgress(data);
            
            if (data.pending > 0 && isRunning) {
                // Obtener detalles pendientes y comenzar envío
                getPendingDetails();
            } else if (data.pending === 0) {
                isRunning = false;
                document.getElementById('status-badge').textContent = 'Completada';
                document.getElementById('status-badge').className = 'badge bg-success';
                addLog('Difusión completada', 'success');
            }
        }
    });
}

// Función para obtener detalles pendientes
function getPendingDetails() {
    fetch('process_broadcast.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_pending_details'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            pendingDetails = data.details;
            currentIndex = 0;
            
            if (pendingDetails.length > 0 && isRunning) {
                addLog(`Iniciando envío a ${pendingDetails.length} contactos`, 'info');
                sendNextMessage();
            } else {
                addLog('No hay contactos pendientes para enviar', 'warning');
                loadPendingDetails();
            }
        } else {
            addLog('Error al obtener detalles pendientes', 'danger');
        }
    })
    .catch(error => {
        addLog(`Error de red: ${error.message}`, 'danger');
    });
}

// Función para enviar el siguiente mensaje
function sendNextMessage() {
    if (!isRunning || currentIndex >= pendingDetails.length) {
        loadPendingDetails();
        return;
    }
    
    const detail = pendingDetails[currentIndex];
    
    addLog(`Enviando a: ${detail.contact_number}`, 'info');
    
    const formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('detail_id', detail.id);
    formData.append('contact_number', detail.contact_number);
    formData.append('message', '<?php echo addslashes($broadcast['message']); ?>');
    <?php if ($broadcast['image_path']) { ?>
    formData.append('image_path', '<?php echo addslashes($broadcast['image_path']); ?>');
    <?php } ?>
    
    fetch('process_broadcast.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            addLog(`✅ Enviado a: ${detail.contact_number}`, 'success');
        } else {
            addLog(`❌ Error enviando a: ${detail.contact_number} - ${data.message}`, 'danger');
        }
        
        currentIndex++;
        
        // Pequeño delay antes del siguiente envío
        setTimeout(() => {
            if (isRunning) {
                sendNextMessage();
            }
        }, 500);
    })
    .catch(error => {
        addLog(`❌ Error de red: ${error.message}`, 'danger');
        currentIndex++;
        
        setTimeout(() => {
            if (isRunning) {
                sendNextMessage();
            }
        }, 1000);
    });
}

// Función para actualizar progreso
function updateProgress(data) {
    document.getElementById('progress-bar').style.width = data.percentage + '%';
    document.getElementById('progress-bar').textContent = data.percentage + '%';
    document.getElementById('sent-count').textContent = data.sent;
    document.getElementById('failed-count').textContent = data.failed;
    document.getElementById('pending-count').textContent = data.pending;
}

// Función para agregar log
function addLog(message, type = 'info') {
    const logContainer = document.getElementById('log-container');
    const timestamp = new Date().toLocaleTimeString();
    
    const logEntry = document.createElement('div');
    logEntry.className = `mb-1 text-${type}`;
    logEntry.innerHTML = `<small>[${timestamp}]</small> ${message}`;
    
    logContainer.appendChild(logEntry);
    logContainer.scrollTop = logContainer.scrollHeight;
}

// Cargar progreso inicial
loadPendingDetails();

// Actualizar progreso cada 5 segundos
setInterval(() => {
    if (isRunning) {
        loadPendingDetails();
    }
}, 5000);
</script>

<?php include 'includes/footer.php'; ?> 