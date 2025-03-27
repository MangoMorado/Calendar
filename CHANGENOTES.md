# CHANGENOTES - Calendario MundoAnimal

## Lista de Cambios por implementar:
- estado de las citas segun codigo de color (Verde, Amarillo: reprogramar, Rojo)
- Problema de multiples mensajes
- Sistema de cola de citas, que el sistema reciba citas del chatbot y que un humano las programe
- Mejoras de seguridad
- Agregar Dias laborales a la configuración
- Bloquear agendas (impedir agendar bajo condiciones personalizables)
- Agregar sistema CRUD de Calendarios
- Agregar sistema de notificación por WhatsApp / Correo electronico
- Mejoras de velocidad de carga
- Optimización de Consultas: Para mejor rendimiento con grandes volúmenes de datos
- Mejora de Responsividad: Para mejorar la experiencia en dispositivos móviles
- Dashboard con estadisticas 🥭
- Integración con Google Calendar
- Integracion con Vetesoft (https://app.vetesoft.org/login/)
- Integración con el chatbot
- Organizar el sistema bajo el modelo MVC
- Agregar sistema de pruebas automatizadas
- Documentación extendida
- Agregar nuevo Sugerencias, panel de sugerencias de nuevas caracteristicas o reporte de errores
- Agregar sistema de personalización "Skins"
- Agregar sistema de configuración modular, pensando la app para varios tipos de usuarios (debe modificarse, nombre de la app, footer, logo, favicon)
- Agregar Favicons
- Mejoras para PWA
- Agregar un al modal que sea Responsable: "Listar usuarios"
- Crear estructura de clases para modelos principales (User, Appointment)
- Agregar configuración de color en el menu de configuraciones
- carga lazy de eventos para calendarios con muchas citas

## Versión 0.0.4 - 🕵️ Sherlock
- Se empezo migración gradual a MVC (Auth, Notes)
- Agregar panel CRUD de usuarios "Solo para Admins"
- Colores para diferenciar los profesionales
- Solución de errores con el sistema de drag and drop
- Se agregaron logs para depuración
- Ahora se puede agendar en el calendario "General"
- Libreta de notas

## Versión 0.0.3 - 🪲 Organizando la Casa
- Busqueda y correciones de bugs
- Se actualizo FullCalendar a la versión 6.1.15
- Se dividio en modulos el archivo de estilos
- Se dividio en modulos la logica del calendario
- Se actualizo el sistema grid de cuadriculas css
- Se Desarrollo una nueva API
- Ahora si se preciona la tecla ESC del teclado con un modal abierto se cierra
- Vista por defecto en Escritorio: Semana, Vista por defecto en movil: Lista
- Nueva función: Eventos de todo el dia

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
- Creada estructura de módulos en `includes/calendar/js-modules/`
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
  - Seleccion de hora de inicio / fin
  - Seleccion de duración de los Slots
  - Formato de Hora (12 hrs / 24 hrs)
- Nuevo sistema de funciones modular:
  - appointments: Manejo de citas
  - Calendar: Manejo del calendario
  - ui: manejor de Interfaces
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