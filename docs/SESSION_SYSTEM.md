# Sistema de Sesiones Avanzado - Mundo Animal

## Descripción General

El sistema de sesiones avanzado permite un control granular sobre las sesiones de usuario, incluyendo identificación de equipos, tiempos de expiración configurables, y la opción de "recordar equipo" para una experiencia de usuario mejorada.

## Características Principales

### 🔐 Autenticación Mejorada
- **Sesiones basadas en cookies seguras** con tokens únicos
- **Identificación automática de equipos** (Windows, Mac, Linux, Móvil)
- **Detección de IP** para mayor seguridad
- **Sesiones múltiples** por usuario con límites configurables

### ⏰ Tiempos de Sesión Flexibles
- **Sesión normal**: 5 minutos a 24 horas (configurable)
- **"Recordar equipo"**: 1 hora a 1 mes (configurable)
- **Expiración automática** de sesiones inactivas
- **Limpieza automática** de sesiones expiradas

### 🛡️ Seguridad Avanzada
- **Límite de sesiones simultáneas** por usuario (1-20)
- **Detección de cambios de IP** (opcional)
- **Sesiones únicas** con tokens criptográficamente seguros
- **Cookies seguras** con flags HttpOnly y Secure

### 📊 Gestión de Sesiones
- **Panel de sesiones activas** para usuarios
- **Cerrar sesiones específicas** o todas a la vez
- **Estadísticas de sesiones** para administradores
- **Configuración centralizada** de parámetros

## Estructura de Base de Datos

### Tabla `user_sessions`
```sql
CREATE TABLE user_sessions (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    session_id VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    device_info VARCHAR(255),
    remember_me TINYINT(1) NOT NULL DEFAULT 0,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Tabla `session_settings`
```sql
CREATE TABLE session_settings (
    setting_key VARCHAR(255) NOT NULL PRIMARY KEY,
    setting_value TEXT NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Configuración por Defecto

| Configuración | Valor | Descripción |
|---------------|-------|-------------|
| `session_timeout` | 3600 | Tiempo de sesión normal (1 hora) |
| `remember_me_timeout` | 604800 | Tiempo de "recordar equipo" (7 días) |
| `max_sessions_per_user` | 5 | Máximo de sesiones simultáneas |
| `require_login_on_visit` | 1 | Requerir login en cada visita |
| `session_cleanup_interval` | 86400 | Intervalo de limpieza (24 horas) |

## Archivos del Sistema

### Archivos Principales
- `includes/session_manager.php` - Clase principal del gestor de sesiones
- `includes/auth.php` - Sistema de autenticación actualizado
- `login.php` - Formulario de login con opción "recordar equipo"
- `sessions.php` - Panel de gestión de sesiones para usuarios
- `session_settings.php` - Configuración de sesiones para administradores

### Scripts de Mantenimiento
- `cron/cleanup_sessions.php` - Script de limpieza automática

## Uso del Sistema

### Para Usuarios

#### Iniciar Sesión con "Recordar Equipo"
1. Ir a `login.php`
2. Ingresar credenciales
3. Marcar checkbox "Recordar este equipo"
4. Hacer clic en "Iniciar Sesión"

#### Gestionar Sesiones Activas
1. Ir a "Mis Sesiones" en el menú de usuario
2. Ver todas las sesiones activas
3. Cerrar sesiones específicas o todas a la vez

### Para Administradores

#### Configurar Parámetros de Sesión
1. Ir a "Config. Sesiones" en el menú de administrador
2. Ajustar tiempos de sesión
3. Configurar límites de sesiones
4. Activar/desactivar "requerir login en cada visita"
5. Guardar configuración

#### Monitorear Estadísticas
- Ver estadísticas de sesiones en tiempo real
- Monitorear sesiones activas y expiradas
- Revisar sesiones con "recordar equipo" activado

## Configuración del Cron Job

Para la limpieza automática de sesiones, configurar un cron job:

```bash
# Limpiar sesiones cada hora
0 * * * * /usr/bin/php /ruta/a/tu/proyecto/cron/cleanup_sessions.php

# Limpiar sesiones cada día a las 2 AM
0 2 * * * /usr/bin/php /ruta/a/tu/proyecto/cron/cleanup_sessions.php
```

## API de Sesiones

### Funciones Disponibles

#### `SessionManager`
- `createSession($userId, $rememberMe)` - Crear nueva sesión
- `validateSession($sessionId)` - Validar sesión existente
- `destroySession($sessionId)` - Cerrar sesión específica
- `getUserSessions($userId)` - Obtener sesiones del usuario
- `clearAllUserSessions($userId)` - Cerrar todas las sesiones del usuario
- `cleanupExpiredSessions($userId)` - Limpiar sesiones expiradas

#### Funciones de Autenticación
- `isAuthenticated()` - Verificar si el usuario está autenticado
- `authenticateUser($user, $rememberMe)` - Iniciar sesión
- `logoutUser()` - Cerrar sesión
- `getUserActiveSessions()` - Obtener sesiones activas del usuario actual
- `logoutAllSessions()` - Cerrar todas las sesiones del usuario actual

## Seguridad

### Medidas Implementadas
- **Tokens únicos**: Cada sesión tiene un token criptográficamente seguro
- **Cookies seguras**: Configuradas con flags HttpOnly y Secure
- **Detección de IP**: Registro y monitoreo de cambios de IP
- **Límites de sesiones**: Prevención de sesiones múltiples excesivas
- **Expiración automática**: Sesiones que expiran automáticamente
- **Limpieza automática**: Eliminación de sesiones expiradas

### Recomendaciones
1. **Configurar HTTPS** para cookies seguras
2. **Ajustar tiempos de sesión** según necesidades de seguridad
3. **Monitorear sesiones** regularmente
4. **Configurar cron job** para limpieza automática
5. **Revisar logs** de cambios de IP sospechosos

## Migración desde el Sistema Anterior

El nuevo sistema es compatible con el sistema de sesiones anterior. Los usuarios existentes mantendrán su funcionalidad, pero se beneficiarán de las nuevas características:

1. **Sesiones existentes**: Se mantienen activas
2. **Configuración**: Se aplican valores por defecto
3. **Funcionalidad**: Todas las funciones anteriores siguen funcionando

## Solución de Problemas

### Sesiones que no se mantienen
- Verificar configuración de cookies
- Revisar configuración de HTTPS
- Comprobar zona horaria del servidor

### Limpieza automática no funciona
- Verificar configuración del cron job
- Revisar permisos del script de limpieza
- Comprobar logs de errores

### Problemas de rendimiento
- Ajustar intervalo de limpieza
- Revisar índices de base de datos
- Optimizar consultas de sesiones

## Personalización

### Modificar Tiempos de Sesión
```php
// En session_settings.php o mediante la interfaz web
$sessionManager->updateSetting('session_timeout', 7200); // 2 horas
$sessionManager->updateSetting('remember_me_timeout', 1209600); // 2 semanas
```

### Agregar Nuevas Configuraciones
```php
// Agregar nueva configuración
$sessionManager->updateSetting('new_setting', 'value');

// Usar nueva configuración
$value = $sessionManager->getSetting('new_setting', 'default');
```

### Personalizar Detección de Dispositivos
```php
// En SessionManager::getDeviceInfo()
// Agregar nuevos patrones de detección
if (preg_match('/TuPatron/i', $userAgent)) {
    $deviceInfo = 'Tu Dispositivo';
}
```

## Soporte

Para soporte técnico o preguntas sobre el sistema de sesiones:

1. Revisar logs de errores
2. Verificar configuración de base de datos
3. Comprobar permisos de archivos
4. Consultar documentación de PHP sobre sesiones

---

**Desarrollado por 🥭 Mango Morado para Mundo Animal** 