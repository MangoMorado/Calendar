# Prompt para Recrear Sistema de Gestión de Citas o Agendas llamada Mango

## Descripción General
Crear un sistema web de gestión de citas que permita manejar diferentes calendarios. El sistema debe ser intuitivo, moderno, seguro y fácil de mantener.

## Requisitos Técnicos

### Tecnologías Base
- Laravel 12
- PHP 7.0 o superior
- MySQL 5.6 o superior
- JavaScript (ES6+)
- FullCalendar v6.1.17
- Bootstrap Icons
- HTML5/CSS3

### Estructura de Directorios
```
├── api/                 # Endpoints de API REST
├── assets/             # Recursos estáticos
│   ├── css/           # Hojas de estilo
│   ├── js/            # Scripts JavaScript
│   └── img/           # Imágenes y recursos gráficos
├── config/             # Configuración de la aplicación
├── controllers/        # Controladores de la aplicación
├── docs/              # Documentación
├── includes/          # Funciones y componentes comunes
├── models/            # Modelos de datos
└── views/             # Vistas de la aplicación
```

## Funcionalidades Principales

### 1. Sistema de Personalización del sistema
- Subir logo personalizado
- El logo debe usarse en el login, y en el index

### 2. Sistema de Autenticación
- Registro de usuarios con validación de email
- Inicio de sesión seguro
- Recuperación de contraseñas
- Roles de usuario (administrador y usuario regular)
- Protección de rutas basada en roles

### 3. Gestión de Citas
- CRUD Calendarios
  - Debe permitir crear, editar, actualizar o eliminar calendarios
  - Debe mostrarme un calendario general que agrupe todos los calendarios creados 
- Funcionalidades del calendario:
  - Vista diaria, semanal y mensual
  - Drag & Drop para mover citas
  - Creación rápida de citas
  - Edición y eliminación de citas
  - Filtros por tipo de servicio
  - Búsqueda de citas
  - modal de información de cita al precionar sobre cita
  - estado de la cita segun codigo de color (rojo: cancelada, amarillo: reprogramada, verde: agendada, morado: agendada por Mango)

### 4. Configuración del Sistema
- Panel de administración para:
  - Configurar horarios de trabajo
  - Establecer duración de slots (15, 30, 60 minutos)
  - Seleccionar días hábiles
  - Configurar formato de hora (12h/24h)
  - Gestionar tipos de servicios
  - Administrar usuarios

### 4. API REST
- Autenticación mediante JWT
- Endpoints para:
  - Gestión de citas (CRUD)
  - Gestión de usuarios
  - Configuración del sistema
- Documentación OpenAPI
- Validación de datos
- Manejo de errores estandarizado

### 5. Interfaz de Usuario
- Diseño responsivo
- Tema claro/oscuro
- Navegación intuitiva
- Notificaciones en tiempo real
- Modales para acciones rápidas
- Formularios validados
- Mensajes de feedback

## Base de Datos

### Tablas Principales
1. `users`
   - id (PK)
   - email
   - password (hashed)
   - role
   - created_at
   - updated_at

2. `appointments`
   - id (PK)
   - user_id (FK)
   - type (veterinario/estetico)
   - start_time
   - end_time
   - status
   - notes
   - created_at
   - updated_at

3. `settings`
   - id (PK)
   - setting_key
   - setting_value
   - created_at
   - updated_at

4. `activity_log`
   - id (PK)
   - user_id (FK)
   - action
   - details
   - created_at

5. `calendars`
   - id (PK)
   - name
   - description
   - color
   - is_active
   - created_by (FK -> users.id)
   - created_at
   - updated_at

6. `calendar_appointments`
   - id (PK)
   - calendar_id (FK -> calendars.id)
   - appointment_id (FK -> appointments.id)
   - created_at
   - updated_at

7. `branding`
   - id (PK)
   - logo_path
   - primary_color
   - secondary_color
   - font_family
   - created_at
   - updated_at

8. `user_preferences`
   - id (PK)
   - user_id (FK -> users.id)
   - theme (light/dark)
   - language
   - timezone
   - date_format
   - time_format (12h/24h)
   - notification_preferences (JSON)
   - created_at
   - updated_at

9. `calendar_settings`
   - id (PK)
   - calendar_id (FK -> calendars.id)
   - working_hours (JSON)
   - slot_duration
   - buffer_time
   - max_appointments_per_day
   - created_at
   - updated_at

10. `calendar_services`
    - id (PK)
    - calendar_id (FK -> calendars.id)
    - name
    - duration
    - price
    - description
    - is_active
    - created_at
    - updated_at

### Relaciones y Consideraciones

1. **Calendarios**
   - Un usuario puede crear múltiples calendarios
   - Cada calendario puede tener múltiples citas
   - Las citas pueden pertenecer a múltiples calendarios

2. **Personalización**
   - La tabla `branding` permite personalización global del sistema
   - `user_preferences` permite personalización por usuario
   - Soporte para temas claro/oscuro
   - Configuración de idioma y zona horaria

3. **Configuraciones de Calendario**
   - Horarios de trabajo personalizados
   - Duración de slots configurable
   - Tiempo de buffer entre citas
   - Límite de citas por día
   - Servicios específicos por calendario

### Campos JSON Detallados

1. `user_preferences.notification_preferences`:
```json
{
    "email": {
        "appointment_reminder": true,
        "appointment_confirmation": true,
        "appointment_cancellation": true
    },
    "push": {
        "appointment_reminder": true,
        "appointment_confirmation": true,
        "appointment_cancellation": true
    }
}
```

2. `calendar_settings.working_hours`:
```json
{
    "monday": {
        "start": "09:00",
        "end": "17:00",
        "is_working_day": true
    },
    "tuesday": {
        "start": "09:00",
        "end": "17:00",
        "is_working_day": true
    }
    // ... resto de días
}
```

## Seguridad

### Implementaciones Requeridas
1. Autenticación
   - Hash de contraseñas con bcrypt
   - Tokens JWT para API
   - Sesiones seguras
   - Protección contra CSRF

2. Validación de Datos
   - Sanitización de entrada
   - Validación en cliente y servidor
   - Prepared statements para SQL

3. Control de Acceso
   - Middleware de autenticación
   - Verificación de roles
   - Registro de actividades

## Características Adicionales

### 1. Notificaciones
- Sistema de notificaciones en tiempo real
- Recordatorios de citas
- Notificaciones de cambios

### 2. Reportes
- Generación de reportes de citas
- Estadísticas de uso
- Exportación a PDF/Excel

### 3. Personalización
- Temas personalizables
- Configuración de idioma
- Preferencias de usuario

## Instrucciones de Implementación

1. Configuración Inicial
   ```bash
   # Clonar repositorio
   git clone [url-del-repositorio]
   
   # Instalar dependencias
   composer install
   
   # Configurar base de datos
   cp config/database.example.php config/database.php
   # Editar config/database.php con credenciales
   
   # Importar estructura de base de datos
   mysql -u [usuario] -p [base_de_datos] < config/database.sql
   ```

2. Configuración del Servidor
   - Configurar virtual host en Apache/Nginx
   - Asegurar permisos de directorios
   - Configurar SSL para HTTPS

3. Desarrollo
   - Seguir estándares PSR-4 para autoloading
   - Implementar patrones MVC
   - Usar prepared statements para queries
   - Implementar manejo de errores consistente

4. Pruebas
   - Pruebas unitarias para funciones críticas
   - Pruebas de integración para API
   - Pruebas de interfaz de usuario

## Consideraciones de Mantenimiento

1. Código
   - Documentación inline
   - Comentarios explicativos
   - Nombres descriptivos
   - Estructura modular

2. Base de Datos
   - Índices optimizados
   - Backups regulares
   - Migraciones versionadas

3. Seguridad
   - Actualizaciones regulares
   - Monitoreo de logs
   - Auditorías de seguridad

## Recursos Adicionales

1. Documentación
   - Manual de usuario
   - Documentación técnica
   - Guía de API
   - Guía de contribución

2. Herramientas
   - Postman collection para API
   - Scripts de mantenimiento
   - Herramientas de monitoreo

## Notas Finales
- Mantener el código limpio y bien documentado
- Seguir las mejores prácticas de seguridad
- Implementar logging para debugging
- Mantener backups regulares
- Documentar cambios importantes 