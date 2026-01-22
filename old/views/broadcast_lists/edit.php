<?php
// Vista para editar una lista de difusión
$currentList = $data['currentList'];
$contactsInList = $data['contactsInList'];
$availableContacts = $data['availableContacts'];
$action = $_GET['action'] ?? 'edit';
?>

<!-- Incluir estilos específicos para listas de difusión -->
<link rel="stylesheet" href="assets/css/modules/broadcast.css">

<div class="container">
    <div class="config-header">
        <h1><i class="bi bi-megaphone"></i> Listas de Difusión</h1>
        <p class="text-muted">Gestiona tus listas de contactos para envío de difusiones masivas.</p>
    </div>

    <!-- Mensajes -->
    <?php if ($message) { ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php } ?>

    <?php if ($error) { ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php } ?>

    <!-- Formulario para editar lista -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-pencil"></i> 
                        <?php echo $action === 'edit' ? 'Editar' : 'Ver'; ?> Lista
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="list_id" value="<?php echo $currentList['id']; ?>">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre de la lista *</label>
                            <input type="text" class="form-control" id="name" name="name" required 
                                   value="<?php echo htmlspecialchars($currentList['name']); ?>"
                                   <?php echo $action === 'view' ? 'readonly' : ''; ?>>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Descripción</label>
                            <textarea class="form-control" id="description" name="description" rows="3"
                                      <?php echo $action === 'view' ? 'readonly' : ''; ?>><?php echo htmlspecialchars($currentList['description']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                                       <?php echo $currentList['is_active'] ? 'checked' : ''; ?>
                                       <?php echo $action === 'view' ? 'disabled' : ''; ?>>
                                <label class="form-check-label" for="is_active">
                                    Lista activa
                                </label>
                            </div>
                        </div>
                        
                        <?php if ($action === 'edit') { ?>
                            <div class="d-flex gap-2">
                                <button type="submit" name="update_list" class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> Actualizar
                                </button>
                                <a href="?action=list" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Volver
                                </a>
                            </div>
                        <?php } else { ?>
                            <div class="d-flex gap-2">
                                <a href="?action=edit&id=<?php echo $currentList['id']; ?>" class="btn btn-primary">
                                    <i class="bi bi-pencil"></i> Editar
                                </a>
                                <a href="?action=list" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Volver
                                </a>
                            </div>
                        <?php } ?>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <!-- Gestión de contactos -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-people"></i> Contactos en la Lista</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Total: <?php echo count($contactsInList); ?> contactos</strong>
                    </div>
                    
                    <?php if (! empty($contactsInList)) { ?>
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm align-middle" id="contactsInListTable">
                                <thead>
                                    <tr>
                                        <th>NOMBRE</th>
                                        <th>NÚMERO</th>
                                        <th class="text-center">ELIMINAR</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contactsInList as $contact) { ?>
                                        <tr data-contact-id="<?php echo $contact['id']; ?>">
                                            <td><?php echo htmlspecialchars($contact['pushName'] ?: 'Sin nombre'); ?></td>
                                            <td><?php echo htmlspecialchars($contact['number']); ?></td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-link text-danger btn-sm delete-contact-btn" title="Eliminar contacto">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <script>
                        // Eliminar contacto de la lista vía AJAX
                        document.querySelectorAll('.delete-contact-btn').forEach(btn => {
                            btn.addEventListener('click', function() {
                                if (!confirm('¿Seguro que deseas eliminar este contacto de la lista?')) return;
                                const row = this.closest('tr');
                                const contactId = row.getAttribute('data-contact-id');
                                const listId = <?php echo (int) $currentList['id']; ?>;
                                const formData = new FormData();
                                formData.append('list_id', listId);
                                formData.append('contact_ids[]', contactId);
                                formData.append('remove_contacts', '1');
                                fetch(window.location.pathname + window.location.search, {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(r => r.text())
                                .then(() => {
                                    row.remove();
                                });
                            });
                        });
                        </script>
                    <?php } else { ?>
                        <p class="text-muted">No hay contactos en esta lista.</p>
                    <?php } ?>
                    
                    <?php if ($action === 'edit') { ?>
                        <hr>
                        <h6>Agregar contactos</h6>
                        <form id="addContactsForm" method="POST">
                            <input type="hidden" name="list_id" value="<?php echo $currentList['id']; ?>">
                            
                            <!-- Filtro: Solo contactos con nombre -->
                            <div class="mb-2 d-flex align-items-center">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="only_named_switch">
                                    <label class="form-check-label ms-2" for="only_named_switch">Mostrar solo contactos con nombre</label>
                                </div>
                            </div>
                            
                            <!-- Búsqueda optimizada -->
                            <div class="mb-3">
                                <label for="contact_search" class="form-label">Buscar contacto</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="contact_search" 
                                           placeholder="Buscar por nombre o número..." 
                                           autocomplete="off">
                                    <button type="button" class="btn btn-secondary" id="clear_search">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </div>
                                <small class="form-text text-muted">
                                    Escribe para buscar contactos. Los resultados se cargan dinámicamente.
                                </small>
                            </div>

                            <!-- Contenedor de resultados de búsqueda -->
                            <div class="mb-3" id="search_results_container" style="display: none;">
                                <label class="form-label">Resultados de búsqueda</label>
                                <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                                    <div id="search_results"></div>
                                    <div id="search_loading" class="text-center text-muted" style="display: none;">
                                        <i class="bi bi-hourglass-split"></i> Buscando...
                                    </div>
                                </div>
                            </div>

                            <!-- Select optimizado con paginación -->
                            <div class="mb-3">
                                <label for="contact_ids" class="form-label">Seleccionar contactos</label>
                                <div class="border rounded">
                                    <div class="p-2 border-bottom bg-light">
                                        <small class="text-muted">
                                            <span id="contacts_info">Cargando contactos...</span>
                                            <button type="button" class="btn btn-link btn-sm float-end" id="select_all_btn" style="display: none;">
                                                Seleccionar todos
                                            </button>
                                        </small>
                                    </div>
                                    <div style="max-height: 250px; overflow-y: auto;">
                                        <div id="contacts_list" class="p-2">
                                            <!-- Los contactos se cargan aquí dinámicamente -->
                                        </div>
                                        <div class="contacts-pagination">
                                            <nav>
                                                <ul class="pagination pagination-sm mb-0" id="contacts_pagination">
                                                    <!-- Paginación dinámica -->
                                                </ul>
                                            </nav>
                                        </div>
                                        <div id="contacts_loading" class="text-center p-3">
                                            <i class="bi bi-hourglass-split"></i> Cargando contactos...
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="add_contacts" class="btn btn-primary btn-sm" id="add_contacts_btn" disabled>
                                <i class="bi bi-plus-circle"></i> Agregar Contactos Seleccionados
                            </button>
                        </form>

                        <!-- Formulario para agregar números manualmente -->
                        <hr>
                        <h6>Agregar número manualmente</h6>
                        <form id="addManualNumberForm" method="POST">
                            <input type="hidden" name="list_id" value="<?php echo $currentList['id']; ?>">
                            <div class="mb-3">
                                <label for="manual_number" class="form-label">Número de WhatsApp</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="manual_number" name="manual_number" 
                                           placeholder="Ej: 573217058135" pattern="[0-9]+" maxlength="15" required>
                                    <span class="input-group-text">@s.whatsapp.net</span>
                                </div>
                                <small class="form-text text-muted">
                                    Ingresa solo los números (sin espacios, guiones o símbolos). El sistema agregará automáticamente @s.whatsapp.net
                                </small>
                            </div>
                            <div class="mb-3">
                                <label for="manual_name" class="form-label">Nombre (opcional)</label>
                                <input type="text" class="form-control" id="manual_name" name="manual_name" 
                                       placeholder="Nombre del contacto" maxlength="100">
                            </div>
                            <button type="submit" name="add_manual_number" class="btn btn-warning btn-sm">
                                <i class="bi bi-plus-circle"></i> Agregar Número Manual
                            </button>
                        </form>

                        <button type="button" class="btn btn-danger ms-2" id="btnLimpiarContactos"><i class="bi bi-trash"></i> Limpiar</button>

                        <script>
                        // Variables globales para la gestión de contactos
                        let currentPage = 1;
                        let totalPages = 1;
                        let searchTimeout = null;
                        let selectedContacts = new Set();
                        const contactsPerPage = 100;
                        const listId = <?php echo (int) $currentList['id']; ?>;
                        let onlyNamed = false;

                        // Función para cargar contactos con paginación
                        function loadContacts(page = 1, search = '') {
                            const loadingEl = document.getElementById('contacts_loading');
                            const listEl = document.getElementById('contacts_list');
                            
                            loadingEl.style.display = 'block';
                            listEl.innerHTML = '';

                            const formData = new FormData();
                            formData.append('action', 'get_contacts');
                            formData.append('page', page);
                            formData.append('search', search);
                            formData.append('per_page', contactsPerPage);
                            if (onlyNamed) formData.append('only_named', '1');

                            fetch('api/contacts_list.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                loadingEl.style.display = 'none';
                                
                                if (data.success) {
                                    currentPage = page;
                                    totalPages = data.total_pages;
                                    
                                    // Actualizar información
                                    document.getElementById('contacts_info').textContent = 
                                        `Página ${page} de ${totalPages} - ${data.total_contacts} contactos totales`;
                                    
                                    // Renderizar contactos
                                    renderContacts(data.contacts);
                                    // Renderizar paginación
                                    renderPagination(page, totalPages);
                                    
                                    // Mostrar/ocultar botón de seleccionar todos
                                    const selectAllBtn = document.getElementById('select_all_btn');
                                    if (data.contacts.length > 0) {
                                        selectAllBtn.style.display = 'inline';
                                    } else {
                                        selectAllBtn.style.display = 'none';
                                    }
                                } else {
                                    listEl.innerHTML = '<div class="text-center text-muted">Error al cargar contactos</div>';
                                    renderPagination(1, 1);
                                }
                            })
                            .catch(error => {
                                loadingEl.style.display = 'none';
                                listEl.innerHTML = '<div class="text-center text-danger">Error de conexión</div>';
                                renderPagination(1, 1);
                                console.error('Error:', error);
                            });
                        }

                        // Función para renderizar contactos
                        function renderContacts(contacts) {
                            const listEl = document.getElementById('contacts_list');
                            
                            if (contacts.length === 0) {
                                listEl.innerHTML = '<div class="text-center text-muted">No se encontraron contactos</div>';
                                return;
                            }

                            const contactsHtml = contacts.map(contact => {
                                const isSelected = selectedContacts.has(contact.id);
                                return `
                                    <div class="form-check contact-item" data-contact-id="${contact.id}">
                                        <input class="form-check-input" type="checkbox" 
                                               id="contact_${contact.id}" 
                                               value="${contact.id}" 
                                               ${isSelected ? 'checked' : ''}>
                                        <label class="form-check-label" for="contact_${contact.id}">
                                            <strong>${contact.pushName || 'Sin nombre'}</strong>
                                            <br><small class="text-muted">${contact.number}</small>
                                        </label>
                                    </div>
                                `;
                            }).join('');

                            listEl.innerHTML = contactsHtml;

                            // Agregar event listeners a los checkboxes
                            listEl.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                                checkbox.addEventListener('change', function() {
                                    const contactId = this.value;
                                    if (this.checked) {
                                        selectedContacts.add(contactId);
                                    } else {
                                        selectedContacts.delete(contactId);
                                    }
                                    updateAddButton();
                                });
                            });
                        }

                        // Función para actualizar el botón de agregar
                        function updateAddButton() {
                            const addBtn = document.getElementById('add_contacts_btn');
                            addBtn.disabled = selectedContacts.size === 0;
                            addBtn.innerHTML = `<i class="bi bi-plus-circle"></i> Agregar ${selectedContacts.size} Contacto${selectedContacts.size !== 1 ? 's' : ''}`;
                        }

                        // Búsqueda con debounce
                        document.getElementById('contact_search').addEventListener('input', function() {
                            const search = this.value.trim();
                            const resultsContainer = document.getElementById('search_results_container');
                            
                            clearTimeout(searchTimeout);
                            
                            if (search.length < 2) {
                                resultsContainer.style.display = 'none';
                                loadContacts(1, '');
                                return;
                            }

                            searchTimeout = setTimeout(() => {
                                performSearch(search);
                            }, 300);
                        });

                        // Función de búsqueda
                        function performSearch(search) {
                            const resultsContainer = document.getElementById('search_results_container');
                            const resultsEl = document.getElementById('search_results');
                            const loadingEl = document.getElementById('search_loading');
                            
                            resultsContainer.style.display = 'block';
                            loadingEl.style.display = 'block';
                            resultsEl.innerHTML = '';

                            const formData = new FormData();
                            formData.append('action', 'search_contacts');
                            formData.append('search', search);
                            formData.append('limit', 20);

                            fetch('api/contacts_list.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                loadingEl.style.display = 'none';
                                
                                if (data.success && data.contacts.length > 0) {
                                    const resultsHtml = data.contacts.map(contact => {
                                        const isSelected = selectedContacts.has(contact.id);
                                        return `
                                            <div class="form-check search-result-item" data-contact-id="${contact.id}">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="search_${contact.id}" 
                                                       value="${contact.id}" 
                                                       ${isSelected ? 'checked' : ''}>
                                                <label class="form-check-label" for="search_${contact.id}">
                                                    <strong>${contact.pushName || 'Sin nombre'}</strong>
                                                    <br><small class="text-muted">${contact.number}</small>
                                                </label>
                                            </div>
                                        `;
                                    }).join('');
                                    
                                    resultsEl.innerHTML = resultsHtml;
                                    
                                    // Event listeners para resultados de búsqueda
                                    resultsEl.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                                        checkbox.addEventListener('change', function() {
                                            const contactId = this.value;
                                            if (this.checked) {
                                                selectedContacts.add(contactId);
                                            } else {
                                                selectedContacts.delete(contactId);
                                            }
                                            updateAddButton();
                                        });
                                    });
                                } else {
                                    resultsEl.innerHTML = '<div class="text-center text-muted">No se encontraron resultados</div>';
                                }
                            })
                            .catch(error => {
                                loadingEl.style.display = 'none';
                                resultsEl.innerHTML = '<div class="text-center text-danger">Error en la búsqueda</div>';
                                console.error('Error:', error);
                            });
                        }

                        // Limpiar búsqueda
                        document.getElementById('clear_search').addEventListener('click', function() {
                            document.getElementById('contact_search').value = '';
                            document.getElementById('search_results_container').style.display = 'none';
                            loadContacts(1, '');
                        });

                        // Seleccionar todos los contactos de la página actual
                        document.getElementById('select_all_btn').addEventListener('click', function() {
                            const checkboxes = document.querySelectorAll('#contacts_list input[type="checkbox"]');
                            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                            
                            checkboxes.forEach(checkbox => {
                                checkbox.checked = !allChecked;
                                const contactId = checkbox.value;
                                if (!allChecked) {
                                    selectedContacts.add(contactId);
                                } else {
                                    selectedContacts.delete(contactId);
                                }
                            });
                            
                            updateAddButton();
                        });

                        // Función para renderizar la paginación
                        function renderPagination(current, total) {
                            const pag = document.getElementById('contacts_pagination');
                            pag.innerHTML = '';
                            if (total <= 1) return;
                            // Botón primera página
                            const first = document.createElement('li');
                            first.className = 'page-item' + (current === 1 ? ' disabled' : '');
                            first.innerHTML = `<a class="page-link" href="#" tabindex="-1">&laquo;&laquo;</a>`;
                            first.onclick = function(e) { e.preventDefault(); if (current > 1) loadContacts(1, document.getElementById('contact_search').value.trim()); };
                            pag.appendChild(first);
                            // Botón anterior
                            const prev = document.createElement('li');
                            prev.className = 'page-item' + (current === 1 ? ' disabled' : '');
                            prev.innerHTML = `<a class="page-link" href="#" tabindex="-1">&laquo;</a>`;
                            prev.onclick = function(e) { e.preventDefault(); if (current > 1) loadContacts(current - 1, document.getElementById('contact_search').value.trim()); };
                            pag.appendChild(prev);
                            // Páginas
                            let start = Math.max(1, current - 2);
                            let end = Math.min(total, current + 2);
                            if (start > 1) {
                                // Primera página
                                const li = document.createElement('li');
                                li.className = 'page-item';
                                li.innerHTML = `<a class="page-link" href="#">1</a>`;
                                li.onclick = function(e) { e.preventDefault(); loadContacts(1, document.getElementById('contact_search').value.trim()); };
                                pag.appendChild(li);
                                if (start > 2) {
                                    const dots = document.createElement('li');
                                    dots.className = 'page-item disabled';
                                    dots.innerHTML = `<span class="page-link">...</span>`;
                                    pag.appendChild(dots);
                                }
                            }
                            for (let i = start; i <= end; i++) {
                                const li = document.createElement('li');
                                li.className = 'page-item' + (i === current ? ' active' : '');
                                li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
                                li.onclick = function(e) { e.preventDefault(); if (i !== current) loadContacts(i, document.getElementById('contact_search').value.trim()); };
                                pag.appendChild(li);
                            }
                            if (end < total) {
                                if (end < total - 1) {
                                    const dots = document.createElement('li');
                                    dots.className = 'page-item disabled';
                                    dots.innerHTML = `<span class="page-link">...</span>`;
                                    pag.appendChild(dots);
                                }
                                // Última página
                                const li = document.createElement('li');
                                li.className = 'page-item';
                                li.innerHTML = `<a class="page-link" href="#">${total}</a>`;
                                li.onclick = function(e) { e.preventDefault(); loadContacts(total, document.getElementById('contact_search').value.trim()); };
                                pag.appendChild(li);
                            }
                            // Botón siguiente
                            const next = document.createElement('li');
                            next.className = 'page-item' + (current === total ? ' disabled' : '');
                            next.innerHTML = `<a class="page-link" href="#">&raquo;</a>`;
                            next.onclick = function(e) { e.preventDefault(); if (current < total) loadContacts(current + 1, document.getElementById('contact_search').value.trim()); };
                            pag.appendChild(next);
                            // Botón última página
                            const last = document.createElement('li');
                            last.className = 'page-item' + (current === total ? ' disabled' : '');
                            last.innerHTML = `<a class="page-link" href="#">&raquo;&raquo;</a>`;
                            last.onclick = function(e) { e.preventDefault(); if (current < total) loadContacts(total, document.getElementById('contact_search').value.trim()); };
                            pag.appendChild(last);
                        }

                        // Formulario para agregar contactos seleccionados
                        document.getElementById('addContactsForm').addEventListener('submit', function(e) {
                            e.preventDefault();
                            
                            if (selectedContacts.size === 0) {
                                alert('Por favor selecciona al menos un contacto.');
                                return;
                            }

                            const formData = new FormData();
                            formData.append('list_id', listId);
                            Array.from(selectedContacts).forEach(id => formData.append('contact_ids[]', id));
                            formData.append('add_contacts', '1');

                            const submitBtn = document.getElementById('add_contacts_btn');
                            const originalText = submitBtn.innerHTML;
                            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Agregando...';
                            submitBtn.disabled = true;

                            fetch(window.location.pathname + window.location.search, {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.text())
                            .then(() => {
                                window.location.reload();
                            })
                            .catch(error => {
                                alert('Error al agregar contactos: ' + error.message);
                                submitBtn.innerHTML = originalText;
                                submitBtn.disabled = false;
                            });
                        });

                        // Validación y procesamiento del formulario de número manual
                        document.getElementById('addManualNumberForm').addEventListener('submit', function(e) {
                            e.preventDefault();
                            const number = document.getElementById('manual_number').value.trim();
                            const name = document.getElementById('manual_name').value.trim();
                            
                            // Validar formato del número
                            if (!/^\d{10,15}$/.test(number)) {
                                alert('El número debe tener entre 10 y 15 dígitos numéricos.');
                                return;
                            }
                            
                            // Confirmar antes de agregar
                            const fullNumber = number + '@s.whatsapp.net';
                            const confirmMessage = `¿Estás seguro de agregar el número:\n${fullNumber}${name ? '\nNombre: ' + name : ''}`;
                            
                            if (!confirm(confirmMessage)) {
                                return;
                            }
                            
                            const formData = new FormData();
                            formData.append('list_id', this.list_id.value);
                            formData.append('manual_number', number);
                            formData.append('manual_name', name);
                            formData.append('add_manual_number', '1');
                            
                            // Mostrar indicador de carga
                            const submitBtn = this.querySelector('button[type="submit"]');
                            const originalText = submitBtn.innerHTML;
                            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Agregando...';
                            submitBtn.disabled = true;
                            
                            fetch(window.location.pathname + window.location.search, {
                                method: 'POST',
                                body: formData
                            })
                            .then(r => r.text())
                            .then(() => {
                                // Recargar la página para mostrar el nuevo contacto
                                window.location.reload();
                            })
                            .catch(error => {
                                alert('Error al agregar el número: ' + error.message);
                                submitBtn.innerHTML = originalText;
                                submitBtn.disabled = false;
                            });
                        });
                        
                        // Formatear automáticamente el número mientras se escribe
                        document.getElementById('manual_number').addEventListener('input', function() {
                            // Remover cualquier carácter que no sea número
                            this.value = this.value.replace(/[^0-9]/g, '');
                        });

                        // Cargar contactos iniciales
                        loadContacts(1, '');

                        // Filtro: Solo contactos con nombre
                        document.getElementById('only_named_switch').addEventListener('change', function() {
                            onlyNamed = this.checked;
                            loadContacts(1, document.getElementById('contact_search').value.trim());
                        });

                        // Limpiar contactos
                        document.getElementById('btnLimpiarContactos').addEventListener('click', function() {
                            if (!confirm('¿Estás seguro de que deseas limpiar la lista de contactos?')) return;
                            const formData = new FormData();
                            formData.append('list_id', listId);
                            formData.append('clear_contacts', '1');
                            fetch(window.location.pathname + window.location.search, {
                                method: 'POST',
                                body: formData
                            })
                            .then(r => r.text())
                            .then(() => {
                                window.location.reload();
                            })
                            .catch(error => {
                                alert('Error al limpiar la lista de contactos: ' + error.message);
                            });
                        });
                        </script>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div> 