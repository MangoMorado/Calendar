# Documentación de la API - Mundo Animal

Este directorio contiene la documentación completa de la API del sistema de gestión de citas de Mundo Animal.

## Estructura de la Documentación

- `openapi.yaml`: Especificación OpenAPI 3.0 de la API
- `README.md`: Este archivo con ejemplos de uso
- `postman_collection.json`: Colección de Postman con todos los endpoints

## Ejemplos de Uso

### 1. Autenticación

La API soporta dos métodos de autenticación:

#### A. Autenticación Básica (Basic Auth)

```javascript
// Ejemplo usando fetch con Basic Auth
fetch('http://localhost/Calendar/api/auth.php', {
  method: 'POST',
  headers: {
    'Authorization': 'Basic ' + btoa('email@ejemplo.com:contraseña')
  }
})
.then(response => response.json())
.then(data => {
  console.log('Respuesta:', data);
  // Guardar el session_id para futuras peticiones
  const sessionId = data.session_id;
});

// Ejemplo usando jQuery
$.ajax({
  url: 'http://localhost/Calendar/api/auth.php',
  method: 'POST',
  headers: {
    'Authorization': 'Basic ' + btoa('email@ejemplo.com:contraseña')
  },
  success: function(data) {
    console.log('Respuesta:', data);
    // Guardar el session_id para futuras peticiones
    const sessionId = data.session_id;
  }
});
```

La respuesta incluirá:
```json
{
  "success": true,
  "message": "Autenticación exitosa",
  "user": {
    "id": 1,
    "name": "Nombre Usuario",
    "email": "email@ejemplo.com",
    "role": "admin"
  },
  "session_id": "abc123..."
}
```

#### B. Autenticación por Sesión

### 1. Obtener Eventos del Calendario

```javascript
// Ejemplo usando fetch
fetch('http://localhost/Calendar/api/appointments.php?action=get_events&start=2024-03-28T00:00:00&end=2024-03-29T23:59:59&calendar_type=estetico', {
  credentials: 'include' // Importante para enviar la cookie de sesión
})
.then(response => response.json())
.then(data => {
  console.log('Eventos:', data);
});

// Ejemplo usando jQuery
$.ajax({
  url: 'http://localhost/Calendar/api/appointments.php',
  method: 'GET',
  data: {
    action: 'get_events',
    start: '2024-03-28T00:00:00',
    end: '2024-03-29T23:59:59',
    calendar_type: 'estetico'
  },
  xhrFields: {
    withCredentials: true
  },
  success: function(data) {
    console.log('Eventos:', data);
  }
});
```

### 2. Crear una Nueva Cita

```javascript
// Ejemplo usando fetch
const formData = new FormData();
formData.append('action', 'create');
formData.append('title', 'Consulta de rutina');
formData.append('description', 'Revisión general del paciente');
formData.append('start_time', '2024-03-28T10:00:00');
formData.append('end_time', '2024-03-28T11:00:00');
formData.append('calendar_type', 'veterinario');
formData.append('all_day', 'false');
formData.append('user_id', '1');

fetch('http://localhost/Calendar/api/appointments.php', {
  method: 'POST',
  credentials: 'include',
  body: formData
})
.then(response => response.json())
.then(data => {
  console.log('Respuesta:', data);
});

// Ejemplo usando jQuery
$.ajax({
  url: 'http://localhost/Calendar/api/appointments.php',
  method: 'POST',
  data: {
    action: 'create',
    title: 'Consulta de rutina',
    description: 'Revisión general del paciente',
    start_time: '2024-03-28T10:00:00',
    end_time: '2024-03-28T11:00:00',
    calendar_type: 'veterinario',
    all_day: false,
    user_id: 1
  },
  xhrFields: {
    withCredentials: true
  },
  success: function(data) {
    console.log('Respuesta:', data);
  }
});
```

### 3. Actualizar una Cita Existente

```javascript
// Ejemplo usando fetch
const formData = new FormData();
formData.append('action', 'update');
formData.append('id', '123');
formData.append('title', 'Consulta de rutina - Actualizada');
formData.append('start_time', '2024-03-28T11:00:00');
formData.append('end_time', '2024-03-28T12:00:00');

fetch('http://localhost/Calendar/api/appointments.php', {
  method: 'POST',
  credentials: 'include',
  body: formData
})
.then(response => response.json())
.then(data => {
  console.log('Respuesta:', data);
});
```

### 4. Eliminar una Cita

```javascript
// Ejemplo usando fetch
const formData = new FormData();
formData.append('action', 'delete');
formData.append('id', '123');

fetch('http://localhost/Calendar/api/appointments.php', {
  method: 'POST',
  credentials: 'include',
  body: formData
})
.then(response => response.json())
.then(data => {
  console.log('Respuesta:', data);
});
```

### 5. Obtener Detalles de una Cita Específica

```javascript
// Ejemplo usando fetch
fetch('http://localhost/Calendar/api/get_appointment.php?id=123', {
  credentials: 'include'
})
.then(response => response.json())
.then(data => {
  console.log('Detalles de la cita:', data);
});
```

### 6. Obtener Notas

```javascript
// Obtener todas las notas
fetch('http://localhost/Calendar/api/notes.php?action=get_notes', {
  credentials: 'include'
})
.then(response => response.json())
.then(data => {
  console.log('Notas:', data);
});

// Obtener una nota específica
fetch('http://localhost/Calendar/api/notes.php?action=get_note&id=123', {
  credentials: 'include'
})
.then(response => response.json())
.then(data => {
  console.log('Nota:', data);
});
```

### 7. Verificar Estado de la API

```javascript
// Ejemplo usando fetch
fetch('http://localhost/Calendar/api/ping.php')
.then(response => response.json())
.then(data => {
  console.log('Respuesta:', data); // { "response": "pong", "timestamp": "2024-03-28 10:00:00" }
});
```

## Manejo de Errores

Todas las respuestas de error siguen este formato:

```json
{
  "success": false,
  "message": "Descripción del error"
}
```

### Códigos de Estado HTTP

- `200`: Operación exitosa
- `400`: Error de validación
- `401`: No autorizado o credenciales inválidas
- `404`: Recurso no encontrado

## Autenticación

La API soporta dos métodos de autenticación:

1. **Basic Auth**: Para autenticación inicial y obtención del session_id
   - Enviar credenciales en el header `Authorization: Basic base64(email:password)`
   - La respuesta incluirá un `session_id` para usar en futuras peticiones

2. **Sesión PHP**: Para peticiones subsecuentes
   - Incluir el `session_id` como cookie `PHPSESSID`
   - Usar `credentials: 'include'` en las peticiones fetch
   - La sesión se mantendrá activa hasta que se cierre explícitamente

## Formato de Fechas

Todas las fechas deben enviarse en formato ISO 8601:

```
YYYY-MM-DDTHH:mm:ss
```

Ejemplo: `2024-03-28T10:00:00`

## Tipos de Calendario

Los tipos de calendario disponibles son:

- `estetico`: Citas de estética
- `veterinario`: Citas médicas
- `general`: Todas las citas

## Herramientas de Desarrollo

Para probar la API, puedes usar:

1. [Swagger UI](https://editor.swagger.io/) - Carga el archivo `openapi.yaml`
2. [Postman](https://www.postman.com/) - Importa la colección de la API
3. [Insomnia](https://insomnia.rest/) - Importa la especificación OpenAPI

## Soporte

Para soporte técnico, contacta a:
- Email: soporte@mundoanimal.com
- Teléfono: (123) 456-7890 