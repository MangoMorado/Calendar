<?php
// Vista para enviar difusión
$lists = $data['lists'];
?>
<div class="container">
    <div class="card mb-4 mt-4">
        <div class="card-header bg-light">
            <h4 class="mb-0"><i class="bi bi-megaphone"></i> Enviar Difusión</h4>
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
                <div class="mb-3">
                    <label for="mensajeDifusion" class="form-label">Mensaje de difusión</label>
                    <textarea id="mensajeDifusion" name="mensaje" class="form-control" rows="4" placeholder="Escribe tu mensaje aquí..."></textarea>
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
                    <button type="button" class="btn btn-secondary ms-2 mb-0" id="btnVistaPrevia">
                        <i class="bi bi-eye"></i> Vista previa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
// Contador de caracteres
const mensajeDifusion = document.getElementById('mensajeDifusion');
const caracteresRestantes = document.getElementById('caracteresRestantes');
if (mensajeDifusion && caracteresRestantes) {
    mensajeDifusion.addEventListener('input', function() {
        const maxChars = 4096; // Límite de WhatsApp
        const currentChars = this.value.length;
        const remaining = maxChars - currentChars;
        if (remaining < 0) {
            caracteresRestantes.innerHTML = `<span class=\"text-danger\">${Math.abs(remaining)} caracteres de más</span>`;
        } else if (remaining < 100) {
            caracteresRestantes.innerHTML = `<span class=\"text-warning\">${remaining} caracteres restantes</span>`;
        } else {
            caracteresRestantes.textContent = `${remaining} caracteres restantes`;
        }
    });
}
// Vista previa (puedes mejorar esto según tu lógica)
document.getElementById('btnVistaPrevia').addEventListener('click', function() {
    const mensaje = mensajeDifusion.value.trim();
    const imagen = document.getElementById('imagenDifusion').files[0];
    let html = '<div class="whatsapp-message-preview">';
    if (imagen) {
        const reader = new FileReader();
        reader.onload = function(e) {
            html += `<div class=\"text-center mb-2\"><img src=\"${e.target.result}\" class=\"img-fluid rounded\" style=\"max-height: 200px;\"></div>`;
            if (mensaje) {
                html += `<div class=\"message-text\">${mensaje.replace(/\n/g, '<br>')}</div>`;
            }
            html += '</div>';
            showPreviewModal(html);
        };
        reader.readAsDataURL(imagen);
    } else {
        html += `<div class=\"message-text\">${mensaje.replace(/\n/g, '<br>')}</div></div>`;
        showPreviewModal(html);
    }
});
function showPreviewModal(html) {
    let modal = document.getElementById('modalVistaPrevia');
    if (!modal) {
        modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'modalVistaPrevia';
        modal.tabIndex = -1;
        modal.innerHTML = `<div class=\"modal-dialog modal-dialog-centered\"><div class=\"modal-content\"><div class=\"modal-header\"><h5 class=\"modal-title\"><i class=\"bi bi-eye\"></i> Vista previa del mensaje</h5><button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"modal\"></button></div><div class=\"modal-body\"><div class=\"card\"><div class=\"card-body\"><div id=\"vistaPreviaContenido\"></div></div></div></div><div class=\"modal-footer\"><button type=\"button\" class=\"btn btn-secondary\" data-bs-dismiss=\"modal\">Cerrar</button></div></div></div>`;
        document.body.appendChild(modal);
    }
    document.getElementById('vistaPreviaContenido').innerHTML = html;
    new bootstrap.Modal(modal).show();
}

// Envío de difusión AJAX
const formDifusion = document.getElementById('formDifusion');
formDifusion.addEventListener('submit', function(e) {
    e.preventDefault();
    const mensaje = document.getElementById('mensajeDifusion').value.trim();
    const imagen = document.getElementById('imagenDifusion').files[0];
    const listId = document.getElementById('listaDifusion').value;
    // Validaciones
    if (!listId) {
        showNotification('Debes seleccionar una lista de difusión.', 'error');
        return;
    }
    if (!mensaje && !imagen) {
        showNotification('Debes ingresar un mensaje o seleccionar una imagen.', 'error');
        return;
    }
    // Confirmar envío
    if (!confirm('¿Estás seguro de que quieres enviar la difusión?')) {
        return;
    }
    // Preparar datos
    const formData = new FormData();
    formData.append('list_id', listId);
    formData.append('message', mensaje);
    if (imagen) {
        formData.append('image', imagen);
    }
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
        // Validar respuestas de Evolution API
        let algunPendiente = false;
        if (data.data && Array.isArray(data.data.evolution_responses)) {
            algunPendiente = data.data.evolution_responses.some(
                resp => resp && resp.status && resp.status.toUpperCase() === 'PENDING'
            );
        }
        if (data.success) {
            document.getElementById('barraProgresoEnvio').style.width = '100%';
            document.getElementById('barraProgresoEnvio').textContent = '100%';
            document.getElementById('estadoEnvioDifusion').textContent = '¡Difusión completada!';
            document.getElementById('enviadosExitosos').textContent = data.data.sent_successfully ?? 0;
            document.getElementById('enviadosFallidos').textContent = data.data.sent_failed ?? 0;
            document.getElementById('detallesEnvio').style.display = 'block';
            document.getElementById('modalFooter').style.display = 'block';
            showNotification('Difusión completada. Enviados: ' + (data.data.sent_successfully ?? 0) + ', Fallidos: ' + (data.data.sent_failed ?? 0), (data.data.sent_failed ?? 0) === 0 ? 'success' : 'warning');
            setTimeout(() => {
                formDifusion.reset();
                document.getElementById('barraProgresoEnvio').style.width = '0%';
                document.getElementById('barraProgresoEnvio').textContent = '0%';
                modal.hide();
            }, 2000);
        } else {
            showNotification(data.message || 'Error en el envío', 'error');
            document.getElementById('estadoEnvioDifusion').textContent = data.message || 'Error en el envío';
        }
    })
    .catch(error => {
        showNotification('Error de conexión: ' + error.message, 'error');
        document.getElementById('estadoEnvioDifusion').textContent = 'Error de conexión';
    });
});
// Notificaciones visuales
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : (type === 'warning' ? 'warning' : 'danger')} notification-toast`;
    notification.style.cssText = `position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; animation: slideIn 0.3s ease;`;
    notification.innerHTML = `<i class="bi bi-${type === 'success' ? 'check-circle' : (type === 'warning' ? 'exclamation-triangle' : 'x-circle')}"></i> ${message}`;
    document.body.appendChild(notification);
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => { if (notification.parentNode) notification.parentNode.removeChild(notification); }, 300);
    }, 5000);
}
</script>
<style>
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
</style>

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
            </div>
        </div>
    </div>
</div> 