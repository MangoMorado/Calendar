/**
 * Módulo de Interfaz de Usuario
 * Maneja los elementos visuales como modales y notificaciones
 */

/**
 * Abre el modal
 */
export function openModal(modal) {
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    document.getElementById('title').focus();
}

/**
 * Cierra el modal
 */
export function closeModal(modal) {
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

/**
 * Muestra una notificación
 */
export function showNotification(message, type) {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    
    // Icono según el tipo de notificación
    let icon = '';
    switch(type) {
        case 'success':
            icon = 'check-circle';
            break;
        case 'error':
            icon = 'exclamation-circle';
            break;
        case 'info':
            icon = 'info-circle';
            break;
        default:
            icon = 'bell';
    }
    
    notification.innerHTML = `
        <div class="notification-icon">
            <i class="bi bi-${icon}"></i>
        </div>
        <div class="notification-content">
            <p>${message}</p>
        </div>
        <button class="notification-close"><i class="bi bi-x"></i></button>
    `;
    
    // Añadir a la página
    document.body.appendChild(notification);
    
    // Mostrar con animación
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Cerrar al hacer clic
    notification.querySelector('.notification-close').addEventListener('click', function() {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    });
    
    // Auto-cerrar después de 5 segundos para éxito e info, 10 segundos para error
    const timeout = type === 'error' ? 10000 : 3000;
    setTimeout(() => {
        if (document.body.contains(notification)) {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }
    }, timeout);
} 