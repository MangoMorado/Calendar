# Aplicación de Gestión de Agenda | Mundo Animal

Una aplicación web moderna para gestionar citas y calendarios en una clínica veterinaria mediante un calendario interactivo utilizando FullCalendar, con sistema de autenticación de usuarios, gestión de roles y potentes integraciones.

## 🚀 Tecnologías

### Backend
- **PHP 8.2+** - Lenguaje de programación
- **Laravel 12** - Framework PHP
- **Laravel Fortify** - Autenticación
- **Laravel Wayfinder** - Generación de rutas tipadas para TypeScript
- **Pest 4** - Framework de testing
- **Laravel Pint** - Formateador de código

### Frontend
- **React 19** - Biblioteca de UI
- **TypeScript** - Tipado estático
- **Inertia.js v2** - SPA sin API
- **FullCalendar 6** - Calendario interactivo
- **Tailwind CSS 4** - Framework de estilos
- **Radix UI** - Componentes accesibles

## ✨ Características Principales

### Sistema de Autenticación y Usuarios
- ✅ Registro de usuarios con validación
- ✅ Login con "Recordarme"
- ✅ Recuperación de contraseña
- ✅ Verificación de correo electrónico
- ✅ Autenticación de dos factores (2FA)
- ✅ Gestión completa de usuarios (CRUD)
- ✅ Sistema de roles: **Mango** (superadmin), **Admin**, **User**
- ✅ Restricciones de permisos por rol
- ✅ Perfil de usuario con edición de información personal

### Gestión de Calendarios
- ✅ CRUD completo de calendarios
- ✅ Múltiples calendarios por usuario
- ✅ Colores personalizables por calendario
- ✅ Estados activo/inactivo
- ✅ Filtrado de calendarios en el dashboard

### Gestión de Citas
- ✅ Vista de calendario interactivo (día, semana, mes)
- ✅ Drag & Drop para mover citas
- ✅ Creación, edición y eliminación de citas
- ✅ Citas de todo el día
- ✅ Asignación de citas a usuarios
- ✅ Vista de próximas citas
- ✅ Filtrado por calendario

### Interfaz de Usuario
- ✅ Interfaz completamente traducida al español
- ✅ Diseño responsivo y moderno
- ✅ Tema claro/oscuro (configurable)
- ✅ Componentes UI accesibles (Radix UI)
- ✅ Input de teléfono con selector de países (Colombia por defecto)
- ✅ Validación en tiempo real
- ✅ Feedback visual de acciones

### Seguridad y Testing
- ✅ Suite completa de tests (160+ tests)
- ✅ Tests de controladores, modelos, políticas
- ✅ Tests de Form Requests y middleware
- ✅ Factories para generación de datos de prueba
- ✅ Validación robusta en backend y frontend
- ✅ Políticas de autorización por rol

## 📋 Requisitos

- PHP >= 8.2
- Composer
- Node.js >= 18
- npm o yarn
- Base de datos (MySQL, PostgreSQL, SQLite)

## 🔧 Instalación

1. **Clonar el repositorio**
   ```bash
   git clone <repository-url>
   cd Calendar
   ```

2. **Instalar dependencias de PHP**
   ```bash
   composer install
   ```

3. **Instalar dependencias de Node.js**
   ```bash
   npm install
   ```

4. **Configurar el entorno**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configurar la base de datos**
   Edita el archivo `.env` y configura tu base de datos:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=calendar
   DB_USERNAME=root
   DB_PASSWORD=
   ```

6. **Ejecutar migraciones**
   ```bash
   php artisan migrate:fresh
   ```

7. **Compilar assets**
   ```bash
   npm run build
   ```

8. **Iniciar el servidor de desarrollo**
   ```bash
   composer run dev
   ```
   O por separado:
   ```bash
   php artisan serve
   npm run dev
   ```

## 🧪 Testing

Ejecutar todos los tests:
```bash
php artisan test --compact
```

Ejecutar tests específicos:
```bash
php artisan test --compact --filter=CalendarTest
```

## 📁 Estructura del Proyecto

```
Calendar/
├── app/
│   ├── Actions/          # Acciones de Fortify
│   ├── Concerns/         # Traits reutilizables
│   ├── Enums/            # Enumeraciones (Role)
│   ├── Http/
│   │   ├── Controllers/  # Controladores
│   │   ├── Middleware/  # Middleware personalizado
│   │   └── Requests/     # Form Requests
│   ├── Models/           # Modelos Eloquent
│   └── Policies/         # Políticas de autorización
├── database/
│   ├── factories/        # Factories para testing
│   ├── migrations/        # Migraciones de base de datos
│   └── seeders/          # Seeders
├── resources/
│   ├── js/
│   │   ├── components/   # Componentes React reutilizables
│   │   ├── layouts/      # Layouts de la aplicación
│   │   ├── pages/        # Páginas Inertia
│   │   └── types/        # Tipos TypeScript
│   └── views/            # Vistas Blade
├── routes/               # Rutas de la aplicación
└── tests/                # Tests Pest
```

## 🔐 Sistema de Roles

El sistema implementa tres roles con diferentes niveles de acceso:

- **Mango**: Superadministrador con permisos completos
  - Puede gestionar todos los usuarios (crear, editar, eliminar)
  - Puede asignar cualquier rol (User, Admin, Mango)
  - Acceso completo a todas las funcionalidades

- **Admin**: Administrador con permisos limitados
  - Puede gestionar usuarios pero solo crear usuarios con rol User
  - No puede crear usuarios Admin o Mango
  - Acceso a gestión de calendarios y citas

- **User**: Usuario estándar
  - Acceso a su propio perfil y calendarios
  - Puede crear y gestionar sus propios calendarios y citas

## 🎨 Características de UI/UX

- **Selector de teléfono**: Input con selector de banderas y formato automático
- **Tema personalizable**: Modo claro, oscuro o según sistema
- **Navegación intuitiva**: Sidebar colapsable y breadcrumbs
- **Feedback visual**: Mensajes de éxito/error y estados de carga
- **Responsive**: Diseño adaptativo para móviles y tablets

## 📝 Comandos Útiles

```bash
# Desarrollo
composer run dev          # Inicia servidor, queue y Vite
npm run dev               # Solo Vite en modo desarrollo
npm run build             # Compilar para producción

# Testing
php artisan test          # Ejecutar todos los tests
php artisan test --filter # Filtrar tests específicos

# Code Quality
vendor/bin/pint           # Formatear código PHP
npm run lint              # Linter de TypeScript/React
npm run format            # Formatear código frontend

# Base de datos
php artisan migrate:fresh # Reiniciar base de datos
php artisan migrate       # Ejecutar migraciones
php artisan db:seed       # Ejecutar seeders
```

## 📚 Documentación Adicional

- **CHANGENOTES.md**: Historial de cambios y versiones
- **ROADMAP.md**: Plan de desarrollo futuro
- **docs/**: Documentación técnica detallada
  - `FEATURES_AND_DB.md`: Características y estructura de BD
  - `API_AVAILABILITY_README.md`: Documentación de API
  - `BROADCAST_SYSTEM.md`: Sistema de difusiones
  - `SESSION_SYSTEM.md`: Sistema de sesiones

## 🔄 Migración desde Versión Anterior

Se mantiene el codigo legacy en la carpeta /old hasta la version 0.6 
- `old/`: Código legacy (referencia)

## 📄 Licencia

Este proyecto está licenciado bajo [MIT License](LICENSE).

---

> Para detalles técnicos, endpoints y ejemplos de uso, consulta la documentación en la carpeta `docs/` y el archivo `CHANGENOTES.md`.
