# Sistema nuevo vs sistema legacy — Features y migración

Documento de referencia: características del **sistema antiguo** (carpeta `old/`) y lo que **falta por migrar** al sistema nuevo (Laravel 12 + Inertia + React).

---

## 1. Resumen ejecutivo

| Área | Legacy | Nuevo sistema | Estado |
|------|--------|----------------|--------|
| Autenticación básica | ✅ | ✅ | Migrado (Fortify, 2FA, verificación email) |
| Roles | admin, user | Mango, Admin, User | Migrado (mejorado) |
| Calendarios | Tipos fijos (general, estético, veterinario, user_X) | CRUD de calendarios por usuario | Migrado (modelo distinto) |
| Citas | Por tipo, sin tabla calendarios | Por calendario (Calendar → Appointment) | Migrado |
| Usuarios CRUD | ✅ | ✅ | Migrado |
| Configuración global (config) | ✅ | ✅ | **Migrada, No será global, será por calendario** |
| Notas (libreta) | ✅ | ✅ | **Migrada, mejorada** |
| Difusiones (Broadcast) | ✅ | ❌ | **Descontinuada, no migrar** |
| Chatbot / integraciones UI | ✅ | ❌ | **Descontinuada, no migrar** |
| Estadísticas | ✅ | ✅ | **Migrada, mejorada** |
| Historial de usuarios | ✅ | ❌ | **Pendiente** |
| API REST (JWT) | ✅ | ❌ | **Pendiente, mejorar** |

---

## 2. Features del sistema legacy (detalle)

### 2.1 Autenticación y usuarios

- **Login, registro, recuperación de contraseña** (forgot_password.php, reset_password.php, register.php).
- **Roles**: `admin` y `user`. Solo admin accede a config, usuarios, historial, estadísticas.
- **Perfil** (profile.php): edición de datos de usuario.
- **Sesiones avanzadas**:
  - Tablas `user_sessions` y `session_settings`.
  - Configuración: timeout, remember_me_timeout, max_sessions_per_user, require_login_on_visit, session_cleanup_interval.
  - Panel de sesiones activas y cierre de sesiones específicas.
  - Cron `cleanup_sessions.php` para limpiar sesiones expiradas.

**En el nuevo:** Auth con Fortify (login, registro, 2FA, verificación email, reset password). Roles Mango, Admin, User. Perfil y contraseña en `/settings`. Sesiones estándar de Laravel; no existe panel ni límite de sesiones por usuario.

---

### 2.2 Calendarios y citas

- **Tipos de calendario**: `general`, `estetico`, `veterinario` y por usuario (`user_{id}` si `calendar_visible = 1`). Páginas: index.php (general), estetico.php, veterinario.php.
- **Citas**: tabla `appointments` con `calendar_type` ENUM (estetico, veterinario, general), sin tabla `calendars`. Campos: title, description, start_time, end_time, all_day, user_id, color.
- **Configuración del calendario** en tabla `settings`: slotMinTime, slotMaxTime, slotDuration, timeFormat (12h/24h), businessDays (JSON), timezone. Leída en includes/calendar/data.php y scripts.php.
- **FullCalendar**: vistas día/semana/mes, drag & drop, modal crear/editar.

**En el nuevo:** Modelo `Calendar` (name, description, color, user_id, is_active). Citas con `calendar_id` y `user_id`. Configuración de horarios/slot no está en BD ni en UI; FullCalendar en dashboard con filtro por calendario.

---

### 2.3 Configuración global del sistema (config.php)

Solo **admin**. Guarda en tabla `settings`:

- **Calendario**: slotMinTime, slotMaxTime, slotDuration, timeFormat, timezone, businessDays.
- **n8n**: n8n_url, n8n_api_key, selected_workflow, selected_notifications_workflow, notifications_webhook_url, notifications_send_time, notifications_daily_appointments_enabled.
- **Evolution API**: evolution_api_url, evolution_api_key, selected_evolution_instance, evolution_instance_name.
- **Sesiones**: session_timeout, remember_me_timeout, max_sessions_per_user, require_login_on_visit, session_cleanup_interval.

**En el nuevo:** No existe vista `/config` ni tabla `settings` para estas opciones (ROADMAP lo menciona como pendiente).

---

### 2.4 Notas (libreta)

- **Rutas**: notes.php (index, view, create, store, edit, update, delete).
- **Modelo**: NoteModel. Tabla `notes`: title, content, type (nota, sugerencia, otro), visibility (solo_yo, todos), user_id.
- **Vistas**: views/notes/index.php, form.php, view.php.
- **API**: old/api/notes.php (CRUD con JWT).

**En el nuevo:** No hay módulo de notas ni modelo Note.

---

### 2.5 Difusiones (Broadcast)

- **Listas**: broadcast_lists.php (list, create, edit, view, send). Tablas: broadcast_lists, broadcast_list_contacts, contacts.
- **Envío**: texto e imágenes vía Evolution API; también send_broadcast_n8n.php (n8n).
- **Historial**: broadcast_history, broadcast_details; vista broadcast_details.php con estadísticas por envío y por contacto.
- **Contactos**: importación desde API o JSON (import_contacts.php, import_contacts_json.php); validación de números WhatsApp.
- **API**: contacts_list.php, contacts_update_selection.php, import_contacts.php, import_contacts_json.php, send_broadcast_n8n.php.

**En el nuevo:** No hay difusiones ni contactos ni integración Evolution/n8n en la app.

---

### 2.6 Chatbot e integraciones

- **Página**: chatbot.php con pestañas Dashboard, Contactos, Configuración, Notificaciones (includes/chatbot/*).
- **Config**: n8n (URL, API key, workflow activo, workflow de notificaciones), Evolution API (URL, key, instancia).
- **Notificaciones**: webhook URL, hora de envío, toggle recordatorio diario; envío vía n8n.
- **Contactos**: gestión ligada a difusiones/chatbot.

**En el nuevo:** No hay UI de chatbot ni de configuración n8n/Evolution.

---

### 2.7 Estadísticas

- **Página**: estadisticas.php (solo admin).
- **Contenido**: total de citas, citas por mes (gráfico), totales por tipo (general, veterinario, estetico), promedios, pico de actividad, distribución por día de la semana.

**En el nuevo:** No hay módulo de estadísticas.

---

### 2.8 Historial de usuarios

- **Página**: historial.php (solo admin).
- **Datos**: campo `users.history` (TEXT), filtros por usuario y tipo, paginación.

**En el nuevo:** No hay campo history en User ni vista de historial.

---

### 2.9 API REST (JWT)

Endpoints en `old/api/`:

- **Auth**: auth.php, token.php.
- **Citas**: appointments.php, get_appointment.php, availability.php (slots disponibles para n8n/MundiBot).
- **Notas**: notes.php.
- **Usuarios**: users.php.
- **Contactos/difusiones**: contacts_list.php, contacts_update_selection.php, import_contacts.php, import_contacts_json.php, send_broadcast_n8n.php.
- **Util**: ping.php.

Documentación: docs/openapi.yaml, docs/API_AVAILABILITY_README.md, docs/n8n.

**En el nuevo:** No hay rutas API públicas; solo flujo web con Inertia.

---

### 2.10 Cron

- **cleanup_sessions.php**: limpieza de sesiones expiradas en `user_sessions`.
- **notify_daily_appointments.php**: envío de recordatorio diario de citas vía webhook n8n (configurable en settings).

**En el nuevo:** No hay tareas programadas equivalentes.

---

### 2.11 Base de datos legacy (tablas no migradas)

- **notes**: libreta de notas.
- **settings**: configuración global (calendario, n8n, Evolution, sesiones).
- **user_sessions** y **session_settings**: sesiones avanzadas.
- **contacts**: contactos para difusiones.
- **broadcast_lists**, **broadcast_list_contacts**, **broadcast_history**, **broadcast_details**: difusiones.

En el nuevo sistema las tablas son: users, calendars, appointments, cache, jobs, y las de Fortify/session de Laravel.

---

## 3. Lo que falta por migrar (checklist)

### Alta prioridad (funcionalidad de negocio)

- [ ] **Configuración global (`/config`)**  
  Vista admin para: horarios del calendario (slotMin/Max/Duration, timeFormat, timezone, businessDays), n8n, Evolution API, y opcionalmente sesiones. Persistencia en tabla `settings` o equivalente en Laravel (config o BD).

- [ ] **API de disponibilidad**  
  Endpoint tipo `availability` (rango de fechas, tipo o calendario) que devuelva slots disponibles para integración con n8n/MundiBot (docs/api/availability.md, docs/n8n).

- [ ] **API REST con JWT**  
  Endpoints para citas, notas (si se migran), usuarios y tokens, para integración externa y bots. Definir versionado y documentación (OpenAPI).

### Prioridad media

- [ ] **Notas (libreta)**  
  Modelo Note, migración de tabla, CRUD en UI (Inertia), políticas por visibility (solo_yo / todos). Opcional: API de notas.

- [ ] **Estadísticas**  
  Vista `/estadisticas` (o equivalente) con totales por periodo y por calendario/tipo, gráficos. Reutilizar modelos Calendar/Appointment.

- [ ] **Cron / tareas programadas**  
  - Limpieza de sesiones (si se vuelve a usar sesiones custom).  
  - Recordatorio diario de citas (webhook n8n o similar), configurable desde config.

### Prioridad baja o futura

- [ ] **Difusiones (Broadcast)**  
  Modelos y tablas (lists, contacts, history, details), UI de listas y envíos, integración Evolution API y/o n8n, importación de contactos. Documentado en docs/BROADCAST_SYSTEM.md.

- [ ] **Chatbot / panel de integraciones**  
  UI tipo chatbot.php para configurar n8n y Evolution, y gestión de contactos/notificaciones si se mantiene el mismo flujo que en legacy.

- [ ] **Historial de usuarios**  
  Campo `history` en User (o tabla aparte) y vista admin para consulta y filtros.

- [ ] **Sesiones avanzadas**  
  Límite de sesiones por usuario, panel de sesiones activas y cierre por dispositivo. Requiere tabla user_sessions y lógica propia o paquete Laravel.

---

## 4. Diferencias de modelo de datos

| Concepto | Legacy | Nuevo |
|----------|--------|-------|
| Calendario | Tipo fijo (general, estetico, veterinario) o user_X. Sin tabla calendars. | Tabla `calendars` por usuario, con name, color, is_active. |
| Cita | calendar_type en appointments. | calendar_id en appointments (FK a calendars). |
| Configuración horario | settings (slotMinTime, slotMaxTime, etc.). | No existe; podría añadirse en settings o config. |
| Roles | admin, user. | Mango, Admin, User (enum). |

Si se quiere compatibilidad con legacy (p. ej. informes por “tipo”): se puede añadir un campo opcional en Calendar (por ejemplo `type` o `slug`) o mantener la lógica solo en el nuevo modelo (calendarios por nombre/uso).

---

## 5. Referencia de archivos legacy

| Feature | Archivos / carpetas principales |
|---------|----------------------------------|
| Config | old/config.php, old/includes/calendar/data.php, scripts.php |
| Notas | old/notes.php, old/controllers/NoteController.php, old/models/NoteModel.php, old/views/notes/, old/api/notes.php |
| Broadcast | old/broadcast_lists.php, old/broadcast_details.php, old/send_broadcast.php, old/process_broadcast.php, old/views/broadcast_lists/, old/api/send_broadcast_n8n.php, old/includes/evolution_api.php |
| Chatbot | old/chatbot.php, old/chatbot_actions.php, old/includes/chatbot/ |
| Estadísticas | old/estadisticas.php |
| Historial | old/historial.php |
| Sesiones | old/includes/session_manager.php, old/includes/session_config.php, old/cron/cleanup_sessions.php |
| API | old/api/*.php, old/includes/api/jwt.php |
| Cron notificaciones | old/cron/notify_daily_appointments.php |
| Calendario legacy | old/index.php, old/estetico.php, old/veterinario.php, old/includes/calendar/ |

---

*Documento generado para guiar la migración del sistema legacy al nuevo sistema Laravel 12 + Inertia + React. Actualizar este checklist según se vayan implementando features.*
