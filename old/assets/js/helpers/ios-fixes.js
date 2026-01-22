/**
 * Utilidades para mejorar la experiencia en dispositivos iOS
 */

(function() {
    // Detectar si es iOS
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    
    if (!isIOS) return; // Solo aplicar en dispositivos iOS
    
    /**
     * Elimina el retraso de 300ms en eventos táctiles en dispositivos iOS
     * más viejos que iOS 9.3 (versiones más recientes no necesitan esto)
     */
    document.addEventListener('DOMContentLoaded', function() {
        // Añadir touch-action a todos los elementos interactivos
        const interactiveElements = document.querySelectorAll('button, a, [role="button"], input[type="button"], input[type="submit"]');
        
        interactiveElements.forEach(function(el) {
            el.style.touchAction = 'manipulation';
            el.style.webkitTapHighlightColor = 'transparent';
        });
        
        // Fix para el menú hamburguesa específicamente
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        if (mobileMenuBtn) {
            // Deshabilitar el delay de 300ms
            mobileMenuBtn.addEventListener('touchstart', function(e) {
                e.preventDefault();
            }, { passive: false });
            
            // Prevenir doble toque
            let lastTap = 0;
            mobileMenuBtn.addEventListener('touchend', function(e) {
                const currentTime = new Date().getTime();
                const tapLength = currentTime - lastTap;
                
                if (tapLength < 300 && tapLength > 0) {
                    e.preventDefault();
                    return false;
                }
                
                lastTap = currentTime;
                return true;
            }, { passive: false });
        }
        
        // Mejoras para el scroll del menú móvil
        const mobileMenu = document.getElementById('mobileMenu');
        if (mobileMenu) {
            mobileMenu.style.webkitOverflowScrolling = 'touch';
        }
        
        // Fix para el body scroll en iOS
        document.addEventListener('touchmove', function(e) {
            if (document.body.classList.contains('menu-open')) {
                e.preventDefault();
            }
        }, { passive: false });
        
        // Fix para eventos en el menú
        const overlay = document.querySelector('.mobile-menu-overlay');
        if (overlay) {
            overlay.addEventListener('touchstart', function(e) {
                if (mobileMenu.classList.contains('show')) {
                    e.preventDefault();
                    
                    // Simular el cierre del menú
                    const event = new CustomEvent('closeMenu');
                    document.dispatchEvent(event);
                }
            }, { passive: false });
        }
        
        // Escuchar el evento personalizado para cerrar menú
        document.addEventListener('closeMenu', function() {
            if (mobileMenu && mobileMenu.classList.contains('show')) {
                mobileMenu.classList.remove('show');
                if (overlay) overlay.classList.remove('show');
                document.body.style.overflow = '';
                document.body.classList.remove('menu-open');
            }
        });
    });
})(); 