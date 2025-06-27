<?php
// Incluir header y verificar autenticación
require_once 'includes/auth.php';
require_once 'config/database.php';
requireAuth();

// Incluir configuración del chatbot
require_once 'includes/chatbot/config.php';

$pageTitle = 'Chatbot | Mundo Animal';
$extraStyles = '<link rel="stylesheet" href="assets/css/chatbot.css">';
include 'includes/header.php';
?>

<div class="container">
    <div class="config-header">
        <h1><i class="bi bi-robot"></i> Chatbot</h1>
        <p class="text-muted">Gestiona el chatbot y las integraciones del sistema.</p>
    </div>

    <div class="config-card">
        <nav class="chatbot-tabs-vertical nav flex-column nav-pills me-3" id="chatbotTabs" role="tablist" aria-orientation="vertical">
            <button class="nav-link active" id="tab-dashboard-btn" data-bs-toggle="pill" data-bs-target="#tab-dashboard" type="button" role="tab">
                <i class="bi bi-bar-chart"></i> Dashboard
            </button>
            <button class="nav-link" id="tab-contactos-btn" data-bs-toggle="pill" data-bs-target="#tab-contactos" type="button" role="tab">
                <i class="bi bi-person-lines-fill"></i> Contactos
            </button>
            <button class="nav-link" id="tab-config-btn" data-bs-toggle="pill" data-bs-target="#tab-config" type="button" role="tab">
                <i class="bi bi-gear"></i> Configuración
            </button>
        </nav>
        <div class="chatbot-layout">
            <div class="chatbot-content-panel tab-content" id="chatbotTabsContent">
                <?php include 'includes/chatbot/dashboard.php'; ?>
                <?php include 'includes/chatbot/contactos.php'; ?>
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

<?php 
// Incluir modales
include 'includes/chatbot/modals.php';

// Incluir scripts principales
include 'includes/chatbot/scripts.php';

// Incluir scripts específicos de contactos
include 'includes/chatbot/contactos-scripts.php';

include 'includes/footer.php'; 
?> 