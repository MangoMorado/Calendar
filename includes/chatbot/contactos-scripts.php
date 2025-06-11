<!-- Scripts específicos para contactos -->
<script>
// Variables globales para paginación y búsqueda
let todosLosContactos = [];
let contactosFiltrados = [];
let paginaActual = 1;
const contactosPorPagina = 100;
let terminoBusqueda = '';

// Función para obtener emoji de bandera según indicativo
function obtenerBanderaPais(numero) {
    const indicativos = {
        // Colombia
        '57': '🇨🇴',
        // México
        '52': '🇲🇽',
        // España
        '34': '🇪🇸',
        // Argentina
        '54': '🇦🇷',
        // Chile
        '56': '🇨🇱',
        // Perú
        '51': '🇵🇪',
        // Ecuador
        '593': '🇪🇨',
        // Venezuela
        '58': '🇻🇪',
        // Estados Unidos/Canadá
        '1': '🇺🇸'
    };
    
    // Verificar indicativos de 1, 2 o 3 dígitos
    for (let i = 1; i <= 3; i++) {
        const indicativo = numero.substring(0, i);
        if (indicativos[indicativo]) {
            return indicativos[indicativo];
        }
    }
    
    return '🌍'; // Bandera genérica si no se encuentra
}

// Función para validar números de teléfono
function validarNumeroTelefono(numero) {
    // Remover espacios, guiones y otros caracteres
    const numeroLimpio = numero.replace(/[\s\-\(\)]/g, '');
    
    // Verificar que solo contenga dígitos
    if (!/^\d+$/.test(numeroLimpio)) {
        return false;
    }
    
    // Validar indicativos de países comunes y longitud
    const indicativos = {
        // Colombia
        '57': { minLength: 10, maxLength: 10 },
        // México
        '52': { minLength: 10, maxLength: 10 },
        // España
        '34': { minLength: 9, maxLength: 9 },
        // Argentina
        '54': { minLength: 10, maxLength: 11 },
        // Chile
        '56': { minLength: 9, maxLength: 9 },
        // Perú
        '51': { minLength: 9, maxLength: 9 },
        // Ecuador
        '593': { minLength: 9, maxLength: 9 },
        // Venezuela
        '58': { minLength: 10, maxLength: 10 },
        // Estados Unidos/Canadá
        '1': { minLength: 10, maxLength: 10 }
    };
    
    // Verificar indicativos de 1, 2 o 3 dígitos
    for (let i = 1; i <= 3; i++) {
        const indicativo = numeroLimpio.substring(0, i);
        if (indicativos[indicativo]) {
            const longitudRestante = numeroLimpio.length - i;
            const config = indicativos[indicativo];
            
            if (longitudRestante >= config.minLength && longitudRestante <= config.maxLength) {
                return true;
            }
        }
    }
    
    return false;
}

// Función para filtrar contactos por búsqueda
function filtrarContactosPorBusqueda(contactos, termino) {
    if (!termino || termino.trim() === '') {
        return contactos;
    }
    
    const terminoLower = termino.toLowerCase().trim();
    return contactos.filter(contacto => {
        const nombre = (contacto.pushName || '').toLowerCase();
        const numero = contacto.number.split('@')[0];
        return nombre.includes(terminoLower) || numero.includes(terminoLower);
    });
}

// Función para obtener contactos de la página actual
function obtenerContactosPagina(contactos, pagina, porPagina) {
    const inicio = (pagina - 1) * porPagina;
    const fin = inicio + porPagina;
    return contactos.slice(inicio, fin);
}

// Función para actualizar controles de paginación
function actualizarControlesPaginacion() {
    const totalPaginas = Math.ceil(contactosFiltrados.length / contactosPorPagina);
    const inicio = (paginaActual - 1) * contactosPorPagina + 1;
    const fin = Math.min(paginaActual * contactosPorPagina, contactosFiltrados.length);
    
    document.getElementById('paginaActual').textContent = paginaActual;
    document.getElementById('totalPaginas').textContent = totalPaginas;
    document.getElementById('mostrandoDesde').textContent = contactosFiltrados.length > 0 ? inicio : 0;
    document.getElementById('mostrandoHasta').textContent = fin;
    document.getElementById('totalResultados').textContent = contactosFiltrados.length;
    
    // Habilitar/deshabilitar botones
    document.getElementById('btnPaginaAnterior').disabled = paginaActual <= 1;
    document.getElementById('btnPaginaSiguiente').disabled = paginaActual >= totalPaginas;
    
    // Mostrar/ocultar paginación
    const paginacion = document.getElementById('paginacion');
    if (contactosFiltrados.length > contactosPorPagina) {
        paginacion.style.display = 'flex';
    } else {
        paginacion.style.display = 'none';
    }
}

// Funciones para manejo de contactos
function cargarContactos() {
    fetch('api/import_contacts.php', { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showNotification('Contactos importados: ' + data.imported + ', actualizados: ' + data.updated, 'success');
                obtenerContactos();
            } else {
                showNotification('Error: ' + (data.message || 'No se pudo importar contactos'), 'error');
            }
        })
        .catch(() => showNotification('Error de red al importar contactos', 'error'));
}

function obtenerContactos() {
    fetch('api/contacts_list.php')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                todosLosContactos = data.contactos;
                aplicarFiltrosYRenderizar();
            } else {
                createAutoCloseAlert('No se pudieron cargar los contactos', 'danger', document.getElementById('contactos-lista'));
            }
        });
}

function aplicarFiltrosYRenderizar() {
    // Filtrar contactos válidos
    const contactosValidos = todosLosContactos.filter(c => {
        const numero = c.number.split('@')[0];
        return validarNumeroTelefono(numero);
    });
    
    // Aplicar búsqueda
    contactosFiltrados = filtrarContactosPorBusqueda(contactosValidos, terminoBusqueda);
    
    // Resetear a primera página
    paginaActual = 1;
    
    // Renderizar
    renderizarContactosPagina();
    actualizarControlesPaginacion();
}

function renderizarContactosPagina() {
    if (contactosFiltrados.length === 0) {
        const mensaje = terminoBusqueda ? 
            'No se encontraron contactos que coincidan con la búsqueda.' :
            'No hay contactos con números válidos.';
        
        createAutoCloseAlert(mensaje, 'warning', document.getElementById('contactos-lista'));
        document.getElementById('contactos-toolbar').style.display = 'none';
        document.getElementById('paginacion').style.display = 'none';
        
        return;
    }
    
    // Obtener contactos de la página actual
    const contactosPagina = obtenerContactosPagina(contactosFiltrados, paginaActual, contactosPorPagina);
    
    let html = '<table><thead><tr><th></th><th>Nombre</th><th>Número</th><th>País</th></tr></thead><tbody>';
    contactosPagina.forEach(c => {
        // Extraer solo el número (antes del @)
        const numero = c.number.split('@')[0];
        const bandera = obtenerBanderaPais(numero);
        html += `<tr${c.send ? ' class="selected"' : ''}>
            <td><input type="checkbox" class="chk-contacto" data-number="${c.number}" ${c.send ? 'checked' : ''}></td>
            <td>${c.pushName ? c.pushName : '<span class="text-muted">Sin nombre</span>'}</td>
            <td>${numero}</td>
            <td style="font-size: 1.2em;">${bandera}</td>
        </tr>`;
    });
    html += '</tbody></table>';
    
    // Mostrar estadísticas con auto-cierre después de 3 segundos
    const totalContactos = todosLosContactos.length;
    const contactosInvalidos = totalContactos - todosLosContactos.filter(c => {
        const numero = c.number.split('@')[0];
        return validarNumeroTelefono(numero);
    }).length;
    
    let estadisticas = `<div class="alert alert-info mb-3" role="alert" id="alert-estadisticas">
        <i class="bi bi-info-circle"></i>
        <strong>Estadísticas:</strong> ${contactosFiltrados.length} contactos encontrados de ${totalContactos} total
    </div>`;
    
    if (contactosInvalidos > 0) {
        estadisticas += `<div class="alert alert-warning mb-3" role="alert" id="alert-invalidos">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Nota:</strong> ${contactosInvalidos} contactos fueron filtrados por tener números inválidos
        </div>`;
    }
    
    if (terminoBusqueda) {
        estadisticas += `<div class="alert alert-success mb-3" role="alert" id="alert-busqueda">
            <i class="bi bi-search"></i>
            <strong>Búsqueda:</strong> "${terminoBusqueda}" - ${contactosFiltrados.length} resultados
        </div>`;
    }
    
    document.getElementById('contactos-lista').innerHTML = estadisticas + html;
    document.getElementById('contactos-toolbar').style.display = '';
    
    // Auto-cerrar alertas después de 3 segundos
    setTimeout(() => {
        const alertas = document.querySelectorAll('#contactos-lista .alert');
        alertas.forEach(alerta => {
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
        });
    }, 3000);
}

function guardarSeleccionContactos(valor = null) {
    // Recoge el estado de los checkboxes y lo envía al backend
    const seleccion = [];
    document.querySelectorAll('.chk-contacto').forEach(chk => {
        seleccion.push({ number: chk.getAttribute('data-number'), send: valor !== null ? valor : chk.checked });
    });
    fetch('api/contacts_update_selection.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ seleccion })
    });
}

// Event listeners para contactos
document.addEventListener('DOMContentLoaded', function() {
    // Buscador en tiempo real
    const buscador = document.getElementById('buscadorContactos');
    if (buscador) {
        let timeoutBusqueda;
        buscador.addEventListener('input', function() {
            clearTimeout(timeoutBusqueda);
            timeoutBusqueda = setTimeout(() => {
                terminoBusqueda = this.value;
                aplicarFiltrosYRenderizar();
            }, 300); // Debounce de 300ms
        });
    }
    
    // Botón limpiar búsqueda
    const btnLimpiarBusqueda = document.getElementById('btnLimpiarBusqueda');
    if (btnLimpiarBusqueda) {
        btnLimpiarBusqueda.addEventListener('click', function() {
            document.getElementById('buscadorContactos').value = '';
            terminoBusqueda = '';
            aplicarFiltrosYRenderizar();
        });
    }
    
    // Controles de paginación
    const btnPaginaAnterior = document.getElementById('btnPaginaAnterior');
    if (btnPaginaAnterior) {
        btnPaginaAnterior.addEventListener('click', function() {
            if (paginaActual > 1) {
                paginaActual--;
                renderizarContactosPagina();
                actualizarControlesPaginacion();
            }
        });
    }
    
    const btnPaginaSiguiente = document.getElementById('btnPaginaSiguiente');
    if (btnPaginaSiguiente) {
        btnPaginaSiguiente.addEventListener('click', function() {
            const totalPaginas = Math.ceil(contactosFiltrados.length / contactosPorPagina);
            if (paginaActual < totalPaginas) {
                paginaActual++;
                renderizarContactosPagina();
                actualizarControlesPaginacion();
            }
        });
    }
    
    // Botón actualizar contactos
    const btnActualizarContactos = document.getElementById('btnActualizarContactos');
    if (btnActualizarContactos) {
        btnActualizarContactos.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('modalCargaContactos'));
            document.getElementById('barraProgresoContactos').style.width = '0%';
            document.getElementById('barraProgresoContactos').textContent = '0%';
            document.getElementById('estadoImportacionContactos').textContent = 'Iniciando importación...';
            modal.show();
            // Simulación de barra de carga
            let progreso = 0;
            const interval = setInterval(() => {
                progreso += 10;
                if (progreso > 90) progreso = 90;
                document.getElementById('barraProgresoContactos').style.width = progreso + '%';
                document.getElementById('barraProgresoContactos').textContent = progreso + '%';
            }, 200);
            fetch('api/import_contacts.php', { method: 'POST' })
                .then(r => r.json())
                .then(data => {
                    clearInterval(interval);
                    document.getElementById('barraProgresoContactos').style.width = '100%';
                    document.getElementById('barraProgresoContactos').textContent = '100%';
                    if (data.success) {
                        document.getElementById('estadoImportacionContactos').textContent = '¡Importación completada!';
                        setTimeout(() => {
                            modal.hide();
                            showNotification('Contactos importados: ' + data.imported + ', actualizados: ' + data.updated, 'success');
                            obtenerContactos();
                        }, 800);
                    } else {
                        document.getElementById('estadoImportacionContactos').textContent = 'Error: ' + (data.message || 'No se pudo importar contactos');
                    }
                })
                .catch(() => {
                    clearInterval(interval);
                    document.getElementById('estadoImportacionContactos').textContent = 'Error de red al importar contactos';
                });
        });
    }

    // Botón seleccionar todos
    const btnSeleccionarTodos = document.getElementById('btnSeleccionarTodos');
    if (btnSeleccionarTodos) {
        btnSeleccionarTodos.addEventListener('click', function() {
            document.querySelectorAll('.chk-contacto').forEach(chk => { chk.checked = true; });
            guardarSeleccionContactos();
        });
    }

    // Botón deseleccionar todos
    const btnDeseleccionarTodos = document.getElementById('btnDeseleccionarTodos');
    if (btnDeseleccionarTodos) {
        btnDeseleccionarTodos.addEventListener('click', function() {
            document.querySelectorAll('.chk-contacto').forEach(chk => { chk.checked = false; });
            guardarSeleccionContactos();
        });
    }

    // Guardar selección individual
    const contactosLista = document.getElementById('contactos-lista');
    if (contactosLista) {
        contactosLista.addEventListener('change', function(e) {
            if (e.target.classList.contains('chk-contacto')) {
                guardarSeleccionContactos();
            }
        });
    }

    // Cargar contactos al iniciar
    obtenerContactos();
});

// Importar contactos desde JSON
const inputImportarContactos = document.getElementById('inputImportarContactos');
if (inputImportarContactos) {
    inputImportarContactos.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(evt) {
            try {
                const data = JSON.parse(evt.target.result);
                let contactos = Array.isArray(data) ? data : [data];
                // Validar estructura mínima
                if (!contactos.every(c => c.remoteJid && c.pushName)) {
                    showNotification('El archivo no tiene la estructura esperada.', 'error');
                    return;
                }
                // Mostrar modal de progreso
                const modal = new bootstrap.Modal(document.getElementById('modalCargaContactos'));
                document.getElementById('barraProgresoContactos').style.width = '0%';
                document.getElementById('barraProgresoContactos').textContent = '0%';
                document.getElementById('estadoImportacionContactos').textContent = 'Importando contactos...';
                modal.show();
                // Enviar al backend
                fetch('api/import_contacts_json.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ contactos })
                })
                .then(r => r.json())
                .then(data => {
                    document.getElementById('barraProgresoContactos').style.width = '100%';
                    document.getElementById('barraProgresoContactos').textContent = '100%';
                    if (data.success) {
                        document.getElementById('estadoImportacionContactos').textContent = '¡Importación completada!';
                        setTimeout(() => {
                            modal.hide();
                            showNotification('Contactos importados: ' + data.imported + ', actualizados: ' + data.updated, 'success');
                            obtenerContactos();
                        }, 800);
                    } else {
                        document.getElementById('estadoImportacionContactos').textContent = 'Error: ' + (data.message || 'No se pudo importar contactos');
                    }
                })
                .catch(() => {
                    document.getElementById('estadoImportacionContactos').textContent = 'Error de red al importar contactos';
                });
            } catch (err) {
                showNotification('El archivo no es un JSON válido.', 'error');
            }
        };
        reader.readAsText(file);
    });
}
</script> 