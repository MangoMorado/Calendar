document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    const dropdownToggle = document.querySelector('.dropdown-toggle');
    const dropdownMenu = document.querySelector('.dropdown-menu');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    const overlay = document.querySelector('.mobile-menu-overlay');
    
    // Detectar si es iOS
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    
    // Función para mostrar/ocultar el menú móvil
    function toggleMobileMenu(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        
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
        document.body.classList.add('menu-open');
        
        // Agregar eventos para cerrar
        document.addEventListener('click', handleOutsideClick);
        document.addEventListener('touchend', handleOutsideClick);
        document.addEventListener('keydown', handleEscKey);
    }
    
    function closeMobileMenu() {
        mobileMenu.classList.remove('show');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
        document.body.classList.remove('menu-open');
        
        // Remover eventos
        document.removeEventListener('click', handleOutsideClick);
        document.removeEventListener('touchend', handleOutsideClick);
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
    
    // Escuchar evento personalizado para cerrar el menú (para iOS)
    document.addEventListener('closeMenu', closeMobileMenu);
    
    // Manejar clic en el botón de menú móvil
    if (mobileMenuBtn) {
        // Eliminar el retraso táctil en iOS
        if (isIOS) {
            mobileMenuBtn.style.touchAction = 'manipulation';
        }
        
        mobileMenuBtn.addEventListener('click', toggleMobileMenu);
        
        // Añadir eventos táctiles para iOS
        mobileMenuBtn.addEventListener('touchstart', function(e) {
            if (isIOS) {
                e.preventDefault(); // Prevenir comportamiento por defecto en iOS
            }
        }, { passive: false });
        
        mobileMenuBtn.addEventListener('touchend', function(e) {
            if (isIOS) {
                e.preventDefault();
                toggleMobileMenu(e);
            }
        }, { passive: false });
    }
    
    // Manejar clics en los items del menú móvil
    const mobileNavItems = document.querySelectorAll('.mobile-nav-item');
    mobileNavItems.forEach(item => {
        item.addEventListener('click', closeMobileMenu);
        
        // Mejorar respuesta táctil en iOS
        if (isIOS) {
            item.style.touchAction = 'manipulation';
            item.addEventListener('touchend', closeMobileMenu, { passive: false });
        }
    });
    
    // Alternar menú desplegable con clic
    if (dropdownToggle && dropdownMenu) {
        dropdownToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
        });
        
        // Mejorar soporte táctil para iOS
        if (isIOS) {
            dropdownToggle.style.touchAction = 'manipulation';
            dropdownToggle.addEventListener('touchend', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropdownMenu.classList.toggle('show');
            }, { passive: false });
        }
        
        // Cerrar menú al hacer clic fuera de él
        document.addEventListener('click', function(e) {
            if (!dropdownMenu.contains(e.target) && !dropdownToggle.contains(e.target)) {
                dropdownMenu.classList.remove('show');
            }
        });
        
        // Añadir soporte para evento táctil
        document.addEventListener('touchend', function(e) {
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