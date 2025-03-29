document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    const dropdownToggle = document.querySelector('.dropdown-toggle');
    const dropdownMenu = document.querySelector('.dropdown-menu');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    const overlay = document.querySelector('.mobile-menu-overlay');
    
    // Función para mostrar/ocultar el menú móvil
    function toggleMobileMenu(event) {
        event.stopPropagation();
        const isOpen = mobileMenu.classList.contains('show');
        
        if (isOpen) {
            closeMobileMenu();
        } else {
            openMobileMenu();
        }
    }
    
    function openMobileMenu() {
        mobileMenu.classList.add('show');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
        
        // Agregar eventos para cerrar
        document.addEventListener('click', handleOutsideClick);
        document.addEventListener('keydown', handleEscKey);
    }
    
    function closeMobileMenu() {
        mobileMenu.classList.remove('show');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
        
        // Remover eventos
        document.removeEventListener('click', handleOutsideClick);
        document.removeEventListener('keydown', handleEscKey);
    }
    
    function handleOutsideClick(event) {
        if (!mobileMenu.contains(event.target) && !mobileMenuBtn.contains(event.target)) {
            closeMobileMenu();
        }
    }
    
    function handleEscKey(event) {
        if (event.key === 'Escape') {
            closeMobileMenu();
        }
    }
    
    // Manejar clic en el botón de menú móvil
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', toggleMobileMenu);
    }
    
    // Manejar clics en los items del menú móvil
    const mobileNavItems = document.querySelectorAll('.mobile-nav-item');
    mobileNavItems.forEach(item => {
        item.addEventListener('click', closeMobileMenu);
    });
    
    // Alternar menú desplegable con clic
    if (dropdownToggle && dropdownMenu) {
        dropdownToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
        });
        
        // Cerrar menú al hacer clic fuera de él
        document.addEventListener('click', function(e) {
            if (!dropdownMenu.contains(e.target) && !dropdownToggle.contains(e.target)) {
                dropdownMenu.classList.remove('show');
            }
        });
    }
    
    // Agregar clase activa al elemento de navegación actual
    const currentPath = window.location.pathname;
    const navItems = document.querySelectorAll('.nav-item, .dropdown-item, .mobile-nav-item');
    
    navItems.forEach(item => {
        const href = item.getAttribute('href');
        if (href && currentPath.endsWith(href)) {
            item.classList.add('active');
        }
    });
}); 