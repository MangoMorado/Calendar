document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    const filterForm = document.querySelector('.filters').closest('form');
    const tipoFilter = document.getElementById('tipo');
    const usuarioFilter = document.getElementById('usuario');
    const filterButton = document.querySelector('.filter-button');
    const historyItems = document.querySelectorAll('.history-item');
    
    // Función para destacar elementos de historial al pasar el mouse
    historyItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(5px)';
            this.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '0 1px 3px rgba(0,0,0,0.05)';
        });
    });
    
    // Manejar cambios en los filtros
    if (tipoFilter) {
        tipoFilter.addEventListener('change', function() {
            if (this.value !== '' && usuarioFilter.value !== '0') {
                filterButton.textContent = 'Filtrar por tipo y usuario';
            } else if (this.value !== '') {
                filterButton.textContent = 'Filtrar por tipo';
            } else if (usuarioFilter.value !== '0') {
                filterButton.textContent = 'Filtrar por usuario';
            } else {
                filterButton.textContent = 'Mostrar todos';
            }
        });
    }
    
    if (usuarioFilter) {
        usuarioFilter.addEventListener('change', function() {
            if (this.value !== '0' && tipoFilter.value !== '') {
                filterButton.textContent = 'Filtrar por tipo y usuario';
            } else if (this.value !== '0') {
                filterButton.textContent = 'Filtrar por usuario';
            } else if (tipoFilter.value !== '') {
                filterButton.textContent = 'Filtrar por tipo';
            } else {
                filterButton.textContent = 'Mostrar todos';
            }
        });
    }
    
    // Mostrar texto de carga durante envío del formulario
    if (filterForm) {
        filterForm.addEventListener('submit', function() {
            filterButton.innerHTML = '<i class="bi bi-hourglass-split"></i> Cargando...';
            filterButton.disabled = true;
        });
    }
}); 