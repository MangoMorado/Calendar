/**
 * Utilidades para manejar la autenticación JWT
 */

// Crear objeto auth solo si no existe ya
window.auth = window.auth || {};

/**
 * Solicita y almacena un token JWT basado en la sesión actual
 * 
 * @returns {Promise} - Promesa que se resuelve cuando el token ha sido obtenido y almacenado
 */
window.auth.storeAuthToken = window.auth.storeAuthToken || function() {
    // Verificar si ya tenemos un token
    if (localStorage.getItem('jwt_token')) {
        return Promise.resolve(true);
    }
    
    // Solicitar un nuevo token a la API
    return fetch('api/token.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        // Enviamos usuario y contraseña anónimos ya que la sesión PHP maneja la autenticación
        // El backend debería verificar la sesión PHP en lugar de estas credenciales
        body: JSON.stringify({
            email: 'session_auth',
            password: 'session_auth'
        }),
        credentials: 'include'  // Importante: incluir cookies para que PHP reconozca la sesión
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Almacenar el token JWT
            localStorage.setItem('jwt_token', data.data.token);
            console.log('Token JWT obtenido y almacenado correctamente');
            return true;
        } else {
            console.error('Error al obtener token JWT:', data.message);
            return false;
        }
    })
    .catch(error => {
        console.error('Error de conexión al obtener token JWT:', error);
        return false;
    });
};

/**
 * Verifica si el usuario tiene un token JWT almacenado
 * 
 * @returns {boolean} - true si hay un token
 */
window.auth.isAuthenticatedWithJWT = window.auth.isAuthenticatedWithJWT || function() {
    return !!localStorage.getItem('jwt_token');
};

/**
 * Elimina el token JWT (parte del proceso de logout)
 */
window.auth.clearAuthToken = window.auth.clearAuthToken || function() {
    localStorage.removeItem('jwt_token');
    console.log('Token JWT eliminado');
};

/**
 * Maneja respuestas con error 401 (No autorizado)
 * 
 * @param {Response} response - La respuesta HTTP
 * @returns {Promise} - Promesa con la respuesta procesada
 */
window.auth.handle401Error = window.auth.handle401Error || function(response) {
    // Deprecated: la lógica de reintento automático se movió a api.fetchWithAuthAndErrorHandling
    if (response && response.status === 401) {
        return { success: false, message: 'No autorizado' };
    }
    return response.json();
};

// Ejecutar automáticamente si la página está en estado "complete"
if (document.readyState === 'complete') {
    window.auth.storeAuthToken();
} else {
    // Si no está completa, esperar a que cargue
    window.addEventListener('load', window.auth.storeAuthToken);
} 