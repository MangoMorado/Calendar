/**
 * Utilidades para manejar peticiones a la API
 * Incluye funcionalidad para agregar tokens JWT automáticamente
 */

/**
 * Realiza una petición fetch con autenticación JWT
 * 
 * @param {string} url - URL del endpoint a llamar
 * @param {object} options - Opciones de fetch (method, body, etc)
 * @returns {Promise} - Promesa con la respuesta del fetch
 */
function fetchWithAuth(url, options = {}) {
    // Obtener el token JWT del almacenamiento local
    const token = localStorage.getItem('jwt_token');
    
    // Si no hay token, podríamos redireccionar al login o continuar sin token
    if (!token) {
        console.warn('No se encontró token JWT. La petición podría ser rechazada.');
        // Opcionalmente redirigir a login: window.location.href = '/login.php';
    }
    
    // Preparar los headers
    const headers = options.headers || {};
    
    // Agregar el token si existe
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }
    
    // Agregar Content-Type por defecto si no está definido y hay body
    if (options.body && !headers['Content-Type']) {
        headers['Content-Type'] = 'application/json';
    }
    
    // Retornar fetch con la configuración actualizada
    return fetch(url, {
        ...options,
        headers
    });
}

/**
 * Realiza una petición fetch con autenticación JWT y manejo de errores 401
 * 
 * @param {string} url - URL del endpoint a llamar
 * @param {object} options - Opciones de fetch (method, body, etc)
 * @returns {Promise} - Promesa con la respuesta procesada
 */
function fetchWithAuthAndErrorHandling(url, options = {}) {
    return fetchWithAuth(url, options)
        .then(response => {
            if (!response.ok && response.status === 401) {
                // Si hay un error 401, utilizamos el manejador especial
                return handle401Error(response);
            }
            return response.json();
        });
}

/**
 * Verifica si hay un token JWT válido almacenado
 * 
 * @returns {boolean} - true si hay un token almacenado
 */
function hasValidToken() {
    return !!localStorage.getItem('jwt_token');
}

/**
 * Elimina el token JWT almacenado (útil para logout)
 */
function clearAuthToken() {
    localStorage.removeItem('jwt_token');
} 