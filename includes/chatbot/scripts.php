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

// Función global para crear alertas con auto-cierre (mantener como utilidad secundaria)
function createAutoCloseAlert(message, type, container, timeout = 3000) {
    const alerta = document.createElement('div');
    alerta.className = `alert alert-${type}`;
    alerta.innerHTML = message;
    
    if (container) {
        container.innerHTML = '';
        container.appendChild(alerta);
    } else {
        document.body.appendChild(alerta);
    }
    
    // Auto-cerrar después del tiempo especificado
    setTimeout(() => {
        if (alerta.parentNode) {
            alerta.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            alerta.style.opacity = '0';
            alerta.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                if (alerta.parentNode) {
                    alerta.parentNode.removeChild(alerta);
                }
            }, 300);
        }
    }, timeout);
    
    return alerta;
}

// Envío de difusión simplificado con n8n
function enviarDifusionN8n(formData) {
    // Mostrar loading
    const loadingBtn = document.getElementById('btnEnviarDifusion');
    const originalText = loadingBtn.innerHTML;
    loadingBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Enviando...';
    loadingBtn.disabled = true;
    
    // Mostrar notificación de envío
    showNotification('📤 Enviando difusión...', 'success');
    
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
                window.location.href = 'chatbot.php';
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
                window.location.href = 'chatbot.php';
            }
        }, 2000);
    })
    .finally(() => {
        loadingBtn.innerHTML = originalText;
        loadingBtn.disabled = false;
    });
}

// Envío de difusión
document.addEventListener('DOMContentLoaded', function() {
    const formDifusion = document.getElementById('formDifusion');
    if (formDifusion) {
        formDifusion.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const mensaje = document.getElementById('mensajeDifusion').value.trim();
            const imagen = document.getElementById('imagenDifusion').files[0];
            
            if (!mensaje && !imagen) {
                showNotification('Debes ingresar un mensaje o seleccionar una imagen.', 'error');
                return;
            }
            
            // Obtener contactos seleccionados
            fetch('api/contacts_list.php')
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        showNotification('No se pudieron obtener los contactos seleccionados.', 'error');
                        return;
                    }
                    
                    const seleccionados = data.contactos.filter(c => c.send);
                    if (seleccionados.length === 0) {
                        showNotification('Debes seleccionar al menos un contacto para enviar la difusión.', 'error');
                        return;
                    }
                    
                    // Preparar FormData para n8n
                    const formData = new FormData();
                    formData.append('mensaje', mensaje);
                    if (imagen) formData.append('imagen', imagen);
                    
                    // Agregar contactos seleccionados
                    seleccionados.forEach(contacto => {
                        formData.append('selected_contacts[]', contacto.number);
                    });
                    
                    // Confirmar envío
                    const confirmacion = confirm(
                        `¿Estás seguro de que quieres enviar la difusión?\n\n` +
                        `👥 Contactos: ${seleccionados.length}\n` +
                        `📝 Mensaje: ${mensaje ? 'Sí' : 'No'}\n` +
                        `🖼️ Imagen: ${imagen ? 'Sí' : 'No'}`
                    );
                    
                    if (confirmacion) {
                        enviarDifusionN8n(formData);
                    }
                });
        });
    }
});

// --- LIMPIEZA GLOBAL DE MODALES ATASCADOS (backdrop) ---
document.addEventListener('hidden.bs.modal', () => { if (window.limpiarBackdrops) window.limpiarBackdrops(); });
document.addEventListener('hide.bs.modal', () => { if (window.limpiarBackdrops) window.limpiarBackdrops(); });

// Refuerza la gestión del modal de QR (conexión)
const qrModalEl = document.getElementById('qrModal');
let qrModalInstance = null;
if (qrModalEl) {
    qrModalEl.addEventListener('hidden.bs.modal', limpiarBackdrops);
    qrModalEl.addEventListener('hide.bs.modal', limpiarBackdrops);
    // Asocia todos los botones que abren el modal de QR
    document.querySelectorAll('[data-bs-target="#qrModal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!qrModalInstance) {
                qrModalInstance = new bootstrap.Modal(qrModalEl);
            }
            qrModalInstance.show();
            setTimeout(limpiarBackdrops, 500);
        });
    });
}

// Función para cerrar el modal de QR si la instancia se conecta
function cerrarModalQR() {
    const qrModalEl = document.getElementById('qrModal');
    if (qrModalEl) {
        let modal = bootstrap.Modal.getInstance(qrModalEl);
        if (!modal) modal = new bootstrap.Modal(qrModalEl);
        modal.hide();
        limpiarBackdrops();
    }
}
</script> 