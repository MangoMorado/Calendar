# Aplicación de Gestión de Agenda | Mundo Animal

Una aplicación web completa para gestionar citas y reuniones en una clínica veterinaria mediante un calendario interactivo utilizando FullCalendar, con sistema de autenticación de usuarios y gestión de actividad.

## Características

- Sistema completo de autenticación y gestión de usuarios
- Roles de usuario (administrador y usuario regular)
- Múltiples tipos de calendarios:
  - Calendario General (todas las citas)
  - Calendario Estético (citas de estética)
  - Calendario Veterinario (citas médicas)
- Vista de calendario interactivo (día, semana, mes)
- Sistema Drag & Drop para gestión de citas
- Creación, edición y eliminación de citas
- Registro de actividades y historial de cambios
- Perfil de usuario con gestión de información personal
- Interfaz responsiva y amigable
- Sistema de recuperación de contraseñas
- Interfaz multilingüe (español por defecto)
- Sistema de notificaciones para acciones del usuario
- Arquitectura modular para mejor mantenimiento del código

## Tecnologías

- PHP 7.0 o superior
- MySQL 5.6 o superior
- JavaScript (ES6+)
- [FullCalendar](https://fullcalendar.io/) v6.1.15
- Bootstrap Icons
- HTML5/CSS3

## Requisitos

- PHP 7.0 o superior
- MySQL 5.6 o superior
- Servidor web (Apache, Nginx, etc.)
- Navegador web moderno

## Instalación

1. Clone o descargue este repositorio en su directorio web (por ejemplo, htdocs en XAMPP)
2. Asegúrese de que el servidor MySQL esté en ejecución
3. Configure la conexión a la base de datos en `config/database.php` si es necesario
4. Importe la estructura de la base de datos desde `config/database.php` o ejecute el script de inicialización
5. Acceda a la aplicación a través de su navegador web (por ejemplo, http://localhost/Calendar)
6. Regístrese como usuario o utilice las credenciales predeterminadas:
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

- `assets/` - Contiene archivos CSS, JavaScript e imágenes
  - `css/` - Hojas de estilo CSS
    - `main.css` - Archivo principal que importa los módulos CSS
    - `modules/` - Módulos CSS separados por funcionalidad
  - `js/` - Scripts JavaScript
  - `img/` - Imágenes y logotipos
- `config/` - Configuración de la aplicación y base de datos
- `includes/` - Funciones comunes, utilidades y componentes reutilizables
  - `auth.php` - Funciones de autenticación y manejo de sesiones
  - `user_functions.php` - Funciones para gestión de usuarios
  - `header.php` y `footer.php` - Componentes de diseño reutilizables
  - `functions.php` - Sistema de funciones modular (appointments, calendar, ui)
  - `calendar/` - Módulos específicos del calendario
    - `init.php` - Inicialización y coordinación de componentes del calendario
    - `data.php` - Procesamiento de datos del calendario
    - `template.php` - Estructura HTML del calendario
    - `modal.php` - Modal para crear/editar citas
    - `scripts.php` - Scripts JavaScript del calendario
- `index.php` - Página principal con vista de calendario general
- `estetico.php` - Calendario específico para citas de estética
- `veterinario.php` - Calendario específico para citas veterinarias
- `login.php` - Página de inicio de sesión
- `register.php` - Página de registro de nuevos usuarios
- `profile.php` - Perfil de usuario y gestión de información personal
- `historial.php` - Historial de actividades (solo para administradores)
- `forgot_password.php` y `reset_password.php` - Sistema de recuperación de contraseñas
- `process_appointment.php` - Procesa creación, actualización y eliminación de citas
- `get_appointment.php` - Obtiene detalles de citas específicas
- `config.php` - Configuración general de la aplicación
- `logout.php` - Cierre de sesión de usuario
- `unauthorized.php` - Página de acceso no autorizado
- `api/` - Endpoints de API para operaciones AJAX
  - `appointments.php` - Gestión de citas vía API

## Arquitectura del Sistema

El sistema utiliza una arquitectura modular que facilita el mantenimiento y la extensión del código:

- **Módulos de Calendario**: Los componentes del calendario están separados en archivos independientes según su funcionalidad.
- **Separación de Responsabilidades**: Cada módulo tiene una responsabilidad específica siguiendo el principio de responsabilidad única.
- **Flujo de Datos**: El flujo de datos está claramente definido desde la obtención hasta la presentación.

## Agradecimientos

- [FullCalendar](https://fullcalendar.io/) - Biblioteca JavaScript para crear calendarios interactivos
- [Bootstrap Icons](https://icons.getbootstrap.com/) - Conjunto de iconos utilizados en la interfaz
- [Google Fonts](https://fonts.google.com/) - Fuentes utilizadas en el diseño

## Licencia

Este proyecto está licenciado bajo [MIT License]

## API REST con JWT

La aplicación ahora incluye una API RESTful mejorada que utiliza JSON Web Tokens (JWT) para la autenticación, lo que proporciona una forma más segura y estándar de autenticar solicitudes API.

### Características de la API

- Autenticación mediante tokens JWT
- Endpoints RESTful que siguen las convenciones estándar HTTP
- Respuestas JSON consistentes y bien estructuradas
- Documentación completa mediante OpenAPI
- Flexibilidad en el envío de parámetros (URL o cuerpo JSON)

### Endpoints principales

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/token.php` | Genera un token JWT para autenticación |
| GET | `/api/appointments.php` | Obtiene citas del calendario (acepta parámetros por URL o cuerpo JSON) |
| POST | `/api/appointments.php` | Crea una nueva cita |
| PUT | `/api/appointments.php` | Actualiza una cita existente |
| DELETE | `/api/appointments.php` | Elimina una cita existente |
| GET | `/api/notes.php` | Obtiene notas del usuario |
| POST | `/api/notes.php` | Crea una nueva nota |
| PUT | `/api/notes.php` | Actualiza una nota existente |
| DELETE | `/api/notes.php` | Elimina una nota existente |

### Ejemplo de uso

Para autenticarse y obtener un token JWT:

```bash
curl -X POST http://localhost/Calendar/api/token.php \
  -H "Content-Type: application/json" \
  -d '{"email":"usuario@ejemplo.com", "password":"contraseña"}'
```

Para obtener citas usando parámetros en la URL:

```bash
curl -X GET "http://localhost/Calendar/api/appointments.php?start=2025-04-01T00:00:00&end=2025-04-02T23:59:59" \
  -H "Authorization: Bearer TU_TOKEN_JWT"
```

Para obtener citas usando parámetros en el cuerpo JSON:

```bash
curl -X GET http://localhost/Calendar/api/appointments.php \
  -H "Authorization: Bearer TU_TOKEN_JWT" \
  -H "Content-Type: application/json" \
  -d '{"start":"2025-04-01T00:00:00", "end":"2025-04-02T23:59:59", "calendar_type":"veterinario"}'
```

### Documentación completa

La documentación completa de la API está disponible en formato OpenAPI en el archivo `docs/openapi.yaml`. También se proporciona una colección de Postman en `docs/postman_collection.json` para facilitar las pruebas.

Para importar la colección en Postman:
1. Abre Postman
2. Haz clic en "Importar"
3. Selecciona el archivo `docs/postman_collection.json`
4. Configura las variables de entorno según sea necesario 