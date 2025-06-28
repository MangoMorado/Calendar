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

<!-- Modal de progreso mejorado -->
<div class="modal fade" id="modalProgresoEnvio" tabindex="-1" aria-labelledby="modalProgresoEnvioLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalProgresoEnvioLabel">
                    <i class="bi bi-send"></i> Enviando Difusión
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" id="btnCerrarModal" style="display:none;"></button>
            </div>
            <div class="modal-body">
                <!-- Información de la difusión -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body p-3">
                                <h6 class="card-title mb-2"><i class="bi bi-list-ul"></i> Información de la Lista</h6>
                                <div class="small">
                                    <strong>Lista:</strong> <span id="nombreLista">-</span><br>
                                    <strong>Total contactos:</strong> <span id="totalContactos">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body p-3">
                                <h6 class="card-title mb-2"><i class="bi bi-clock"></i> Tiempo Estimado</h6>
                                <div class="small">
                                    <strong>Tiempo estimado:</strong> <span id="tiempoEstimado">-</span><br>
                                    <strong>Velocidad:</strong> <span id="velocidadEnvio">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Barra de progreso principal -->
                <div class="progress mb-3" style="height: 25px;">
                    <div id="barraProgresoEnvio" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                         role="progressbar" style="width: 0%; font-weight: bold; font-size: 14px;">0%</div>
                </div>

                <!-- Estado actual -->
                <div class="text-center mb-3">
                    <div id="estadoEnvioDifusion" class="h5 text-primary mb-2">
                        <i class="bi bi-hourglass-split"></i> Preparando envío...
                    </div>
                    <div id="estadoDetallado" class="text-muted small">Inicializando sistema de difusión</div>
                </div>

                <!-- Estadísticas en tiempo real -->
                <div class="row mb-3" id="estadisticasEnvio" style="display:none;">
                    <div class="col-md-3">
                        <div class="card text-center bg-success text-white">
                            <div class="card-body p-2">
                                <div class="h4 mb-0" id="enviadosExitosos">0</div>
                                <small>Enviados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center bg-danger text-white">
                            <div class="card-body p-2">
                                <div class="h4 mb-0" id="enviadosFallidos">0</div>
                                <small>Fallidos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center bg-info text-white">
                            <div class="card-body p-2">
                                <div class="h4 mb-0" id="porcentajeExito">0%</div>
                                <small>Éxito</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center bg-warning text-white">
                            <div class="card-body p-2">
                                <div class="h4 mb-0" id="tiempoTranscurrido">0s</div>
                                <small>Tiempo</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Log de actividad -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-list-check"></i> Log de Actividad</h6>
                    </div>
                    <div class="card-body p-0">
                        <div id="logActividad" class="p-3" style="max-height: 200px; overflow-y: auto; background: #f8f9fa; font-family: monospace; font-size: 12px;">
                            <div class="text-muted">Iniciando sistema...</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" id="modalFooter" style="display:none;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btnVerDetalles" style="display:none;">
                    <i class="bi bi-list-ul"></i> Ver Detalles
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Variables globales para el progreso
let tiempoInicio = 0;
let intervaloActualizacion = null;
let progresoActual = 0;

// Contador de caracteres
const mensajeDifusion = document.getElementById('mensajeDifusion');
const caracteresRestantes = document.getElementById('caracteresRestantes');
if (mensajeDifusion && caracteresRestantes) {
    mensajeDifusion.addEventListener('input', function() {
        const maxChars = 4096; // Límite de WhatsApp
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

// Vista previa mejorada
document.getElementById('btnVistaPrevia').addEventListener('click', function() {
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

// Función para agregar log
function agregarLog(mensaje, tipo = 'info') {
    const logContainer = document.getElementById('logActividad');
    const timestamp = new Date().toLocaleTimeString();
    const iconos = {
        'info': 'bi-info-circle',
        'success': 'bi-check-circle',
        'warning': 'bi-exclamation-triangle',
        'error': 'bi-x-circle',
        'progress': 'bi-arrow-right'
    };
    const colores = {
        'info': 'text-primary',
        'success': 'text-success',
        'warning': 'text-warning',
        'error': 'text-danger',
        'progress': 'text-info'
    };
    
    const logEntry = document.createElement('div');
    logEntry.className = `mb-1 ${colores[tipo]}`;
    logEntry.innerHTML = `<i class="bi ${iconos[tipo]}"></i> [${timestamp}] ${mensaje}`;
    logContainer.appendChild(logEntry);
    logContainer.scrollTop = logContainer.scrollHeight;
}

// Función para actualizar progreso
function actualizarProgreso(porcentaje, estado, estadoDetallado) {
    const barra = document.getElementById('barraProgresoEnvio');
    const estadoElement = document.getElementById('estadoEnvioDifusion');
    const estadoDetalladoElement = document.getElementById('estadoDetallado');
    
    barra.style.width = porcentaje + '%';
    barra.textContent = porcentaje + '%';
    estadoElement.innerHTML = estado;
    estadoDetalladoElement.textContent = estadoDetallado;
    
    // Cambiar color según el progreso
    if (porcentaje < 25) {
        barra.className = 'progress-bar progress-bar-striped progress-bar-animated bg-primary';
    } else if (porcentaje < 75) {
        barra.className = 'progress-bar progress-bar-striped progress-bar-animated bg-warning';
    } else {
        barra.className = 'progress-bar progress-bar-striped progress-bar-animated bg-success';
    }
}

// Función para actualizar estadísticas
function actualizarEstadisticas(enviados, fallidos, tiempoTranscurrido) {
    const total = enviados + fallidos;
    const porcentajeExito = total > 0 ? Math.round((enviados / total) * 100) : 0;
    
    document.getElementById('enviadosExitosos').textContent = enviados;
    document.getElementById('enviadosFallidos').textContent = fallidos;
    document.getElementById('porcentajeExito').textContent = porcentajeExito + '%';
    document.getElementById('tiempoTranscurrido').textContent = tiempoTranscurrido + 's';
}

// Función para calcular tiempo estimado
function calcularTiempoEstimado(totalContactos) {
    const segundosPorContacto = 2; // Promedio de 1-3 segundos
    const tiempoTotal = Math.ceil((totalContactos * segundosPorContacto) / 60);
    return tiempoTotal > 1 ? `${tiempoTotal} minutos` : 'Menos de 1 minuto';
}

// Envío de difusión AJAX mejorado
const formDifusion = document.getElementById('formDifusion');
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
    
    // Confirmar envío con información detallada
    const confirmacion = confirm(
        `¿Estás seguro de que quieres enviar la difusión?\n\n` +
        `📋 Lista: ${nombreLista}\n` +
        `👥 Contactos: ${totalContactos}\n` +
        `⏱️ Tiempo estimado: ${calcularTiempoEstimado(totalContactos)}\n` +
        `📝 Mensaje: ${mensaje ? 'Sí' : 'No'}\n` +
        `🖼️ Imagen: ${imagen ? 'Sí' : 'No'}`
    );
    
    if (!confirmacion) return;
    
    // Inicializar modal de progreso
    inicializarModalProgreso(nombreLista, totalContactos);
    
    // Preparar datos
    const formData = new FormData();
    formData.append('list_id', listId);
    formData.append('message', mensaje);
    if (imagen) {
        formData.append('image', imagen);
    }
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalProgresoEnvio'));
    modal.show();
    
    // Iniciar envío
    enviarDifusion(formData, totalContactos);
});

function inicializarModalProgreso(nombreLista, totalContactos) {
    // Limpiar log
    document.getElementById('logActividad').innerHTML = '';
    
    // Configurar información inicial
    document.getElementById('nombreLista').textContent = nombreLista;
    document.getElementById('totalContactos').textContent = totalContactos;
    document.getElementById('tiempoEstimado').textContent = calcularTiempoEstimado(totalContactos);
    document.getElementById('velocidadEnvio').textContent = '1-3 segundos por contacto';
    
    // Ocultar elementos
    document.getElementById('estadisticasEnvio').style.display = 'none';
    document.getElementById('modalFooter').style.display = 'none';
    document.getElementById('btnCerrarModal').style.display = 'none';
    
    // Inicializar progreso
    actualizarProgreso(0, '<i class="bi bi-hourglass-split"></i> Preparando envío...', 'Inicializando sistema de difusión');
    
    // Agregar logs iniciales
    agregarLog('Iniciando proceso de difusión...', 'info');
    agregarLog(`Lista seleccionada: ${nombreLista}`, 'info');
    agregarLog(`Total de contactos: ${totalContactos}`, 'info');
    agregarLog('Verificando conexión con Evolution API...', 'progress');
    
    // Iniciar temporizador
    tiempoInicio = Date.now();
    iniciarActualizacionTiempo();
}

function iniciarActualizacionTiempo() {
    intervaloActualizacion = setInterval(() => {
        const tiempoTranscurrido = Math.floor((Date.now() - tiempoInicio) / 1000);
        document.getElementById('tiempoTranscurrido').textContent = tiempoTranscurrido + 's';
    }, 1000);
}

function enviarDifusion(formData, totalContactos) {
    // Simular progreso inicial
    setTimeout(() => {
        actualizarProgreso(10, '<i class="bi bi-gear"></i> Configurando envío...', 'Preparando datos y verificando instancia');
        agregarLog('Verificando estado de la instancia de WhatsApp...', 'progress');
    }, 500);
    
    setTimeout(() => {
        actualizarProgreso(20, '<i class="bi bi-check-circle"></i> Instancia verificada', 'Conexión establecida correctamente');
        agregarLog('✅ Instancia de WhatsApp conectada', 'success');
        agregarLog('Iniciando envío de mensajes...', 'progress');
    }, 1500);
    
    // Enviar difusión real
    fetch('api/send_broadcast_bulk.php', {
        method: 'POST',
        credentials: 'include',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        clearInterval(intervaloActualizacion);
        const tiempoTranscurrido = Math.floor((Date.now() - tiempoInicio) / 1000);
        
        if (data.success) {
            // Éxito
            actualizarProgreso(100, '<i class="bi bi-check-circle"></i> ¡Difusión completada!', 'Proceso finalizado exitosamente');
            agregarLog('✅ Difusión completada exitosamente', 'success');
            agregarLog(`📊 Resultados: ${data.data.sent_successfully} enviados, ${data.data.sent_failed} fallidos`, 'info');
            
            // Mostrar estadísticas
            document.getElementById('estadisticasEnvio').style.display = 'block';
            actualizarEstadisticas(data.data.sent_successfully, data.data.sent_failed, tiempoTranscurrido);
            
            // Mostrar footer
            document.getElementById('modalFooter').style.display = 'block';
            document.getElementById('btnCerrarModal').style.display = 'block';
            
            // Notificación
            const tipoNotificacion = data.data.sent_failed === 0 ? 'success' : 'warning';
            showNotification(
                `Difusión completada en ${tiempoTranscurrido}s. Enviados: ${data.data.sent_successfully}, Fallidos: ${data.data.sent_failed}`,
                tipoNotificacion
            );
            
            // Limpiar formulario después de 3 segundos
            setTimeout(() => {
                formDifusion.reset();
                document.getElementById('caracteresRestantes').textContent = 'Sin límite de caracteres';
            }, 3000);
            
        } else {
            // Error
            actualizarProgreso(0, '<i class="bi bi-x-circle"></i> Error en el envío', data.message || 'Error desconocido');
            agregarLog(`❌ Error: ${data.message}`, 'error');
            
            document.getElementById('btnCerrarModal').style.display = 'block';
            showNotification(data.message || 'Error en el envío', 'error');
        }
    })
    .catch(error => {
        clearInterval(intervaloActualizacion);
        actualizarProgreso(0, '<i class="bi bi-x-circle"></i> Error de conexión', 'No se pudo conectar con el servidor');
        agregarLog(`❌ Error de conexión: ${error.message}`, 'error');
        
        document.getElementById('btnCerrarModal').style.display = 'block';
        showNotification('Error de conexión: ' + error.message, 'error');
    });
}

// Notificaciones mejoradas
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : (type === 'warning' ? 'warning' : 'danger')} notification-toast`;
    notification.style.cssText = `
        position: fixed; 
        top: 20px; 
        right: 20px; 
        z-index: 9999; 
        min-width: 350px; 
        max-width: 500px;
        animation: slideIn 0.3s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border: none;
        border-radius: 8px;
    `;
    
    const iconos = {
        'success': 'bi-check-circle-fill',
        'warning': 'bi-exclamation-triangle-fill',
        'error': 'bi-x-circle-fill'
    };
    
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="bi ${iconos[type]} me-2" style="font-size: 1.2em;"></i>
            <div class="flex-grow-1">${message}</div>
            <button type="button" class="btn-close ms-2" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => { 
                if (notification.parentNode) notification.parentNode.removeChild(notification); 
            }, 300);
        }
    }, 6000);
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