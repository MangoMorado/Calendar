## Mango Morado | Calendario Mundo Animal — Features y Estructura de Base de Datos

### Características principales

- **Autenticación y roles**: Registro, login, recuperación de contraseña, control de acceso con roles (`admin`, `user`).
- **Gestión de calendarios y citas**:
  - Vistas día/semana/mes con FullCalendar v6.1.17 y Drag & Drop.
  - CRUD de tipo de calendarios
  - Citas de tipo `general`, `estetico`, `veterinario` y por usuario (`user_{id}`).
  - Modal para crear/editar; acciones de mover/editar/eliminar; soporte all-day.
- **Configuración del sistema (UI admin)**:
  - Horarios (`slotMinTime`, `slotMaxTime`), duración (`slotDuration`), formato (12h/24h), días hábiles, zona horaria.
  - Integraciones: n8n (workflows) y Evolution API (instancias WhatsApp).
- **Notas (libreta)**: Notas con visibilidad `todos` o `solo_yo`, tipos (`nota`, `sugerencia`, `otro`, `actualizaciones`).
- **Difusiones (Broadcast)**:
  - Listas de difusión, contactos, envío de texto e imágenes vía Evolution API.
  - Historial de difusiones y detalles por contacto; estadísticas básicas.
  - Importación de contactos desde API/JSON, validación robusta de números.
- **API REST con JWT**: Endpoints para citas, notas, usuarios, tokens; OpenAPI (`docs/openapi.yaml`) y Postman.
- **Seguridad**: Password hashing, prepared statements, sanitización/escape en vistas, JWT para APIs, control de sesiones.
- **UX/UI**: Interfaz moderna, responsiva, módulos JS/CSS, feedback visual, tooltips/estados.
- **Herramientas y pruebas**: Scripts en `test/` y `tools/` para diagnóstico (sesiones, API, importaciones, envíos, etc.).

### Integraciones

- **Evolution API**: Envío de mensajes/medios a WhatsApp; gestión de instancias y estado.
- **n8n**: Selección de workflow activo vía API; soporte para automatizaciones y chatbot.

### Arquitectura

- **PHP modular**:
  - Ningun archivo debe superar las mil lineas de codigo
  - Las responsabilidades por documento deben estar separadas
  - Reutilizar al maximo el codigo (DRY)
  - API en `api/` con endpoints separados y JWT.

---

## Estructura de la Base de Datos

Nota: La estructura base proviene de `config/create_tables.sql`. Tablas relacionadas a difusiones (broadcasts) y contactos se infieren de los modelos y endpoints existentes. Cuando un campo es inferido por uso en código, se marca como “(inferido)”.

### 1. users

```sql
users (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  email             VARCHAR(255) NOT NULL UNIQUE,
  password          VARCHAR(255) NOT NULL,
  name              VARCHAR(255) NOT NULL,
  role              ENUM('admin','user') NOT NULL DEFAULT 'user',
  history           TEXT,
  color             VARCHAR(7) DEFAULT '#3788d8',
  calendar_visible  TINYINT(1) NOT NULL DEFAULT 1,
  reset_token       VARCHAR(255) DEFAULT NULL,
  reset_token_expiry DATETIME DEFAULT NULL,
  created_at        TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
```

### 2. appointments

```sql
appointments (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  title          VARCHAR(255) NOT NULL,
  description    TEXT,
  start_time     DATETIME NOT NULL,
  end_time       DATETIME NOT NULL,
  calendar_type  ENUM('estetico','veterinario','general') DEFAULT 'estetico',
  all_day        TINYINT(1) NOT NULL DEFAULT 0,
  user_id        INT DEFAULT NULL REFERENCES users(id) ON DELETE SET NULL,
  color          VARCHAR(7) DEFAULT '#3788d8',
  created_at     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
```

### 3. notes

```sql
notes (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  title       VARCHAR(100) NOT NULL,
  content     TEXT NOT NULL,
  type        ENUM('nota','sugerencia','otro') NOT NULL DEFAULT 'nota',
  visibility  ENUM('solo_yo','todos') NOT NULL DEFAULT 'solo_yo',
  user_id     INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
```

### 4. settings

```sql
settings (
  setting_key    VARCHAR(255) PRIMARY KEY,
  setting_value  TEXT NOT NULL,
  created_at     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
```

### 5. user_sessions

```sql
user_sessions (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  user_id        INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  session_id     VARCHAR(255) NOT NULL UNIQUE,
  ip_address     VARCHAR(45) NOT NULL,
  user_agent     TEXT,
  device_info    VARCHAR(255),
  remember_me    TINYINT(1) NOT NULL DEFAULT 0,
  expires_at     TIMESTAMP NOT NULL,
  created_at     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  last_activity  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  is_active      TINYINT(1) NOT NULL DEFAULT 1,
  INDEX idx_session_id (session_id),
  INDEX idx_user_id (user_id),
  INDEX idx_expires_at (expires_at),
  INDEX idx_is_active (is_active)
)
```

### 6. session_settings

```sql
session_settings (
  setting_key    VARCHAR(255) PRIMARY KEY,
  setting_value  TEXT NOT NULL,
  created_at     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
```

Valores por defecto insertados (si no existen):
- `session_timeout = 3600`
- `remember_me_timeout = 604800`
- `max_sessions_per_user = 5`
- `require_login_on_visit = 1`
- `session_cleanup_interval = 86400`

### 7. contacts

Base definida en SQL:
```sql
contacts (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  number    VARCHAR(50) NOT NULL UNIQUE,
  pushName  VARCHAR(255) DEFAULT NULL,
  send      BOOLEAN NOT NULL DEFAULT FALSE
)
```

Uso inferido por modelos (campos adicionales que el código utiliza):
- `user_id` (INT) (inferido)
- `created_at` (TIMESTAMP) (inferido)

### 8. broadcast_lists (inferido por modelos)

```sql
broadcast_lists (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(255) NOT NULL,
  description TEXT,
  user_id     INT NOT NULL REFERENCES users(id),
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
```

### 9. broadcast_list_contacts (inferido por modelos)

```sql
broadcast_list_contacts (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  list_id    INT NOT NULL REFERENCES broadcast_lists(id),
  contact_id INT NOT NULL REFERENCES contacts(id),
  added_at   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_list_contact (list_id, contact_id)
)
```

### 10. broadcast_history (inferido por modelos)

```sql
broadcast_history (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  list_id           INT NOT NULL REFERENCES broadcast_lists(id),
  message           TEXT,
  image_path        VARCHAR(255),
  total_contacts    INT,
  user_id           INT NOT NULL REFERENCES users(id),
  status            ENUM('in_progress','completed','failed') NOT NULL DEFAULT 'in_progress',
  sent_successfully INT DEFAULT 0,
  sent_failed       INT DEFAULT 0,
  started_at        TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at      TIMESTAMP NULL DEFAULT NULL,
  created_at        TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
```

### 11. broadcast_details (inferido por modelos)

```sql
broadcast_details (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  broadcast_id    INT NOT NULL REFERENCES broadcast_history(id),
  contact_id      INT NULL REFERENCES contacts(id),
  contact_number  VARCHAR(50) NULL,
  status          VARCHAR(50) NOT NULL,  -- e.g., sent, failed, pending
  error_message   TEXT NULL,
  sent_at         DATETIME NULL,
  created_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
)
```

---


