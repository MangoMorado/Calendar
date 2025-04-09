<?php 
/**
 * Vista principal de Libreta de Notas
 */
require_once 'includes/header.php';

// CSS específico para notas
$extraStyles = '
<link rel="stylesheet" href="assets/css/modules/notes.css">
';
?>

<div class="container">
    <div class="page-header">
        <h2><i class="bi bi-journal-text"></i> Libreta de Notas</h2>
        <div class="page-actions">
            <a href="notes.php?action=create" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Nueva Nota
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php 
                echo $_SESSION['success_message']; 
                unset($_SESSION['success_message']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?php 
                echo $_SESSION['error_message']; 
                unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>

    <div class="notes-container">
        <div class="notes-sidebar">
            <div class="notes-filter">
                <input type="text" id="searchNotes" class="form-control" placeholder="Buscar notas...">
                <div class="filter-buttons">
                    <button class="btn btn-filter active" data-filter="all">Todas</button>
                    <button class="btn btn-filter" data-filter="nota">Notas</button>
                    <button class="btn btn-filter" data-filter="sugerencia">Sugerencias</button>
                    <button class="btn btn-filter" data-filter="otro">Otros</button>
                </div>
            </div>
            <div class="notes-list" id="notesList">
                <!-- Aquí se cargarán las notas mediante JavaScript -->
                <div class="loading-indicator">
                    <i class="bi bi-hourglass-split"></i> Cargando notas...
                </div>
            </div>
        </div>
        <div class="note-detail" id="noteDetail">
            <div class="note-detail-empty">
                <i class="bi bi-journal-text"></i>
                <p>Selecciona una nota para ver su contenido</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div class="delete-confirmation-modal" id="deleteNoteModal">
    <div class="modal-content">
        <h4>Confirmar eliminación</h4>
        <p>¿Estás seguro de que deseas eliminar esta nota? Esta acción no se puede deshacer.</p>
        <div class="modal-actions">
            <button class="btn btn-secondary" id="cancelDeleteBtn">Cancelar</button>
            <form action="notes.php?action=delete" method="post" id="deleteNoteForm">
                <input type="hidden" name="id" id="deleteNoteId">
                <button type="submit" class="btn btn-danger">Eliminar</button>
            </form>
        </div>
    </div>
</div>

<script>
// Variables globales
let currentFilter = 'all';
let allNotes = [];
let activeNoteId = null;

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    // Cargar las notas al cargar la página
    loadNotes();
    
    // Configurar eventos para filtros
    document.querySelectorAll('.btn-filter').forEach(button => {
        button.addEventListener('click', function() {
            currentFilter = this.dataset.filter;
            document.querySelectorAll('.btn-filter').forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            filterNotes();
        });
    });
    
    // Configurar evento para búsqueda
    document.getElementById('searchNotes').addEventListener('input', function() {
        filterNotes();
    });
    
    // Configurar eventos para modal de eliminación
    const modal = document.getElementById('deleteNoteModal');
    
    document.getElementById('cancelDeleteBtn').addEventListener('click', function(e) {
        e.preventDefault();
        modal.classList.remove('active');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    });
    
    // Cerrar modal si se hace clic fuera de él
    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
    });
});

// Cargar todas las notas del usuario
function loadNotes() {
    fetchWithAuthAndErrorHandling('api/notes.php?action=get_notes')
        .then(data => {
            if (data.success) {
                allNotes = data.data.notes;
                renderNotes();
            } else {
                document.getElementById('notesList').innerHTML = `
                    <div class="alert alert-warning">
                        ${data.message || 'Error al cargar las notas.'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('notesList').innerHTML = `
                <div class="alert alert-danger">
                    Error de conexión al cargar las notas.
                </div>
            `;
        });
}

// Renderizar lista de notas
function renderNotes() {
    const notesList = document.getElementById('notesList');
    
    if (allNotes.length === 0) {
        notesList.innerHTML = `
            <div class="alert alert-info">
                No hay notas disponibles. ¡Crea una nueva!
            </div>
        `;
        return;
    }
    
    let html = '';
    allNotes.forEach(note => {
        // Aplicar filtro
        if (currentFilter !== 'all' && note.type !== currentFilter) {
            return;
        }
        
        // Formatear fecha
        const createdDate = new Date(note.created_at);
        const formattedDate = createdDate.toLocaleDateString('es-ES');
        
        // Crear HTML para la nota
        html += `
            <div class="note-item ${activeNoteId === note.id ? 'active' : ''}" data-id="${note.id}" onclick="loadNoteDetail(${note.id})">
                <div class="note-title">${note.title}</div>
                <div class="note-preview">${note.content.substring(0, 100)}${note.content.length > 100 ? '...' : ''}</div>
                <div class="note-meta">
                    <span>${formattedDate}</span>
                    <span>${getTypeLabel(note.type)}</span>
                </div>
            </div>
        `;
    });
    
    notesList.innerHTML = html;
}

// Obtener etiqueta para tipo de nota
function getTypeLabel(type) {
    switch (type) {
        case 'nota': return 'Nota';
        case 'sugerencia': return 'Sugerencia';
        case 'otro': return 'Otro';
        default: return 'Nota';
    }
}

// Filtrar notas por tipo y búsqueda
function filterNotes() {
    const searchText = document.getElementById('searchNotes').value.toLowerCase();
    
    // Filtrar por tipo y texto de búsqueda
    const filteredNotes = allNotes.filter(note => {
        // Filtrar por tipo
        if (currentFilter !== 'all' && note.type !== currentFilter) {
            return false;
        }
        
        // Filtrar por texto
        if (searchText && !note.title.toLowerCase().includes(searchText) && 
            !note.content.toLowerCase().includes(searchText)) {
            return false;
        }
        
        return true;
    });
    
    // Actualizar vista con notas filtradas
    const notesList = document.getElementById('notesList');
    
    if (filteredNotes.length === 0) {
        notesList.innerHTML = `
            <div class="alert alert-info">
                No se encontraron notas que coincidan con el filtro.
            </div>
        `;
        return;
    }
    
    let html = '';
    filteredNotes.forEach(note => {
        // Formatear fecha
        const createdDate = new Date(note.created_at);
        const formattedDate = createdDate.toLocaleDateString('es-ES');
        
        // Crear HTML para la nota
        html += `
            <div class="note-item ${activeNoteId === note.id ? 'active' : ''}" data-id="${note.id}" onclick="loadNoteDetail(${note.id})">
                <div class="note-title">${note.title}</div>
                <div class="note-preview">${note.content.substring(0, 100)}${note.content.length > 100 ? '...' : ''}</div>
                <div class="note-meta">
                    <span>${formattedDate}</span>
                    <span>${getTypeLabel(note.type)}</span>
                </div>
            </div>
        `;
    });
    
    notesList.innerHTML = html;
}

// Cargar detalle de una nota
function loadNoteDetail(noteId) {
    activeNoteId = noteId;
    
    // Actualizar clase activa en la lista
    document.querySelectorAll('.note-item').forEach(item => {
        item.classList.remove('active');
        if (parseInt(item.dataset.id) === noteId) {
            item.classList.add('active');
        }
    });
    
    // Mostrar cargando en el detalle
    document.getElementById('noteDetail').innerHTML = `
        <div class="loading-indicator">
            <i class="bi bi-hourglass-split"></i> Cargando detalle...
        </div>
    `;
    
    // Cargar detalle mediante API
    fetchWithAuthAndErrorHandling(`api/notes.php?action=get_note&id=${noteId}`)
        .then(data => {
            if (data.success) {
                renderNoteDetail(data.note, data.can_edit);
            } else {
                document.getElementById('noteDetail').innerHTML = `
                    <div class="alert alert-warning">
                        ${data.message || 'Error al cargar el detalle de la nota.'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('noteDetail').innerHTML = `
                <div class="alert alert-danger">
                    Error de conexión al cargar el detalle.
                </div>
            `;
        });
}

// Renderizar detalle de nota
function renderNoteDetail(note, canEdit) {
    // Formatear fechas
    const createdDate = new Date(note.created_at);
    const formattedCreatedDate = createdDate.toLocaleString('es-ES');
    
    let updatedInfo = '';
    if (note.created_at !== note.updated_at) {
        const updatedDate = new Date(note.updated_at);
        const formattedUpdatedDate = updatedDate.toLocaleString('es-ES');
        updatedInfo = `
            <div class="meta-item updated">
                <i class="bi bi-clock-history"></i>
                <span>Actualizado: ${formattedUpdatedDate}</span>
            </div>
        `;
    }
    
    // Configurar iconos y textos según tipo y visibilidad
    const typeLabels = {
        'nota': 'Nota',
        'sugerencia': 'Sugerencia',
        'otro': 'Otro'
    };
    const typeLabelText = typeLabels[note.type] || 'Nota';
    
    const visibilityIcon = note.visibility === 'todos' ? 'bi-eye' : 'bi-eye-slash';
    const visibilityText = note.visibility === 'todos' ? 'Visible para todos' : 'Solo yo';
    
    // Crear HTML para el detalle
    let html = `
        <div class="note-header">
            <h3>${note.title}</h3>
            <div class="note-actions">
                ${canEdit ? `
                    <a href="notes.php?action=edit&id=${note.id}" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                    <button class="btn btn-sm btn-danger" onclick="showDeleteModal(${note.id})">
                        <i class="bi bi-trash"></i> Eliminar
                    </button>
                ` : ''}
                <a href="notes.php?action=view&id=${note.id}" class="btn btn-sm btn-secondary">
                    <i class="bi bi-box-arrow-up-right"></i> Ver página completa
                </a>
            </div>
        </div>
        
        <div class="note-meta-info">
            <div class="meta-item type ${note.type}">
                <i class="bi bi-tag"></i>
                <span>${typeLabelText}</span>
            </div>
            <div class="meta-item author">
                <i class="bi bi-person"></i>
                <span>Por: ${note.author_name}</span>
            </div>
            <div class="meta-item date">
                <i class="bi bi-calendar"></i>
                <span>Creado: ${formattedCreatedDate}</span>
            </div>
            ${updatedInfo}
            <div class="meta-item visibility ${note.visibility}">
                <i class="bi ${visibilityIcon}"></i>
                <span>${visibilityText}</span>
            </div>
        </div>
        
        <div class="note-content">
            ${note.content.replace(/\n/g, '<br>')}
        </div>
    `;
    
    document.getElementById('noteDetail').innerHTML = html;
}

// Mostrar modal de confirmación de eliminación
function showDeleteModal(noteId) {
    const modal = document.getElementById('deleteNoteModal');
    document.getElementById('deleteNoteId').value = noteId;
    modal.style.display = 'block';
    // Usamos setTimeout para permitir que el navegador procese el cambio de display
    // antes de agregar la clase que activa la animación
    setTimeout(() => {
        modal.classList.add('active');
    }, 10);
}
</script>

<?php require_once 'includes/footer.php'; ?> 