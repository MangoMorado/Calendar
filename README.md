# Aplicación de Gestión de Agenda

Una aplicación web para gestionar citas y reuniones mediante un calendario interactivo utilizando FullCalendar.

## Características

- Vista de calendario interactivo (día, semana, mes)
- Creación, edición y eliminación de citas
- Interfaz responsiva y amigable
- Arrastrar y soltar citas (próximamente)
- Interfaz multilingüe (español por defecto)

## Tecnologías

- PHP 7.0 o superior
- MySQL 5.6 o superior
- JavaScript (ES6+)
- [FullCalendar](https://fullcalendar.io/) v5.10.1
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
4. Acceda a la aplicación a través de su navegador web (por ejemplo, http://localhost/Calendar)

## Uso

- Para crear una nueva cita, haga clic en el botón "Nueva Cita" o directamente en una fecha/hora en el calendario
- Para editar una cita existente, haga clic en la cita que desea modificar
- Puede cambiar la vista del calendario (mes, semana, día o lista) utilizando los botones de la parte superior derecha
- Para navegar entre períodos, utilice los botones de navegación (anterior, siguiente) o haga clic en el botón "Hoy" para volver a la fecha actual

## Estructura de Archivos

- `assets/` - Contiene archivos CSS y JavaScript
- `config/` - Configuración de la aplicación
- `includes/` - Funciones comunes y utilidades
- `index.php` - Página principal con vista de calendario
- `get_appointment.php` - API para obtener detalles de una cita
- `process_appointment.php` - Procesa creación, actualización y eliminación de citas

## Agradecimientos

- [FullCalendar](https://fullcalendar.io/) - Biblioteca JavaScript para crear calendarios interactivos 