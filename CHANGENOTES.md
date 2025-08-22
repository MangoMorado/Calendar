# CHANGENOTES - Calendario MundoAnimal

## Lista de Cambios por implementar:
- Revisar linea 67 de Appointments.php / API
- reparar bug: (no modifiques nada solo explicame porque sale esto:

Warning: ini_set(): Session ini settings cannot be changed after headers have already been sent in /includes/session_config.php on line 17

Warning: session_set_cookie_params(): Session cookie parameters cannot be changed after headers have already been sent in includes/session_config.php on line 18

Warning: ini_set(): Session ini settings cannot be changed after headers have already been sent in includes/session_config.php on line 17

Warning: session_set_cookie_params(): Session cookie parameters cannot be changed after headers have already been sent in includes/session_config.php on line 18

Warning: session_start(): Session cannot be started after headers have already been sent in includes/auth.php on line 10

Warning: session_start(): Session cannot be started after headers have already been sent in includes/auth.php on line 299

Warning: Cannot modify header information - headers already sent by (output started at config/database.php:296) in includes/auth.php on line 110)

- Agregar retroalimentación del estado de envio de las difusiones
- Estado de las citas según código de color (Verde, Amarillo: reprogramar, Rojo)
- Sistema de cola de citas, que el sistema reciba citas del chatbot y que un humano las programe
- Mejoras de seguridad: Mover la JWT_SECRET fuera del código fuente. Restringir Access-Control-Allow-Origin en producción. Revisar posibles vulnerabilidades (inyección SQL, XSS - aunque el uso de consultas preparadas y htmlspecialchars ayuda).
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
- Reparar consistencia: Hay dos formas de manejar las citas (API REST en api/ y el script process_appointment.php). Sería ideal consolidar en una sola (probablemente la API). La eliminación de citas en assets/js/app.js usa el endpoint antiguo y recarga la página, podría mejorarse usando la API y actualizando dinámicamente.
- Mejorar eficiencia: La obtención y paginación del historial en historial.php podría optimizarse. El sistema de migración automática en config/database.php podría reemplazarse por uno más robusto si la aplicación crece.
- Mejoras detectas en la 0.0.8:
  - Consistencia: Dos formas de manejar citas (API y process_appointment.php)
  - Arquitectura: Migración gradual a MVC en progreso
  - Rendimiento: Optimización de consultas para grandes volúmenes
  - Seguridad: JWT_SECRET en código fuente
  - Testing: Falta sistema de pruebas automatizadas
  - Optimizar Consultas: Implementar paginación y lazy loading
  - Mejorar Seguridad: Mover secretos a variables de entorno
  - Agregar Testing: Implementar pruebas unitarias y de integración
- Agregar CI/CD autodeploy en server y vps
- Modificar arquitectura para SAAS

## Version 0.3.3 - GPT Review
- **Sistema de integración continua con GitHub Actions:** Ahora hace deploy FTP automaticamente con github

## Version 0.3.2 - GPT Review
- **Sistema de validación robusto**: Implementada validación robusta de números de WhatsApp para evitar números inválidos como "718584497008509@s.whatsapp.net"
- **Herramienta de limpieza**: Nueva herramienta `cleanup_invalid_contacts.php` para identificar y eliminar contactos inválidos existentes
- **Actualización de difusiones automáticas**: Botón "Actualizar Difusiones Automáticas" que elimina difusiones existentes y crea nuevas con descripción simplificada
- **Validación en importación**: Todas las funciones de importación de contactos ahora usan el sistema de validación robusto
- **Corrección de recursión**: Solucionado error de "Maximum call stack size exceeded" en notificaciones 

## Version 0.3.1.3 - GPT Review
- Nueva tool permite generar json de las listas de difusion [generate_broadcast_json.php]

## Version 0.3.1.2 - GPT Review
- Las listas de difusion ahora son globales

## Version 0.3.1.1 - GPT Review
- Reparado errores de visualización de notas
- Se movio archivos a tools

## Version 0.3.1 - GPT Review
- Nuevo sistema de creación de difusiones automaticas
- Se agrega la difusion de Evolution API el parametro de la intancia configurada

## Version 0.3 - GPT Review
- Reparación de bugs conocidos

## Version 0.2.0.3 - Aullador
- Se repararon bugs respecto al envio de información a n8n
- Se eliminaron documentos obsoletos

## Version 0.2.0.2 - Aullador
- Se agregaron tools relacionadas con los cambios a la base de datos, si da error al enviar difusion ejecutar tools/fix_contacts_table.php

## Version 0.2.0.1 - Aullador
- Se corrigio bug de respuesta JSON de n8n

## Version 0.2 - Aullador
- Se reformo por completo el sistema de difusiones, ahora funciona con n8n, el flujo esta disponible en docs/n8n

## Version 0.1.1.2
- Correciones de bug de listado de los contactos

## Version 0.1.1.1
- Mejoras y correcciones en el envío masivo de difusiones, gestión de contactos y estilos de listas.

## Version 0.1.1
- Se actualizo el modal de envio de difusiones

## Version 0.1.0.4
- Se agrego la funcion de agregar numeros de manera manual

## Version 0.1.0.3
- **CORRECCIÓN DE AUTENTICACIÓN**: Se agregó `credentials: 'include'` al fetch en `views/broadcast_lists/send.php` para asegurar que las cookies de sesión se envíen correctamente a la API.
- **DEBUGGING MEJORADO**: Se agregó logging detallado en `api/send_broadcast_bulk.php` para facilitar la depuración de problemas de autenticación.

## Version 0.1.0.3.1
- Se agrego test de envio de imagenes

## Version 0.1.0.3.2
- Se corrigio bug de envio de imagenes

## Version 0.1.0.2 - Corrección de Difusiones con Imágenes

- **CORRECCIÓN CRÍTICA**: Se corrigió el error de tabla `broadcasts` que no existía, cambiando a `broadcast_history` en `api/send_broadcast_bulk.php`.
- **NUEVA FUNCIÓN**: Se agregó `sendEvolutionMedia()` en `includes/evolution_api.php` para enviar imágenes según la documentación oficial de Evolution API.
- **ACTUALIZACIÓN**: Se modificó `api/send_broadcast_bulk.php` para usar la función correcta según si hay imagen o solo texto.
- **MEJORA**: El test de imágenes ahora verifica también la configuración de Evolution API y las funciones disponibles.

## Version 0.1.0.1 - TestRunner

- Se agregó el test `test_broadcast_image.php` para verificar el soporte de envío de difusiones con imágenes (permisos, funciones PHP, directorio uploads, etc).
- Se creó el endpoint API correspondiente en `api/test/test_broadcast_image.php` para integración automática.
- Se integró el test de imágenes en el listado y lógica de la interfaz de tests (`test/index.php`), con soporte de LED verde/rojo/amarillo según el resultado.
- Mejoras en validación y manejo de errores para el envío de imágenes en el sistema de difusión.

## Version 0.1.0 - "TestRunner"
- Refactorización de los archivos de test para soportar ejecución dual: navegador (HTML informativo) y API (respuesta simple para automatización).
- Creación de endpoints en api/test/ que permiten ejecutar cada test vía AJAX y obtener un resultado claro (ok/error/warning).
- Integración visual en test/index.php con botón "Correr todos los tests" y LEDs de estado para cada test y global.
- Corrección de rutas relativas y absolutas para compatibilidad total en la navegación y ejecución de pruebas.
- Robustez en la detección de modo API para evitar análisis de HTML y asegurar resultados confiables en el dashboard de pruebas.
- **Refactorización y conversión a MVC de broadcast_lists:**
    - Separación de la lógica en un controlador (`BroadcastListController.php`).
    - Creación de vistas independientes para listar, crear, editar, ver y enviar difusiones.
    - Implementación de un router minimalista en `broadcast_lists.php` para delegar acciones.
    - Mejora de la experiencia de usuario y mantenibilidad del módulo de listas de difusión.

## Version 0.0.9 - 📒 Paginas Amarillas
- Ahora la app permite importar contactos de WhatsApp y guardarlos en la base de datos
- Permite enviar difusiones con texto plano o imagenes

### Version 0.0.9.1 - 📒 Paginas Amarillas
- Correción de bug de sesiones

### Version 0.0.9.2 - 📒 Paginas Amarillas
- Creacion de debugs y test para sesiones

### Version 0.0.9.3 - 📒 Paginas Amarillas
- Refactor completo del sistema de sesiones:
  - Eliminado el sistema personalizado de gestión de sesiones y cookies.
  - Ahora solo se usan sesiones PHP nativas, con configuración centralizada.
  - Función "recordar equipo" implementada usando solo parámetros nativos de PHP.
  - Control de timeout por inactividad y duración de sesión configurable desde la base de datos.
  - Todas las funciones de autenticación y control de sesión están en `includes/auth.php`.
  - Limpieza de código y eliminación de tablas y scripts obsoletos relacionados a sesiones.
  - Corrección de errores fatales y warnings relacionados con la gestión de sesiones y configuración de cookies.
  - Scripts de prueba y verificación de sesiones actualizados.
- Mejoras menores de seguridad y organización del código.
- Corrección de bug en la configuración de sesiones desde el panel de administración.
- Documentación y scripts de test para asegurar la correcta migración y funcionamiento del sistema simplificado.

### Version 0.0.9.4 - 📒 Paginas Amarillas
- Corrección del error 400 en la importación de contactos de Evolution API:
  - Revisión basada en la documentación oficial de Evolution API
  - Confirmado que el endpoint requiere método POST (no GET como se había corregido inicialmente)
  - Verificada la estructura correcta del body según documentación oficial
  - Actualizado el archivo `api/import_contacts.php` para usar POST con body correcto
  - Mejorado el script de diagnóstico `debug_import_contacts_v2.php` con información oficial
  - Actualizado script de prueba `test_import_contacts_fixed.php` con referencias a documentación
  - Identificadas posibles causas del error 400: instancia no conectada, API Key inválida, URL incorrecta
  - Documentación de la solución con enlaces a la documentación oficial de Evolution API

### Version 0.0.9.5 - 📒 Paginas Amarillas
- **🔄 Mejoras en Importación de Contactos JSON**
  - Actualizado `api/import_contacts_json.php` para compatibilidad con múltiples estructuras JSON
  - Nuevo script `import_from_json_file.php` para importación directa desde archivos JSON locales
  - Nuevo script `test_json_structure.php` para análisis y validación de archivos JSON
  - **Compatibilidad mejorada**: Soporte para arrays directos, objetos con clave 'contactos', y objetos únicos
  - **Validación robusta**: Verificación de estructura de contactos y campos requeridos
  - **Creación automática de tabla**: La tabla `contacts` se crea automáticamente si no existe
  - **Filtrado inteligente**: Ignora automáticamente grupos de WhatsApp (@g.us)
  - **Manejo de errores mejorado**: Mensajes de error específicos y estadísticas detalladas
  - **Interfaz web amigable**: Progreso en tiempo real y formularios integrados
  - **Estadísticas completas**: Conteo de importados, actualizados, omitidos y errores
  - **Limpieza de datos**: Validación de números de teléfono y campos requeridos
  - **Campos soportados**: `remoteJid`, `pushName`, `id`, `profilePicUrl`, `createdAt`, `updatedAt`, `instanceId`

### Version 0.0.9.6 - 📒 Paginas Amarillas
- **🚨 Mejoras en Manejo de Errores de Difusiones**
  - Nuevo script `debug_broadcast_400.php` para diagnóstico específico de errores HTTP 400
  - **Mejorado `api/send_broadcast.php`** con verificación previa del estado de la instancia
  - **Logging detallado**: Información completa de debugging para errores de envío
  - **Mensajes de error específicos**: Diferenciación entre errores de conexión, instancia y números
  - **Verificación de estado**: Comprobación automática del estado de la instancia antes del envío
  - **Frontend mejorado**: Detección automática de problemas críticos y detención del envío
  - **Información de debugging**: Detalles completos en consola para facilitar resolución de problemas
  - **Manejo inteligente de errores**: Detección de instancia desconectada y números inválidos
  - **Resumen detallado**: Información específica de errores al finalizar difusiones
- **🔗 Corrección de Codificación URL - Espacios en Instancia**
  - **Nuevo script `debug_url_encoding.php`** para diagnosticar problemas de codificación URL
  - **Corregido `api/send_broadcast.php`** para usar `rawurlencode()` en el nombre de la instancia
  - **Corregido `api/import_contacts.php`** para usar `rawurlencode()` en las URLs de Evolution API
  - **Corregido `chatbot_actions.php`** para usar `rawurlencode()` en todas las URLs de Evolution API
  - **Corregido `debug_broadcast_400.php`** para usar `rawurlencode()` en las URLs de prueba
  - **Solución para espacios**: Los espacios en nombres de instancia ahora se codifican correctamente como `%20`
  - **Compatibilidad mejorada**: Todas las URLs de Evolution API ahora manejan correctamente caracteres especiales

### Version 0.0.9.7 - 📒 Paginas Amarillas
- Sistema CRUD de difusiones

### Version 0.0.9.8 - 📒 Paginas Amarillas
- Correciones de backdrops y modales
- Corrección de envio de difusiones

## Version 0.0.8 - 💚 Evolution
- Nueva integración con Evolution API
  - Permite ingresar la API Key de Evoluution API
  - Permite ingresar la URL server de Evolution API
  - Permite obtener el listado de instancias y seleccionar uno
  - Permite saber si la instancia esta conectada o no

## Version 0.0.7 - 📣 Megáfono
- Corrección del bug del modal de creación / actualización de citas
- Nuevo sistema de sesiones
  - Se definen tiempos de sesión
  - Se permite recordar el equipo
  - Ahora se puede configurar un limite de sesiones
  - Ahora se puede configurar si desea recordar o no un equipo
  - Ahora se pueden establecer sesiones de limpieza de sesiones
- Actualizacion de los flujos de n8n y prompts
- Se agrego Timezones
- Se agrego integraciones con N8N
  - Permite ignresar una API KEY de n8n
  - Permite obtener el listado de workflows y seleccionar uno
  - Permite saber si el workflow esta activo o no
  - Permite prender o apagar el workflow

## Versión 0.0.6.6.1 - ⚙️ Settings Update
- Actualización de los flujos de N8N y prompts

## Versión 0.0.6.6 - ⚙️ Settings Update
- Actualización de los flujos de N8N y prompts

## Versión 0.0.6.5 - ⚙️ Settings Update
- Se agrego un boton que redirecciona al inventario de mundo animal
- Se agrego información sobre el sistema MCP en n8n
- Se actualizo el prompt de n8n de MundiBot

## Versión 0.0.6.4 - ⚙️ Settings Update
- Se actualizan los flujos de n8n y los prompts
- Se acttualizan las colecciones de Posman

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