/**
 * Utilidades para manejar peticiones a la API
 * Incluye funcionalidad para agregar tokens JWT automáticamente
 */

// Crear objeto api solo si no existe ya
window.api = window.api || {};

/**
 * Realiza una petición fetch con autenticación JWT
 * 
 * @param {string} url - URL del endpoint a llamar
 * @param {object} options - Opciones de fetch (method, body, etc)
 * @returns {Promise} - Promesa con la respuesta del fetch
 */
window.api.fetchWithAuth = window.api.fetchWithAuth || function(url, options = {}) {
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
};

/**
 * Realiza una petición fetch con autenticación JWT y manejo de errores 401
 * 
 * @param {string} url - URL del endpoint a llamar
 * @param {object} options - Opciones de fetch (method, body, etc)
 * @returns {Promise} - Promesa con la respuesta procesada
 */
window.api.fetchWithAuthAndErrorHandling = window.api.fetchWithAuthAndErrorHandling || function(url, options = {}) {
    // Primer intento con el token actual
    return window.api.fetchWithAuth(url, options)
        .then(response => {
            if (response.status === 401) {
                // Renovar token y reintentar UNA vez automáticamente
                return window.auth.storeAuthToken()
                    .then(success => {
                        if (!success) {
                            // Sin token renovado, forzar login
                            window.auth.clearAuthToken();
                            window.location.href = '/login.php';
                            return Promise.reject(new Error('No autorizado'));
                        }
                        // Reintento con token renovado
                        return window.api.fetchWithAuth(url, options)
                            .then(r2 => {
                                if (!r2.ok) {
                                    if (r2.status === 401) {
                                        window.auth.clearAuthToken();
                                        window.location.href = '/login.php';
                                    }
                                    return r2.json();
                                }
                                return r2.json();
                            });
                    });
            }
            // Respuesta no-401: devolver JSON (éxito o error manejable por caller)
            return response.json();
        });
};

/**
 * Verifica si hay un token JWT válido almacenado
 * 
 * @returns {boolean} - true si hay un token almacenado
 */
window.api.hasValidToken = window.api.hasValidToken || function() {
    return !!localStorage.getItem('jwt_token');
};

/**
 * Elimina el token JWT almacenado (útil para logout)
 */
window.api.clearAuthToken = window.api.clearAuthToken || function() {
    localStorage.removeItem('jwt_token');
};

// También exportar funciones individuales para facilitar el uso (sólo si no existen ya)
window.fetchWithAuth = window.fetchWithAuth || window.api.fetchWithAuth;
window.fetchWithAuthAndErrorHandling = window.fetchWithAuthAndErrorHandling || window.api.fetchWithAuthAndErrorHandling; 