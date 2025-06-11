# Aplicación de Gestión de Agenda | Mundo Animal

Una aplicación web completa para gestionar citas y reuniones en una clínica veterinaria mediante un calendario interactivo utilizando FullCalendar, con sistema de autenticación de usuarios, gestión de actividad y potentes integraciones.

## Novedades recientes

- **Integración Evolution API**: Envía difusiones y conecta WhatsApp vía QR desde el sistema.
- **Gestión avanzada de sesiones**: Control de sesiones simultáneas, recordar equipo, limpieza automática.
- **Mejoras de seguridad**: Hash seguro, prepared statements, JWT, control de acceso y recuperación de contraseñas.
- **API RESTful robusta**: Endpoints para citas, notas y autenticación JWT.
- **Arquitectura modular y migración a MVC**: Mejor mantenibilidad y escalabilidad.
- **Mejoras visuales**: Interfaz moderna, responsiva y consistente en todos los módulos.
- **Soporte para múltiples calendarios**: General, Estético, Veterinario y por usuario.
- **Sistema de notificaciones y feedback visual**: Acciones del usuario y estados de integración.

## Características

- Sistema completo de autenticación y gestión de usuarios (roles: admin y usuario)
- Múltiples tipos de calendarios: General, Estético, Veterinario, por usuario
- Vista de calendario interactivo (día, semana, mes) con Drag & Drop
- Creación, edición y eliminación de citas
- Registro de actividades e historial de cambios
- Perfil de usuario y gestión de información personal
- Interfaz responsiva y amigable
- Recuperación de contraseñas y validación robusta
- Interfaz multilingüe (español por defecto)
- Sistema de notificaciones para acciones del usuario
- Integración con Evolution API (WhatsApp) y N8N (automatizaciones)
- Arquitectura modular y migración progresiva a MVC

## Tecnologías

- PHP 7.0 o superior
- MySQL 5.6 o superior
- JavaScript (ES6+)
- [FullCalendar](https://fullcalendar.io/) v6.1.17
- Bootstrap Icons
- HTML5/CSS3

## Seguridad

- Contraseñas con hash seguro
- Protección contra inyección SQL y XSS
- Validación de formularios en cliente y servidor
- Control de sesiones y roles
- Recuperación de contraseñas con tokens de un solo uso
- JWT para autenticación de la API

## Instalación y uso

1. Clona o descarga el repositorio en tu directorio web (ejemplo: htdocs en XAMPP)
2. Configura la base de datos en `config/database.php`
3. Importa la estructura de la base de datos o ejecuta el script de inicialización
4. Accede a la aplicación en tu navegador (ejemplo: http://localhost/Calendar)
5. Regístrate o usa las credenciales predeterminadas:
   - Administrador: admin@example.com / admin123
   - Usuario: user@example.com / user123

## Uso

- Inicie sesión con sus credenciales para acceder al sistema
- Seleccione el tipo de calendario que desea utilizar (General, Estético o Veterinario)
- Para crear una nueva cita, haga clic en el botón "Nueva Cita" o directamente en una fecha/hora en el calendario
- Para editar una cita existente, haga clic en la cita que desea modificar
- Puede mover citas directamente usando el sistema Drag & Drop
- Cambie la vista del calendario usando los botones de la parte superior
- Acceda a su perfil para modificar su información personal o cambiar su contraseña
- Los administradores pueden acceder al historial completo de actividades del sistema

## Configuración del Calendario

El sistema permite configurar:
- Hora de inicio y fin del calendario
- Duración de los slots de tiempo
- Formato de hora (12 horas / 24 horas)

## Seguridad

- Contraseñas almacenadas con hash seguro
- Protección contra inyección SQL mediante prepared statements
- Validación de formularios en el cliente y servidor
- Sesiones seguras y control de acceso basado en roles
- Sistema de recuperación de contraseñas con tokens de un solo uso

## Estructura de Archivos

- `assets/` - CSS, JS, imágenes
- `config/` - Configuración y scripts de base de datos
- `includes/` - Funciones, utilidades y módulos de calendario
- `api/` - Endpoints RESTful (citas, notas, autenticación)
- `docs/` - Documentación, OpenAPI, colección Postman
- `views/` - Vistas adicionales

## API REST con JWT

- Autenticación y endpoints RESTful para citas y notas
- Documentación OpenAPI (`docs/openapi.yaml`)
- Colección Postman (`docs/postman_collection.json`)

## Roadmap y mejoras en curso

- Integración Google Calendar y Vetesoft
- Dashboard con estadísticas
- Sistema de notificaciones por WhatsApp/email
- Optimización de consultas y carga lazy
- Consolidación de manejo de citas en la API REST
- Migración completa a MVC y pruebas automatizadas
- Mejoras para PWA y personalización visual

## Licencia

Este proyecto está licenciado bajo [MIT License]

---

> Para detalles técnicos, endpoints y ejemplos de uso, consulta la documentación en la carpeta `docs/` y el archivo `CHANGENOTES.md`. 