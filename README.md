# Aplicación de Gestión de Agenda | Mundo Animal

Una aplicación web completa para gestionar citas y reuniones en una clínica veterinaria mediante un calendario interactivo utilizando FullCalendar, con sistema de autenticación de usuarios y gestión de actividad.

## Características

- Sistema completo de autenticación y gestión de usuarios
- Roles de usuario (administrador y usuario regular)
- Vista de calendario interactivo (día, semana, mes)
- Creación, edición y eliminación de citas
- Registro de actividades y historial de cambios
- Perfil de usuario con gestión de información personal
- Interfaz responsiva y amigable
- Sistema de recuperación de contraseñas
- Interfaz multilingüe (español por defecto)

## Tecnologías

- PHP 7.0 o superior
- MySQL 5.6 o superior
- JavaScript (ES6+)
- [FullCalendar](https://fullcalendar.io/) v5.10.1
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
- Para crear una nueva cita, haga clic en el botón "Nueva Cita" o directamente en una fecha/hora en el calendario
- Para editar una cita existente, haga clic en la cita que desea modificar
- Puede cambiar la vista del calendario usando los botones de la parte superior
- Acceda a su perfil para modificar su información personal o cambiar su contraseña
- Los administradores pueden acceder al historial completo de actividades del sistema

## Seguridad

- Contraseñas almacenadas con hash seguro
- Protección contra inyección SQL mediante prepared statements
- Validación de formularios en el cliente y servidor
- Sesiones seguras y control de acceso basado en roles
- Sistema de recuperación de contraseñas con tokens de un solo uso

## Estructura de Archivos

- `assets/` - Contiene archivos CSS, JavaScript e imágenes
  - `css/` - Hojas de estilo CSS
  - `js/` - Scripts JavaScript
  - `img/` - Imágenes y logotipos
- `config/` - Configuración de la aplicación y base de datos
- `includes/` - Funciones comunes, utilidades y componentes reutilizables
  - `auth.php` - Funciones de autenticación y manejo de sesiones
  - `user_functions.php` - Funciones para gestión de usuarios
  - `header.php` y `footer.php` - Componentes de diseño reutilizables
- `index.php` - Página principal con vista de calendario
- `login.php` - Página de inicio de sesión
- `register.php` - Página de registro de nuevos usuarios
- `profile.php` - Perfil de usuario y gestión de información personal
- `historial.php` - Historial de actividades (solo para administradores)
- `forgot_password.php` y `reset_password.php` - Sistema de recuperación de contraseñas
- `process_appointment.php` - Procesa creación, actualización y eliminación de citas

## Agradecimientos

- [FullCalendar](https://fullcalendar.io/) - Biblioteca JavaScript para crear calendarios interactivos
- [Bootstrap Icons](https://icons.getbootstrap.com/) - Conjunto de iconos utilizados en la interfaz
- [Google Fonts](https://fonts.google.com/) - Fuentes utilizadas en el diseño

## Licencia

Este proyecto está licenciado bajo [MIT License] 