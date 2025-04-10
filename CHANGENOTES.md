# CHANGENOTES - Calendario MundoAnimal

## Lista de Cambios por implementar:
- Estado de las citas según código de color (Verde, Amarillo: reprogramar, Rojo)
- Sistema de cola de citas, que el sistema reciba citas del chatbot y que un humano las programe
- Mejoras de seguridad
- Bloquear agendas (impedir agendar bajo condiciones personalizables)
- Agregar sistema CRUD de Calendarios
- Agregar sistema de notificación por WhatsApp / Correo electrónico
- Mejoras de velocidad de carga
- Optimización de Consultas: Para mejor rendimiento con grandes volúmenes de datos
- Mejora de Responsividad: Para mejorar la experiencia en dispositivos móviles
- Dashboard con estadísticas 🥭
- Integración con Google Calendar
- Integración con Vetesoft (https://app.vetesoft.org/login/)
- Organizar el sistema bajo el modelo MVC
- Agregar sistema de pruebas automatizadas
- Documentación extendida
- Agregar sistema de personalización "Skins"
- Agregar sistema de configuración modular, pensando la app para varios tipos de usuarios (debe modificarse, nombre de la app, footer, logo, favicon)
- Mejoras para PWA
- Crear estructura de clases para modelos principales (User, Appointment)
- carga lazy de eventos para calendarios con muchas citas

## Versión 0.0.6.3 - ⚙️ Settings Update
- Se actualizó FullCalendar a la versión 6.1.17
- Se implementó API para obtención de usuarios
- Se mejoró la robustez de la selección de usuarios en formularios
- Se agregaron registros de depuración para facilitar la resolución de problemas
- Se implementó mecanismo de recuperación ante fallos en la carga de usuarios
- Se agregaron filtros según usuarios, estos filtros pueden modificarse en el panel de usuarios

## Versión 0.0.6.2 - ⚙️ Settings Update
- Se actualizó los flujos de n8n y los prompts

## Versión 0.0.6.1 - ⚙️ Settings Update
- Se actualizó los flujos de n8n y los prompts

## Versión 0.0.6 - ⚙️ Settings Update
- Agregar Días laborales a la configuración
- Corrección de bugs conocidos

## Versión 0.0.5.9 - 🏓 Ping-Pong
- Implementación de autenticación JWT para APIs
- Nuevos archivos de utilidades JavaScript para manejo de tokens
  - `assets/js/helpers/api.js`: Manejo de peticiones API con autenticación
  - `assets/js/helpers/auth.js`: Gestión de token JWT (obtención, renovación y eliminación)
- Actualización de `api/token.php` para soportar autenticación basada en sesión
- Renovación automática de tokens expirados
- Implementación de manejo de errores 401 (No autorizado)
- Integración de tokens JWT en la libreta de notas

## Versión 0.0.5.8 - 🏓 Ping-Pong
- Nuevos endpoints para la API
- Estado actual de los prompts y los flujos de n8n en Docs

## Versión 0.0.5.7 - 🏓 Ping-Pong
- Actualización de la API (consultas del calendario)

## Versión 0.0.5.6 - 🏓 Ping-Pong
- Actualización de la API

## Versión 0.0.5.5 - 🏓 Ping-Pong
- Se corrigieron errores conocidos

## Versión 0.0.5.4 - 🏓 Ping-Pong
- Se corrigió Error en iOS que no muestra el menú hamburguesa

## Versión 0.0.5.3 - 🏓 Ping-Pong
- Se corrigió bug que duplicaba citas
- Se completó la migración de JS vanilla a ES6

## Versión 0.0.5.2 - 🏓 Ping-Pong
- lo que la actualización 0.0.5.1 debía hacer

## Versión 0.0.5.1 - 🏓 Ping-Pong
- Corrección de error 403

## Versión 0.0.5 - 🏓 Ping-Pong
- Se corrigieron errores conocidos
- Se agregó un nuevo sistema de API que permite interactuar con la APP, para su integración con n8n
- Se agregó colecciones de postman para pruebas de la API
- Se agregó mensaje de confirmación para mover citas
- Se agregó acción de deshhacer al mover citas
- Se eliminó la leyenda de colores del calendario
- Se Agregó botón deshacer

## Versión 0.0.4 - 🕵️ Sherlock
- Se empezó migración gradual a MVC (Auth, Notes)
- Agregar panel CRUD de usuarios "Solo para Admins"
- Colores para diferenciar los profesionales
- Solución de errores con el sistema de drag and drop
- Se agregaron logs para depuración
- Ahora se puede agendar en el calendario "General"
- Libreta de notas

## Versión 0.0.3 - 🪲 Organizando la Casa
- Búsqueda y correcciones de bugs
- Se actualizó FullCalendar a la versión 6.1.15
- Se dividió en módulos el archivo de estilos
- Se dividió en módulos la lógica del calendario
- Se actualizó el sistema grid de cuadrículas css
- Se desarrolló una nueva API
- Ahora si se presiona la tecla ESC del teclado con un modal abierto se cierra
- Vista por defecto en Escritorio: Semana, Vista por defecto en movil: Lista
- Nueva función: Eventos de todo el día

### 🏗️ Modularización
- Implementación de estructura modular para el calendario
- Separación del código en módulos independientes:
  - `includes/calendar/init.php`: Inicialización y coordinación de componentes
  - `includes/calendar/data.php`: Procesamiento de datos del calendario
  - `includes/calendar/template.php`: Estructura HTML del calendario
  - `includes/calendar/modal.php`: Modal para crear/editar citas
  - `includes/calendar/scripts.php`: Scripts JavaScript del calendario
- Optimización de código con reducción de archivos extensos
- Mejora de la mantenibilidad y testabilidad del código
- Se prepara para implementación de patrón MVC, esto puede tomar varias updates 🥲
- Modularizado el código JavaScript del calendario
- Creada estructura de módulos en `assets/js/js-modules/`
- Separadas las funcionalidades en archivos específicos
- Mejorado el rendimiento al cargar solo los scripts necesarios
- Añadido README con documentación de la estructura
- Modularizado el sistema de calendario
- Creados módulos específicos:
  - `data.php`: Para manejo de datos del calendario
  - `template.php`: Para la estructura HTML
  - `modal.php`: Para la ventana modal de citas
  - `scripts.php`: Para los scripts JavaScript
  - `init.php`: Para inicializar todo el sistema
- Mejorada la estructura de archivos para mejor organización

## Versión 0.0.2 - 📅 UX Calendar Update
- Nuevo sistema de configuración:
  - Selección de hora de inicio / fin
  - Selección de duración de los Slots
  - Formato de Hora (12 hrs / 24 hrs)
- Nuevo sistema de funciones modular:
  - appointments: Manejo de citas
  - Calendar: Manejo del calendario
  - ui: manejo de Interfaces
- Nuevo sistema Drag and Drop

## 🪲 Bug detectados:
- main.min.js:12  Uncaught SyntaxError: Cannot use import statement outside a module

## Versión 0.0.1.0 - Calendarios Múltiples

### Nuevas Funcionalidades
- Implementación de tres tipos de calendarios distintos:
  - Calendario General (muestra todas las citas)
  - Calendario Estético (exclusivo para citas de estética)
  - Calendario Veterinario (exclusivo para citas veterinarias)
- Sistema de navegación con pestañas para cambiar entre calendarios
- Colores distintivos para cada tipo de cita:
  - Estético: Púrpura (#8E44AD)
  - Veterinario: Azul (#2E86C1)
  - General: Azul original (#5D69F7)
- Leyenda de colores para identificar tipos de citas en la vista general
- Formularios específicos según el tipo de calendario

### Mejoras
- Indicador visual de color en la lista de próximas citas
- Filtrado automático de próximas citas según el calendario activo
- Títulos personalizados en cada tipo de calendario
- Experiencia de usuario mejorada con animaciones sutiles
- Mensajes descriptivos en cada página específica

### Correcciones
- Solucionado problema con eliminación de citas
- Corrección en la visualización de citas en diferentes vistas
- Ajustes en los estilos para mejorar consistencia visual

## Versión 0.0.0.2 - Mejoras de Interfaz

### Nuevas Funcionalidades
- Sistema de notificaciones para acciones del usuario
- Tooltips informativos al pasar el cursor sobre las citas
- Lista de próximas citas en el panel lateral
- Sistema de autenticación de usuarios

### Mejoras
- Diseño responsivo optimizado
- Mejoras en el modal de creación/edición de citas
- Navegación mejorada entre semanas

## Versión 0.0.0.1 - Lanzamiento Inicial

### Funcionalidades Base
- Calendario interactivo con FullCalendar
- Creación, edición y eliminación de citas
- Vistas por día, semana y mes
- Diseño responsivo básico