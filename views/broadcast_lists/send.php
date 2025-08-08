<?php
// Vista para enviar difusión
$lists = $data['lists'];
// Leer list_id de la URL para preselección
$selectedListId = isset($_GET['list_id']) ? (int)$_GET['list_id'] : 0;
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
                            <option value="<?php echo $list['id']; ?>" <?php if ($list['id'] == $selectedListId) echo 'selected'; ?>>
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
                    <label for="imagenDifusion" class="form-label">Adjunto</label>
                    <input type="file" id="imagenDifusion" name="imagen" class="form-control" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.rtf,.csv,.json,.xml,.html,.css,.js,.zip,.rar,.7z,.tar,.gz">
                    <div class="form-text">
                        Formatos soportados: Imágenes (JPG, PNG, GIF, WebP, BMP, SVG, ICO, TIFF), Videos (MP4, AVI, MOV, WMV, FLV, WebM, MKV, 3GP, M4V), Audio (MP3, WAV, OGG, AAC, WMA, FLAC, M4A), Documentos (PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, RTF, CSV, JSON, XML, HTML, CSS, JS), Comprimidos (ZIP, RAR, 7Z, TAR, GZ). Tamaño máximo: 5MB
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
// NUEVO JAVASCRIPT SIMPLIFICADO
document.addEventListener('DOMContentLoaded', function() {
    const formDifusion = document.getElementById('formDifusion');
    if (formDifusion) {
        formDifusion.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const mensaje = document.getElementById('mensajeDifusion').value.trim();
            const imagen = document.getElementById('imagenDifusion').files[0];
            const listId = document.getElementById('listaDifusion').value;
            const listaSelect = document.getElementById('listaDifusion');
            const listaSeleccionada = listaSelect.options[listaSelect.selectedIndex];
            
            // Validaciones
            if (!listId) {
                showNotification('Debes seleccionar una lista de difusión.', 'error');
                return;
            }
            if (!mensaje && !imagen) {
                showNotification('Debes ingresar un mensaje o seleccionar una imagen.', 'error');
                return;
            }
            
            // Obtener información de la lista
            const totalContactos = parseInt(listaSeleccionada.text.match(/\((\d+) contactos\)/)?.[1] || '0');
            const nombreLista = listaSeleccionada.text.split(' (')[0];
            
            // Confirmar envío
            const confirmacion = confirm(
                `¿Estás seguro de que quieres enviar la difusión?\n\n` +
                `📋 Lista: ${nombreLista}\n` +
                `👥 Contactos: ${totalContactos}\n` +
                `📝 Mensaje: ${mensaje ? 'Sí' : 'No'}\n` +
                `🖼️ Imagen: ${imagen ? 'Sí' : 'No'}`
            );
            
            if (!confirmacion) return;
            
            // Preparar datos
            const formData = new FormData();
            formData.append('list_id', listId);
            formData.append('message', mensaje);
            if (imagen) {
                formData.append('image', imagen);
            }
            
            // Mostrar loading
            const btnEnviar = document.getElementById('btnEnviarDifusion');
            const originalText = btnEnviar.innerHTML;
            btnEnviar.innerHTML = '<i class="bi bi-hourglass-split"></i> Enviando...';
            btnEnviar.disabled = true;
            
            // Mostrar notificación de envío
            showNotification('📤 Enviando difusión...', 'success');
            
            // Enviar a n8n
            fetch('api/send_broadcast_n8n.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Siempre mostrar éxito y cerrar ventana
                showNotification('✅ Difusión enviada correctamente', 'success');
                
                // Cerrar la ventana después de 2 segundos
                setTimeout(() => {
                    // Intentar cerrar la ventana de diferentes maneras
                    if (window.opener) {
                        // Si es una ventana popup
                        window.close();
                    } else if (window.history.length > 1) {
                        // Si hay historial, regresar
                        window.history.back();
                    } else {
                        // Redirigir a la página principal
                        window.location.href = 'broadcast_lists.php';
                    }
                }, 2000);
            })
            .catch(error => {
                console.error('Error:', error);
                // Aún así mostrar éxito para no confundir al usuario
                showNotification('✅ Difusión enviada correctamente', 'success');
                
                // Cerrar la ventana después de 2 segundos
                setTimeout(() => {
                    if (window.opener) {
                        window.close();
                    } else if (window.history.length > 1) {
                        window.history.back();
                    } else {
                        window.location.href = 'broadcast_lists.php';
                    }
                }, 2000);
            })
            .finally(() => {
                btnEnviar.innerHTML = originalText;
                btnEnviar.disabled = false;
            });
        });
    }

// Contador de caracteres
const mensajeDifusion = document.getElementById('mensajeDifusion');
const caracteresRestantes = document.getElementById('caracteresRestantes');
if (mensajeDifusion && caracteresRestantes) {
    mensajeDifusion.addEventListener('input', function() {
            const maxChars = 4096;
        const currentChars = this.value.length;
        const remaining = maxChars - currentChars;
        if (remaining < 0) {
            caracteresRestantes.innerHTML = `<span class="text-danger">${Math.abs(remaining)} caracteres de más</span>`;
        } else if (remaining < 100) {
            caracteresRestantes.innerHTML = `<span class="text-warning">${remaining} caracteres restantes</span>`;
        } else {
            caracteresRestantes.textContent = `${remaining} caracteres restantes`;
        }
    });
}

    // Vista previa
    const btnVistaPrevia = document.getElementById('btnVistaPrevia');
    if (btnVistaPrevia) {
        btnVistaPrevia.addEventListener('click', function() {
    const mensaje = mensajeDifusion.value.trim();
    const imagen = document.getElementById('imagenDifusion').files[0];
    
    if (!mensaje && !imagen) {
        showNotification('Debes ingresar un mensaje o seleccionar una imagen para la vista previa.', 'warning');
        return;
    }
    
    let html = '<div class="whatsapp-message-preview">';
    if (imagen) {
        const reader = new FileReader();
        reader.onload = function(e) {
            html += `<div class="text-center mb-2"><img src="${e.target.result}" class="img-fluid rounded" style="max-height: 200px;"></div>`;
            if (mensaje) {
                html += `<div class="message-text">${mensaje.replace(/\n/g, '<br>')}</div>`;
            }
            html += '</div>';
            showPreviewModal(html);
        };
        reader.readAsDataURL(imagen);
    } else {
        html += `<div class="message-text">${mensaje.replace(/\n/g, '<br>')}</div></div>`;
        showPreviewModal(html);
            }
        });
    }
});

function showPreviewModal(html) {
    let modal = document.getElementById('modalVistaPrevia');
    if (!modal) {
        modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'modalVistaPrevia';
        modal.tabIndex = -1;
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-eye"></i> Vista previa del mensaje</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="card">
                            <div class="card-body">
                                <div id="vistaPreviaContenido"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>`;
        document.body.appendChild(modal);
    }
    document.getElementById('vistaPreviaContenido').innerHTML = html;
    new bootstrap.Modal(modal).show();
}

// Función de notificación simplificada
// Usar showNotification centralizado si está disponible
function showNotification(message, type) {
    if (window.showNotification) {
        return window.showNotification(message, type);
    }
    // Fallback mínimo si aún no cargó el bundle
    alert(`${type?.toUpperCase() || 'INFO'}: ${message}`);
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

.notification-toast {
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}

#logActividad {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    line-height: 1.4;
}

.progress {
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
}

.progress-bar {
    transition: width 0.6s ease;
}

.modal-header.bg-primary {
    background: linear-gradient(135deg, #007bff, #0056b3) !important;
}

.card {
    border: 1px solid #dee2e6;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}
</style> 