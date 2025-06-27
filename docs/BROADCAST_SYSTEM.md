# Sistema de Difusiones - Mundo Animal

## Resumen

El sistema de difusiones permite enviar mensajes masivos a través de WhatsApp a listas de contactos predefinidas. Incluye gestión completa de listas, historial de envíos, estadísticas detalladas y seguimiento de cada mensaje.

## Características Principales

### ✅ Funcionalidades Implementadas

1. **Gestión de Listas de Difusión**
   - Crear, editar y eliminar listas
   - Agregar/remover contactos de las listas
   - Búsqueda y filtrado de listas
   - Estados activo/inactivo

2. **Envío de Difusiones**
   - Envío masivo a listas completas
   - Selección individual de contactos
   - Soporte para mensajes de texto e imágenes
   - Vista previa del mensaje
   - Confirmación antes del envío

3. **Seguimiento y Estadísticas**
   - Historial completo de difusiones
   - Estadísticas detalladas por difusión
   - Estado individual de cada envío
   - Información de errores específicos

4. **Integración con Evolution API**
   - Verificación automática del estado de la instancia
   - Envío de mensajes de texto e imágenes
   - Manejo robusto de errores
   - Rate limiting para evitar bloqueos

## Instalación y Configuración

### 1. Crear las Tablas de Base de Datos

Ejecuta el script de configuración:

```bash
# Accede a tu navegador y visita:
http://tu-dominio/Calendar/setup_broadcast_tables.php
```

O ejecuta manualmente el archivo SQL:
```sql
-- Ver archivo: config/create_broadcast_tables.sql
```

### 2. Configurar Evolution API

En el panel de administración (`config.php`):

1. **URL de Evolution API**: `http://tu-servidor-evolution:8080`
2. **API Key**: Tu clave de API de Evolution
3. **Instancia**: Selecciona la instancia de WhatsApp conectada

### 3. Verificar la Conexión

El sistema verificará automáticamente:
- Estado de la instancia de WhatsApp
- Configuración de la API
- Permisos de acceso

## Uso del Sistema

### Crear una Lista de Difusión

1. Ve a **Listas de Difusión** (`broadcast_lists.php`)
2. Haz clic en **"Nueva Lista"**
3. Completa:
   - **Nombre**: Identificador de la lista
   - **Descripción**: Propósito de la lista
4. Haz clic en **"Crear Lista"**

### Agregar Contactos a una Lista

1. Edita la lista creada
2. En la sección **"Agregar contactos"**:
   - Usa el buscador para filtrar contactos
   - Selecciona los contactos deseados
   - Haz clic en **"Agregar Contactos"**

### Enviar una Difusión

1. En la sección **"Enviar Difusión"**:
   - Selecciona la lista de destino
   - Escribe el mensaje (máximo 4096 caracteres)
   - Opcionalmente, adjunta una imagen
   - Usa **"Vista previa"** para revisar el mensaje
   - Selecciona los contactos específicos (opcional)
   - Haz clic en **"Enviar difusión"**

2. **Confirmación**: El sistema pedirá confirmación antes del envío

3. **Seguimiento**: Se mostrará el progreso en tiempo real

### Ver Detalles de una Difusión

1. Después del envío, haz clic en **"Ver detalles"**
2. O ve a **Listas de Difusión** → **Ver** en cualquier difusión anterior

La página de detalles muestra:
- Información general de la difusión
- Estadísticas completas
- Lista de todos los contactos con su estado
- Errores específicos (si los hay)

## Estructura de Base de Datos

### Tablas Principales

#### `broadcast_lists`
- Listas de difusión creadas por los usuarios
- Campos: id, name, description, user_id, is_active, timestamps

#### `broadcast_list_contacts`
- Relación entre listas y contactos
- Campos: id, list_id, contact_id, added_at

#### `broadcast_history`
- Historial de difusiones enviadas
- Campos: id, list_id, message, image_path, total_contacts, sent_successfully, sent_failed, user_id, status, timestamps

#### `broadcast_details`
- Detalles individuales de cada envío
- Campos: id, broadcast_id, contact_id, contact_number, status, error_message, sent_at

## API Endpoints

### Envío Masivo
```
POST /api/send_broadcast_bulk.php
```

**Parámetros:**
- `list_id`: ID de la lista de difusión
- `message`: Mensaje de texto (opcional si hay imagen)
- `image`: Archivo de imagen (opcional)
- `selected_contacts[]`: Array de números de teléfono (opcional)

**Respuesta:**
```json
{
  "success": true,
  "message": "Difusión procesada",
  "data": {
    "broadcast_id": 123,
    "total_contacts": 50,
    "sent_successfully": 48,
    "sent_failed": 2,
    "status": "completed"
  }
}
```

### Envío Individual
```
POST /api/send_broadcast.php
```

**Parámetros:**
- `number`: Número de teléfono
- `mensaje`: Mensaje de texto
- `imagen`: Archivo de imagen (opcional)

## Estados y Flujo de Trabajo

### Estados de Difusión
- **pending**: Pendiente de envío
- **in_progress**: En proceso de envío
- **completed**: Completada exitosamente
- **failed**: Fallida completamente
- **cancelled**: Cancelada

### Estados de Envío Individual
- **pending**: Pendiente
- **sent**: Enviado exitosamente
- **failed**: Fallido
- **cancelled**: Cancelado

## Características de Seguridad

### Validaciones
- Verificación de permisos de usuario
- Validación de acceso a listas
- Comprobación de estado de instancia
- Rate limiting (0.5 segundos entre envíos)

### Manejo de Errores
- Logging detallado de errores
- Información específica de debugging
- Recuperación automática de fallos
- Limpieza de archivos temporales

## Limitaciones y Consideraciones

### WhatsApp
- **Límite de caracteres**: 4096 por mensaje
- **Rate limiting**: Máximo 1 mensaje por segundo
- **Formato de números**: Debe incluir código de país
- **Estado de instancia**: Debe estar conectada

### Sistema
- **Tamaño de imagen**: Máximo 5MB
- **Formatos soportados**: JPG, PNG, GIF
- **Tiempo de envío**: Depende del número de contactos
- **Almacenamiento**: Imágenes temporales se eliminan automáticamente

## Troubleshooting

### Problemas Comunes

#### "La instancia de WhatsApp no está conectada"
- Verifica que la instancia esté activa en Evolution API
- Revisa la configuración de URL y API Key
- Asegúrate de que WhatsApp esté conectado

#### "Error al enviar mensaje"
- Verifica el formato del número de teléfono
- Revisa los logs de error en la consola
- Comprueba el estado de la instancia

#### "No se pudo guardar la imagen"
- Verifica permisos de escritura en la carpeta `uploads/`
- Comprueba el tamaño del archivo
- Asegúrate de que el formato sea compatible

### Logs y Debugging

Los logs se guardan en:
- **PHP errors**: `php_error.log`
- **Evolution API**: Consola del navegador
- **Base de datos**: Tabla `broadcast_details`

## Próximas Mejoras

### Funcionalidades Planificadas
- [ ] Programación de difusiones
- [ ] Plantillas de mensajes
- [ ] Segmentación avanzada
- [ ] Reportes y analytics
- [ ] Integración con otros canales

### Optimizaciones
- [ ] Envío asíncrono con colas
- [ ] Caché de contactos
- [ ] Compresión de imágenes
- [ ] API REST completa

## Soporte

Para problemas o preguntas:
1. Revisa los logs de error
2. Verifica la configuración de Evolution API
3. Consulta la documentación de Evolution API
4. Contacta al equipo de desarrollo

---

**Versión**: 1.0.0  
**Última actualización**: Diciembre 2024  
**Compatibilidad**: PHP 7.0+, MySQL 5.6+, Evolution API 