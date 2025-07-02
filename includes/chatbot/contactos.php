<div class="tab-pane fade" id="tab-contactos" role="tabpanel">
    <div class="form-section">
        <h2><i class="bi bi-person-lines-fill"></i> Contactos</h2>
        <div class="mb-3">
            <button id="btnActualizarContactos" class="btn btn-primary">
                <i class="bi bi-arrow-repeat"></i> Actualizar contactos
            </button>
            <label for="inputImportarContactos" class="btn btn-secondary ms-2 mb-0">
                <i class="bi bi-upload"></i> Importar
                <input type="file" id="inputImportarContactos" accept="application/json" style="display:none;">
            </label>
            <button id="btnLimpiarContactos" class="btn btn-danger ms-2">
                <i class="bi bi-trash"></i> Limpiar
            </button>
        </div>
        
        <!-- Buscador -->
        <div class="mb-3">
            <div class="input-group">
                <span class="input-group-text">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" id="buscadorContactos" class="form-control" placeholder="Buscar por nombre o número...">
                <button class="btn btn-outline-secondary" type="button" id="btnLimpiarBusqueda">
                    <i class="bi bi-x-circle"></i> Limpiar
                </button>
            </div>
        </div>
        
        <div id="contactos-toolbar" class="mb-2" style="display:none;">
            <button id="btnSeleccionarTodos" class="btn btn-sm btn-outline-success">Seleccionar todos</button>
            <button id="btnDeseleccionarTodos" class="btn btn-sm btn-outline-secondary">Deseleccionar todos</button>
        </div>
        
        <div id="contactos-lista" class="table-responsive mb-4"></div>
        
        <!-- Paginación -->
        <div id="paginacion" class="d-flex justify-content-between align-items-center" style="display:none;">
            <div class="pagination-info">
                Mostrando <span id="mostrandoDesde">0</span> - <span id="mostrandoHasta">0</span> de <span id="totalResultados">0</span> contactos
            </div>
            <nav aria-label="Navegación de páginas">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item">
                        <button class="page-link" id="btnPaginaAnterior" aria-label="Anterior">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                    </li>
                    <li class="page-item">
                        <span class="page-link" id="paginaActual">1</span>
                    </li>
                    <li class="page-item">
                        <span class="page-link">de</span>
                    </li>
                    <li class="page-item">
                        <span class="page-link" id="totalPaginas">1</span>
                    </li>
                    <li class="page-item">
                        <button class="page-link" id="btnPaginaSiguiente" aria-label="Siguiente">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div> 