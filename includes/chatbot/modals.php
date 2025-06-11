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

<!-- Modal de carga para importación de contactos -->
<div class="modal fade" id="modalCargaContactos" tabindex="-1" aria-labelledby="modalCargaContactosLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCargaContactosLabel">
                    <i class="bi bi-person-lines-fill"></i> Importando contactos
                </h5>
            </div>
            <div class="modal-body">
                <div class="progress mb-3">
                    <div id="barraProgresoContactos" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%">0%</div>
                </div>
                <div id="estadoImportacionContactos" class="text-center text-muted">Iniciando importación...</div>
            </div>
        </div>
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
                    <div id="barraProgresoEnvio" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%">0%</div>
                </div>
                <div id="estadoEnvioDifusion" class="text-center text-muted">Preparando envío...</div>
            </div>
        </div>
    </div>
</div> 