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