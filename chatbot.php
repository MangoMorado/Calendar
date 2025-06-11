<?php
// Incluir header y verificar autenticación
require_once 'includes/auth.php';
require_once 'config/database.php';
requireAuth();

// Obtener configuración de n8n desde la base de datos
$n8nConfig = [];
$sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('n8n_url', 'n8n_api_key', 'selected_workflow')";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $n8nConfig[$row['setting_key']] = $row['setting_value'];
}

$n8nUrl = $n8nConfig['n8n_url'] ?? '';
$n8nApiKey = $n8nConfig['n8n_api_key'] ?? '';
$selectedWorkflow = $n8nConfig['selected_workflow'] ?? '';

// Extraer el ID del workflow seleccionado
$workflowId = '';
if (!empty($selectedWorkflow) && strpos($selectedWorkflow, '|') !== false) {
    $workflowId = explode('|', $selectedWorkflow)[0];
}

// Verificar estado del workflow
$workflowStatus = 'error'; // Por defecto: problema con API
$workflowName = 'No configurado';

if (!empty($n8nUrl) && !empty($n8nApiKey) && !empty($workflowId)) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, rtrim($n8nUrl, '/') . '/api/v1/workflows/' . $workflowId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: application/json',
        'X-N8N-API-KEY: ' . $n8nApiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $workflowData = json_decode($response, true);
        if (is_array($workflowData)) {
            $workflowName = $workflowData['name'] ?? 'Nombre no disponible';
            $workflowStatus = $workflowData['active'] ? 'active' : 'inactive';
        }
    }
}

$pageTitle = 'Chatbot | Mundo Animal';
include 'includes/header.php';
?>

<main class="container">
    <div class="users-header">
        <h1><i class="bi bi-robot"></i> Chatbot</h1>
    </div>
    <div class="profile-panel" style="display: flex; gap: 2.5rem; align-items: flex-start;">
        <!-- Tabs laterales -->
        <nav class="profile-tabs-vertical nav flex-column nav-pills me-3" id="chatbotTabs" role="tablist" aria-orientation="vertical">
            <button class="nav-link active" id="tab-dashboard-btn" data-bs-toggle="pill" data-bs-target="#tab-dashboard" type="button" role="tab">
                <i class="bi bi-bar-chart"></i> Dashboard
            </button>
            <button class="nav-link" id="tab-difusiones-btn" data-bs-toggle="pill" data-bs-target="#tab-difusiones" type="button" role="tab">
                <i class="bi bi-megaphone"></i> Difusiones
            </button>
            <button class="nav-link" id="tab-config-btn" data-bs-toggle="pill" data-bs-target="#tab-config" type="button" role="tab">
                <i class="bi bi-gear"></i> Configuración
            </button>
        </nav>
        <!-- Contenido de tabs -->
        <div class="profile-content-panel tab-content" id="chatbotTabsContent" style="flex: 1 1 400px; min-width: 320px; max-width: 800px;">
            <div class="tab-pane fade show active" id="tab-dashboard" role="tabpanel">
                <h4><i class="bi bi-bar-chart"></i> Dashboard</h4>
                
                <!-- Indicador de estado del workflow -->
                <div class="workflow-status-card">
                    <div class="status-header">
                        <h5><i class="bi bi-diagram-3"></i> Estado del Workflow</h5>
                    </div>
                    <div class="status-content">
                        <div class="status-indicator">
                            <div class="led-indicator <?php echo $workflowStatus; ?>"></div>
                            <div class="status-text">
                                <strong>Workflow:</strong> <?php echo htmlspecialchars($workflowName); ?>
                                <br>
                                <small class="text-muted">
                                    <?php if ($workflowStatus === 'active'): ?>
                                        <i class="bi bi-check-circle"></i> Activo y funcionando
                                    <?php elseif ($workflowStatus === 'inactive'): ?>
                                        <i class="bi bi-pause-circle"></i> Inactivo
                                    <?php else: ?>
                                        <i class="bi bi-exclamation-triangle"></i> Problema con la API
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                        <?php if ($workflowStatus !== 'error'): ?>
                        <div class="toggle-container">
                            <button type="button" class="workflow-toggle <?php echo $workflowStatus === 'active' ? 'active' : ''; ?>" 
                                    data-workflow-id="<?php echo htmlspecialchars($workflowId); ?>" 
                                    data-current-status="<?php echo $workflowStatus; ?>">
                                <div class="toggle-slider"></div>
                                <span class="toggle-label">
                                    <?php echo $workflowStatus === 'active' ? 'ON' : 'OFF'; ?>
                                </span>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="tab-difusiones" role="tabpanel">
                <h4><i class="bi bi-megaphone"></i> Difusiones</h4>
                <div class="text-muted">Aquí podrás gestionar las difusiones del chatbot.</div>
            </div>
            <div class="tab-pane fade" id="tab-config" role="tabpanel">
                <h4><i class="bi bi-gear"></i> Configuración</h4>
                <div class="text-muted">Aquí podrás ajustar la configuración del chatbot.</div>
            </div>
        </div>
    </div>
</main>

<style>
.workflow-status-card {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.status-header h5 {
    margin: 0 0 15px 0;
    color: #495057;
    display: flex;
    align-items: center;
    gap: 8px;
}

.status-content {
    display: flex;
    align-items: center;
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 15px;
}

.led-indicator {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 10px rgba(0,0,0,0.3);
    animation: pulse 2s infinite;
}

.led-indicator.active {
    background-color: #28a745;
    box-shadow: 0 0 15px rgba(40, 167, 69, 0.5);
}

.led-indicator.inactive {
    background-color: #dc3545;
    box-shadow: 0 0 15px rgba(220, 53, 69, 0.5);
}

.led-indicator.error {
    background-color: #6c757d;
    box-shadow: 0 0 15px rgba(108, 117, 125, 0.5);
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.status-text {
    flex: 1;
}

.status-text strong {
    color: #495057;
}

.toggle-container {
    margin-left: 20px;
}

.workflow-toggle {
    position: relative;
    width: 60px;
    height: 30px;
    background-color: #ccc;
    border: none;
    border-radius: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 5px;
    font-size: 12px;
    font-weight: bold;
    color: #fff;
}

.workflow-toggle.active {
    background-color: #28a745;
}

.workflow-toggle:not(.active) {
    background-color: #dc3545;
}

.workflow-toggle:hover {
    transform: scale(1.05);
}

.workflow-toggle:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.toggle-slider {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 26px;
    height: 26px;
    background-color: white;
    border-radius: 50%;
    transition: transform 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.workflow-toggle.active .toggle-slider {
    transform: translateX(30px);
}

.toggle-label {
    z-index: 1;
    transition: all 0.3s ease;
}

.workflow-toggle.active .toggle-label {
    margin-left: 0;
    margin-right: auto;
}

.workflow-toggle:not(.active) .toggle-label {
    margin-left: auto;
    margin-right: 0;
}
</style>

<!-- Script para tabs de chatbot -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('#chatbotTabs .nav-link');
    const tabPanes = document.querySelectorAll('#chatbotTabsContent .tab-pane');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            tabButtons.forEach(b => b.classList.remove('active'));
            tabPanes.forEach(pane => pane.style.display = 'none');
            this.classList.add('active');
            const target = this.getAttribute('data-bs-target');
            const pane = document.querySelector(target);
            if (pane) {
                pane.style.display = 'block';
            }
        });
    });
    
    // Inicializar: mostrar solo el tab activo
    tabPanes.forEach(pane => pane.style.display = 'none');
    const activeBtn = document.querySelector('#chatbotTabs .nav-link.active');
    if (activeBtn) {
        const target = activeBtn.getAttribute('data-bs-target');
        const pane = document.querySelector(target);
        if (pane) {
            pane.style.display = 'block';
        }
    }

    // Manejar toggle del workflow
    const workflowToggle = document.querySelector('.workflow-toggle');
    if (workflowToggle) {
        workflowToggle.addEventListener('click', function() {
            if (this.disabled) return;
            
            const workflowId = this.getAttribute('data-workflow-id');
            const currentStatus = this.getAttribute('data-current-status');
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            
            // Deshabilitar el botón durante la petición
            this.disabled = true;
            
            // Crear FormData para enviar los datos
            const formData = new FormData();
            formData.append('action', 'toggle_workflow');
            formData.append('workflow_id', workflowId);
            formData.append('new_status', newStatus);
            
            // Hacer petición AJAX
            fetch('chatbot_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar el estado visual
                    this.classList.toggle('active', newStatus === 'active');
                    this.setAttribute('data-current-status', newStatus);
                    
                    // Actualizar el texto del toggle
                    const toggleLabel = this.querySelector('.toggle-label');
                    toggleLabel.textContent = newStatus === 'active' ? 'ON' : 'OFF';
                    
                    // Actualizar el LED y texto de estado
                    const ledIndicator = document.querySelector('.led-indicator');
                    const statusText = document.querySelector('.status-text small');
                    
                    ledIndicator.className = 'led-indicator ' + newStatus;
                    
                    if (newStatus === 'active') {
                        statusText.innerHTML = '<i class="bi bi-check-circle"></i> Activo y funcionando';
                    } else {
                        statusText.innerHTML = '<i class="bi bi-pause-circle"></i> Inactivo';
                    }
                    
                    // Mostrar mensaje de éxito
                    showNotification('Workflow ' + (newStatus === 'active' ? 'activado' : 'desactivado') + ' correctamente', 'success');
                } else {
                    // Revertir cambios si hay error
                    showNotification('Error: ' + (data.message || 'No se pudo cambiar el estado del workflow'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error de conexión', 'error');
            })
            .finally(() => {
                // Rehabilitar el botón
                this.disabled = false;
            });
        });
    }
});

// Función para mostrar notificaciones
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} notification-toast`;
    notification.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
        ${message}
    `;
    
    // Estilos para la notificación
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideIn 0.3s ease;
        max-width: 300px;
    `;
    
    document.body.appendChild(notification);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Agregar estilos para las animaciones
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
`;
document.head.appendChild(style);
</script>

<?php include 'includes/footer.php'; ?> 