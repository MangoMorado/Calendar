<?php
// Incluir header y verificar autenticación
require_once 'includes/auth.php';
require_once 'config/database.php';
requireAuth();

// Obtener configuración de n8n desde la base de datos
$n8nConfig = [];
$sql = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('n8n_url', 'n8n_api_key', 'selected_workflow', 'evolution_api_url', 'evolution_api_key', 'selected_evolution_instance', 'evolution_instance_name')";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $n8nConfig[$row['setting_key']] = $row['setting_value'];
}

$n8nUrl = $n8nConfig['n8n_url'] ?? '';
$n8nApiKey = $n8nConfig['n8n_api_key'] ?? '';
$selectedWorkflow = $n8nConfig['selected_workflow'] ?? '';
$evolutionApiUrl = $n8nConfig['evolution_api_url'] ?? '';
$evolutionApiKey = $n8nConfig['evolution_api_key'] ?? '';
$selectedEvolutionInstance = $n8nConfig['selected_evolution_instance'] ?? '';
$evolutionInstanceName = $n8nConfig['evolution_instance_name'] ?? '';

// Extraer el ID del workflow seleccionado
$workflowId = '';
if (!empty($selectedWorkflow) && strpos($selectedWorkflow, '|') !== false) {
    $workflowId = explode('|', $selectedWorkflow)[0];
}

// Extraer el token de la instancia seleccionada
$evolutionInstanceToken = '';
if (!empty($selectedEvolutionInstance) && strpos($selectedEvolutionInstance, '|') !== false) {
    $parts = explode('|', $selectedEvolutionInstance);
    if (count($parts) >= 2) {
        $evolutionInstanceToken = $parts[1]; // El token está en la segunda parte
    }
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

// Verificar estado de la instancia de Evolution API
$evolutionStatus = 'error'; // Por defecto: problema con API
$evolutionConnectionStatus = 'No configurado';

if (!empty($evolutionApiUrl) && !empty($evolutionApiKey) && !empty($evolutionInstanceToken)) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, rtrim($evolutionApiUrl, '/') . '/instance/fetchInstances');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: application/json',
        'apikey: ' . $evolutionApiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $instancesData = json_decode($response, true);
        if (is_array($instancesData)) {
            foreach ($instancesData as $instance) {
                if (is_array($instance) && isset($instance['token']) && $instance['token'] === $evolutionInstanceToken) {
                    $evolutionConnectionStatus = $instance['connectionStatus'] ?? 'unknown';
                    $evolutionStatus = ($evolutionConnectionStatus === 'open') ? 'active' : 'inactive';
                    break;
                }
            }
        }
    }
}

$pageTitle = 'Chatbot | Mundo Animal';
include 'includes/header.php';
?>

<div class="container">
    <div class="config-header">
        <h1><i class="bi bi-robot"></i> Chatbot</h1>
        <p class="text-muted">Gestiona el chatbot y las integraciones del sistema.</p>
    </div>

    <div class="config-card">
        <!-- Tabs laterales -->
        <div class="chatbot-layout">
            <nav class="chatbot-tabs-vertical nav flex-column nav-pills me-3" id="chatbotTabs" role="tablist" aria-orientation="vertical">
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
            <div class="chatbot-content-panel tab-content" id="chatbotTabsContent">
                <div class="tab-pane fade show active" id="tab-dashboard" role="tabpanel">
                    <div class="form-section">
                        <h2><i class="bi bi-bar-chart"></i> Dashboard</h2>
                        
                        <!-- Indicador de estado del workflow -->
                        <div class="status-card">
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

                        <!-- Indicador de estado de Evolution API -->
                        <div class="status-card">
                            <div class="status-header">
                                <h5><i class="bi bi-chat-dots"></i> Estado de Evolution API</h5>
                            </div>
                            <div class="status-content">
                                <div class="status-indicator">
                                    <div class="led-indicator <?php echo $evolutionStatus; ?>"></div>
                                    <div class="status-text">
                                        <strong>Instancia:</strong> <?php echo htmlspecialchars($evolutionInstanceName); ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php if ($evolutionStatus === 'active'): ?>
                                                <i class="bi bi-check-circle"></i> Conectado y funcionando
                                            <?php elseif ($evolutionStatus === 'inactive'): ?>
                                                <i class="bi bi-pause-circle"></i> Desconectado
                                            <?php else: ?>
                                                <i class="bi bi-exclamation-triangle"></i> Problema con la API
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                                <?php if ($evolutionStatus !== 'error'): ?>
                                <div class="toggle-container">
                                    <button type="button" class="workflow-toggle <?php echo $evolutionStatus === 'active' ? 'active' : ''; ?>" 
                                            data-instance-token="<?php echo htmlspecialchars($evolutionInstanceToken); ?>" 
                                            data-current-status="<?php echo $evolutionStatus; ?>"
                                            data-instance-name="<?php echo htmlspecialchars($evolutionInstanceName); ?>">
                                        <div class="toggle-slider"></div>
                                        <span class="toggle-label">
                                            <?php echo $evolutionStatus === 'active' ? 'ON' : 'OFF'; ?>
                                        </span>
                                    </button>
                                    <?php if ($evolutionStatus === 'inactive'): ?>
                                    <button type="button" class="btn btn-primary btn-sm connect-btn" 
                                            data-instance-token="<?php echo htmlspecialchars($evolutionInstanceToken); ?>"
                                            data-instance-name="<?php echo htmlspecialchars($evolutionInstanceName); ?>">
                                        <i class="bi bi-qr-code"></i> Conectar
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="tab-difusiones" role="tabpanel">
                    <div class="form-section">
                        <h2><i class="bi bi-megaphone"></i> Difusiones</h2>
                        <div class="text-muted">Aquí podrás gestionar las difusiones del chatbot.</div>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="tab-config" role="tabpanel">
                    <div class="form-section">
                        <h2><i class="bi bi-gear"></i> Configuración</h2>
                        <div class="text-muted">Aquí podrás ajustar la configuración del chatbot.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para mostrar QR -->
<div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="qrModalLabel">
                    <i class="bi bi-qr-code"></i> Conectar Instancia
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div id="qrContent">
                    <p class="text-muted">Generando código QR...</p>
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos base del sistema (copiados de config.php) */
.config-header {
    text-align: center;
    margin-bottom: 30px;
    padding: 20px 0;
    border-bottom: 2px solid #e9ecef;
}

.config-header h1 {
    color: #495057;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}

.config-header h1 i {
    color: #007bff;
    font-size: 1.2em;
}

.config-card {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

/* Layout del chatbot */
.chatbot-layout {
    display: flex;
    gap: 2rem;
    align-items: flex-start;
}

.chatbot-tabs-vertical {
    flex: 0 0 200px;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    border: 1px solid #e9ecef;
}

.chatbot-tabs-vertical .nav-link {
    border: none;
    border-radius: 6px;
    margin-bottom: 8px;
    padding: 12px 15px;
    color: #495057;
    background: transparent;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 500;
}

.chatbot-tabs-vertical .nav-link:hover {
    background-color: #e9ecef;
    color: #007bff;
}

.chatbot-tabs-vertical .nav-link.active {
    background-color: #007bff;
    color: white;
    box-shadow: 0 2px 4px rgba(0,123,255,0.3);
}

.chatbot-content-panel {
    flex: 1;
    min-width: 0;
}

/* Estilos de secciones (copiados de config.php) */
.form-section {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.form-section h2 {
    color: #495057;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e9ecef;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-section h2 i {
    color: #007bff;
}

/* Estilos de tarjetas de estado */
.status-card {
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
    font-size: 1.1rem;
    font-weight: 600;
}

.status-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 15px;
    flex: 1;
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
    font-weight: 600;
}

.toggle-container {
    display: flex;
    align-items: center;
    gap: 10px;
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

.connect-btn {
    padding: 5px 12px;
    font-size: 12px;
    border-radius: 15px;
    background-color: #007bff;
    border-color: #007bff;
    color: white;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 5px;
}

.connect-btn:hover {
    background-color: #0056b3;
    border-color: #0056b3;
    transform: scale(1.05);
    color: white;
}

.connect-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* Estilos responsivos */
@media (max-width: 768px) {
    .chatbot-layout {
        flex-direction: column;
        gap: 1rem;
    }
    
    .chatbot-tabs-vertical {
        flex: none;
        width: 100%;
        display: flex;
        flex-direction: row;
        overflow-x: auto;
        padding: 10px;
    }
    
    .chatbot-tabs-vertical .nav-link {
        flex: 0 0 auto;
        margin-bottom: 0;
        margin-right: 8px;
        white-space: nowrap;
    }
    
    .status-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .toggle-container {
        width: 100%;
        justify-content: flex-end;
    }
}

/* Estilos del modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1050;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal.show {
    display: block;
}

.modal-dialog {
    position: relative;
    width: auto;
    margin: 1.75rem auto;
    max-width: 500px;
    pointer-events: none;
}

.modal-dialog-centered {
    display: flex;
    align-items: center;
    min-height: calc(100% - 3.5rem);
}

.modal-content {
    position: relative;
    display: flex;
    flex-direction: column;
    width: 100%;
    pointer-events: auto;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid rgba(0, 0, 0, 0.2);
    border-radius: 0.3rem;
    outline: 0;
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.5);
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
    border-top-left-radius: calc(0.3rem - 1px);
    border-top-right-radius: calc(0.3rem - 1px);
}

.modal-title {
    margin-bottom: 0;
    line-height: 1.5;
    font-size: 1.25rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}

.modal-body {
    position: relative;
    flex: 1 1 auto;
    padding: 1rem;
}

.modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding: 0.75rem;
    border-top: 1px solid #dee2e6;
    border-bottom-right-radius: calc(0.3rem - 1px);
    border-bottom-left-radius: calc(0.3rem - 1px);
}

.btn-close {
    box-sizing: content-box;
    width: 1em;
    height: 1em;
    padding: 0.25em 0.25em;
    color: #000;
    background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23000'%3e%3cpath d='M.293.293a1 1 0 011.414 0L8 6.586 14.293.293a1 1 0 111.414 1.414L9.414 8l6.293 6.293a1 1 0 01-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 01-1.414-1.414L6.586 8 .293 1.707a1 1 0 010-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat;
    border: 0;
    border-radius: 0.25rem;
    opacity: 0.5;
    cursor: pointer;
}

.btn-close:hover {
    color: #000;
    text-decoration: none;
    opacity: 0.75;
}

.qr-image {
    max-width: 100%;
    height: auto;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin: 15px 0;
}

.qr-instructions {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    margin: 15px 0;
    text-align: left;
}

.qr-instructions h6 {
    color: #495057;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.qr-instructions ol {
    margin-bottom: 0;
    padding-left: 20px;
}

.qr-instructions li {
    margin-bottom: 5px;
    color: #6c757d;
}

/* Estilos para botones */
.btn {
    display: inline-block;
    font-weight: 400;
    line-height: 1.5;
    color: #212529;
    text-align: center;
    text-decoration: none;
    vertical-align: middle;
    cursor: pointer;
    user-select: none;
    background-color: transparent;
    border: 1px solid transparent;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    border-radius: 0.25rem;
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.btn-primary {
    color: #fff;
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.btn-primary:hover {
    color: #fff;
    background-color: #0b5ed7;
    border-color: #0a58ca;
}

.btn-secondary {
    color: #fff;
    background-color: #6c757d;
    border-color: #6c757d;
}

.btn-secondary:hover {
    color: #fff;
    background-color: #5c636a;
    border-color: #565e64;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: 0.2rem;
}

/* Estilos para alertas */
.alert {
    position: relative;
    padding: 0.75rem 1.25rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: 0.25rem;
}

.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}

.alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
}

/* Estilos para spinner */
.spinner-border {
    display: inline-block;
    width: 2rem;
    height: 2rem;
    vertical-align: text-bottom;
    border: 0.25em solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    animation: spinner-border 0.75s linear infinite;
}

.spinner-border.text-primary {
    color: #0d6efd !important;
}

@keyframes spinner-border {
    to {
        transform: rotate(360deg);
    }
}

/* Estilos para notificaciones */
.notification-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    padding: 15px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    animation: slideIn 0.3s ease;
    max-width: 300px;
    display: flex;
    align-items: center;
    gap: 8px;
}

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes slideOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
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
    const workflowToggle = document.querySelector('.workflow-toggle[data-workflow-id]');
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
                    const ledIndicator = this.closest('.status-content').querySelector('.led-indicator');
                    const statusText = this.closest('.status-content').querySelector('.status-text small');
                    
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

    // Manejar toggle de Evolution API
    const evolutionToggle = document.querySelector('.workflow-toggle[data-instance-token]');
    if (evolutionToggle) {
        evolutionToggle.addEventListener('click', function() {
            if (this.disabled) return;
            
            const instanceToken = this.getAttribute('data-instance-token');
            const currentStatus = this.getAttribute('data-current-status');
            const instanceName = this.getAttribute('data-instance-name');
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            
            // Deshabilitar el botón durante la petición
            this.disabled = true;
            
            // Crear FormData para enviar los datos
            const formData = new FormData();
            formData.append('action', 'toggle_evolution_instance');
            formData.append('instance_token', instanceToken);
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
                    const ledIndicator = this.closest('.status-content').querySelector('.led-indicator');
                    const statusText = this.closest('.status-content').querySelector('.status-text small');
                    
                    ledIndicator.className = 'led-indicator ' + newStatus;
                    
                    if (newStatus === 'active') {
                        statusText.innerHTML = '<i class="bi bi-check-circle"></i> Conectado y funcionando';
                    } else {
                        statusText.innerHTML = '<i class="bi bi-pause-circle"></i> Desconectado';
                        // Recargar la página tras desconexión exitosa
                        setTimeout(function() { location.reload(); }, 800);
                    }
                    
                    // Mostrar mensaje de éxito
                    showNotification('Instancia ' + instanceName + ' ' + (newStatus === 'active' ? 'conectada' : 'desconectada') + ' correctamente', 'success');
                } else {
                    // Revertir cambios si hay error
                    showNotification('Error: ' + (data.message || 'No se pudo cambiar el estado de la instancia'), 'error');
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

    // Manejar botón de conexión de Evolution API
    const connectBtn = document.querySelector('.connect-btn');
    if (connectBtn) {
        connectBtn.addEventListener('click', function() {
            if (this.disabled) return;
            
            const instanceToken = this.getAttribute('data-instance-token');
            const instanceName = this.getAttribute('data-instance-name');
            
            // Deshabilitar el botón durante la petición
            this.disabled = true;
            
            // Mostrar modal usando JavaScript puro
            const modal = document.getElementById('qrModal');
            modal.classList.add('show');
            modal.style.display = 'block';
            document.body.classList.add('modal-open');
            
            // Agregar backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'modalBackdrop';
            document.body.appendChild(backdrop);
            
            // Crear FormData para enviar los datos
            const formData = new FormData();
            formData.append('action', 'connect_evolution_instance');
            formData.append('instance_token', instanceToken);
            
            // Hacer petición AJAX
            fetch('chatbot_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar QR en el modal
                    const qrContent = document.getElementById('qrContent');
                    qrContent.innerHTML = `
                        <h6>Instancia: ${instanceName}</h6>
                        <div class="qr-instructions">
                            <h6><i class="bi bi-info-circle"></i> Instrucciones:</h6>
                            <ol>
                                <li>Abre WhatsApp en tu teléfono</li>
                                <li>Ve a Configuración > Dispositivos vinculados</li>
                                <li>Selecciona "Vincular un dispositivo"</li>
                                <li>Escanea el código QR que aparece abajo</li>
                            </ol>
                        </div>
                        <img src="${data.qr_code}" alt="Código QR" class="qr-image">
                        ${data.pairing_code ? `<p class="text-muted"><small>Código de emparejamiento: <strong>${data.pairing_code}</strong></small></p>` : ''}
                    `;
                    
                    // Mostrar mensaje de éxito
                    showNotification('QR generado correctamente', 'success');
                } else {
                    // Mostrar error en el modal
                    const qrContent = document.getElementById('qrContent');
                    qrContent.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i>
                            Error: ${data.message}
                        </div>
                    `;
                    
                    // Mostrar mensaje de error
                    showNotification('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const qrContent = document.getElementById('qrContent');
                qrContent.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        Error de conexión
                    </div>
                `;
                showNotification('Error de conexión', 'error');
            })
            .finally(() => {
                // Rehabilitar el botón
                this.disabled = false;
            });
        });
    }

    // Función para cerrar el modal
    function closeModal() {
        const modal = document.getElementById('qrModal');
        const backdrop = document.getElementById('modalBackdrop');
        
        modal.classList.remove('show');
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        
        if (backdrop) {
            backdrop.remove();
        }
    }

    // Event listeners para cerrar el modal
    const modal = document.getElementById('qrModal');
    const closeButtons = modal.querySelectorAll('[data-bs-dismiss="modal"], .btn-close, .btn-secondary');
    
    closeButtons.forEach(button => {
        button.addEventListener('click', closeModal);
    });

    // Cerrar modal al hacer clic en el backdrop
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // Cerrar modal con la tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('show')) {
            closeModal();
        }
    });
});

// Función para mostrar notificaciones
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} notification-toast`;
    notification.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
        ${message}
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
</script>

<?php include 'includes/footer.php'; ?> 