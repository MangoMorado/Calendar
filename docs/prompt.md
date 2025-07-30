# Prompt para Cursor: App de Gestión de Agenda y Difusiones (Estilo Mundo Animal)

**Quiero que diseñes una aplicación web completa para la gestión de citas, agenda y difusiones masivas. Se desea una SAAS, cada usuario puede crear sus calendarios al gusto, hay 2 planes una versión gratis y una versión pro. Debe incluir todas las siguientes características y buenas prácticas:**

---

### 1. **Formulario de registro de nueva App**
- Registro del usuario no registrado en un formulario dinamico paso a paso, donde solo se le pregunte al usuario informacion clave
- Todo usuario nuevo debe ingresar con email y contraseña
- Todo usuario nuevo tiene la versión FREE

### 2. **Gestión de Usuarios y Autenticación**
- Registro, login y recuperación de contraseña.
- Roles: administrador y usuario estándar.
- Seguridad: contraseñas con hash seguro, JWT para API, control de sesiones y roles.

### 3. **Calendarios Interactivos**
- Sistema CRUD para crear, editar, actualizar, eliminar calendarios.
- Vista de calendario (día, semana, mes) usando FullCalendar.
- Drag & drop para mover citas.
- Creación, edición y eliminación de citas desde el calendario.
- Filtros por usuario y por tipo de calendarios
- la vista principal debe tener 2 columnas, la del calendario 70% y 30% un listado de proximas citas, que sean cicleables para poder editar
- Configuración de horarios, duración de slots, días hábiles y zona horaria.
- Botón "deshacer ultimo cambio"

### 4. **Gestión de Citas**
- CRUD completo de citas.
- Asignación de citas a usuarios/responsables.
- Estado visual de las citas (colores por estado o tipo).
- Historial de cambios y actividades.

### 5. **Notas y Recordatorios**
- CRUD de notas asociadas a usuarios o citas.
- Visibilidad: solo yo / todos.
- Tipos de nota: nota, sugerencia, bug, otro.

### 6. **Integraciones**
- Integración con Evolution API para WhatsApp.
- Integración con N8N.

### 7. **Difusiones Masivas (WhatsApp)**
- Gestión de listas de difusión y contactos.
- Envio a n8n para difusion de mensajes en lote
- Seguimiento de estado de cada envío (enviado, fallido, pendiente).
- Historial y estadísticas de difusiones.
- Soporte para colas, reintentos y rate limiting (integración con n8n).

### 8. **API RESTful**
- Endpoints protegidos con JWT para citas, notas, usuarios y difusiones.
- Documentación OpenAPI y colección Postman.
- Endpoints para consultar disponibilidad de horarios (con reglas de negocio).

### 9. **Integración con n8n**
- Endpoint para enviar solicitudes de difusión a n8n.
- Endpoint para actualizar el estado de la difusión desde n8n.
- Configuración de URL, API Key y workflow activo desde el panel de administración.

### 10. **Integración con EvolutionAPI**
- Envio de notificaciones de citas (1 vez por dia: "Para mañana tienes las siguientes citas: ...")
- Envio de notificaciones del estado de difusiones (Envio de diifusion terminada: X mensajes enviados)

### 11. **Panel de Administración**
- Configuración de horarios, sesiones, integración n8n y Evolution API.
- Selección de workflow activo de n8n.
- Visualización y gestión de usuarios, calendarios y difusiones.

### 12. **Seguridad y Buenas Prácticas**
- Validación de formularios en frontend y backend.
- Protección contra inyección SQL y XSS.
- Uso de prepared statements y sanitización de datos.
- Manejo seguro de tokens y claves API (variables de entorno).

### 13. **Documentación y Pruebas**
- Documentación técnica y de usuario.
- Scripts de prueba para endpoints y flujos críticos.
- Roadmap y changelog.

### 14. **Extras y UX**
- Interfaz moderna, responsiva y accesible con Shadcn.
- Notificaciones visuales y feedback de usuario.
- Soporte multilingüe (español por defecto).
- Dashboard con estadísticas (opcional).
- Preparada para integración futura con Google Calendar y otros servicios.

---

**Por favor, genera la estructura de carpetas, los modelos principales, los endpoints y los archivos de configuración base.**

---
