# API de Disponibilidad de Horarios

Este endpoint permite consultar los horarios disponibles en un rango de fechas, considerando automáticamente las reglas de negocio como horarios de atención, cantidad máxima de citas simultáneas y tiempo mínimo de anticipación.

## Endpoint

```
POST /api/availability.php
```

## Autenticación

Se requiere autenticación JWT. Debes incluir el token en el encabezado de autorización:

```
Authorization: Bearer <tu_token>
```

Para obtener un token, utiliza el endpoint `/api/token.php`.

## Parámetros

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| start | string | Sí | Fecha y hora de inicio en formato 'YYYY-MM-DD HH:MM:SS' |
| end | string | Sí | Fecha y hora de fin en formato 'YYYY-MM-DD HH:MM:SS' |
| calendar_type | string | No | Tipo de calendario ('general', 'veterinario', 'estetico'). Si no se especifica, se consideran todos los tipos. |
| slot_duration | integer | No | Duración del slot en segundos. Por defecto es 3600 (1 hora). |

## Respuesta

### Estructura de la respuesta

```json
{
    "success": true,
    "message": "Horarios disponibles obtenidos correctamente",
    "data": [
        {
            "start": "2025-04-08 08:00:00",
            "end": "2025-04-08 09:00:00",
            "available_spots": 2
        },
        {
            "start": "2025-04-08 09:00:00",
            "end": "2025-04-08 10:00:00",
            "available_spots": 1
        }
    ]
}
```

### Campos de la respuesta

| Campo | Descripción |
|-------|-------------|
| success | Booleano que indica si la solicitud fue exitosa |
| message | Mensaje descriptivo sobre el resultado de la operación |
| data | Array con los horarios disponibles |
| data[].start | Fecha y hora de inicio del slot disponible |
| data[].end | Fecha y hora de fin del slot disponible |
| data[].available_spots | Número de espacios disponibles en ese horario (máximo 2) |

## Ejemplos

### Solicitud

```bash
curl -X POST \
  https://mundoanimal.mangomorado.com/api/availability.php \
  -H 'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...' \
  -H 'Content-Type: application/json' \
  -d '{
    "start": "2025-04-08 00:00:00",
    "end": "2025-04-10 23:59:59",
    "calendar_type": "veterinario",
    "slot_duration": 3600
}'
```

### Respuesta

```json
{
    "success": true,
    "message": "Horarios disponibles obtenidos correctamente",
    "data": [
        {
            "start": "2025-04-08 08:00:00",
            "end": "2025-04-08 09:00:00",
            "available_spots": 2
        },
        {
            "start": "2025-04-08 09:00:00",
            "end": "2025-04-08 10:00:00",
            "available_spots": 1
        },
        {
            "start": "2025-04-08 10:00:00",
            "end": "2025-04-08 11:00:00",
            "available_spots": 2
        }
    ]
}
```

## Notas Importantes

1. **Horario de atención**: El sistema solo considera horarios entre 8:00 AM y 6:00 PM, de lunes a sábado.
2. **Anticipación mínima**: No se pueden agendar citas con menos de 3 horas de anticipación.
3. **Citas simultáneas**: Solo se permiten 2 citas en el mismo horario.
4. **Duración del slot**: Por defecto, cada slot tiene una duración de 1 hora (3600 segundos).

## Manejo de Errores

| Código | Descripción |
|--------|-------------|
| 400 | Parámetros faltantes o inválidos |
| 401 | No autenticado o token inválido |
| 405 | Método no permitido |
| 500 | Error interno del servidor |

### Ejemplo de Error

```json
{
    "success": false,
    "message": "Los parámetros start y end son obligatorios",
    "data": null
}
```

## Integración con n8n

Para integrar este endpoint en n8n, utiliza un nodo "HTTP Request" con la siguiente configuración:

- **Método**: POST
- **URL**: https://mundoanimal.mangomorado.com/api/availability.php
- **Headers**: 
  - Authorization: Bearer {{$node["Login"].json["data"]["token"]}}
  - Content-Type: application/json
- **JSON Body**:
```json
{
  "start": "{{$now.format('YYYY-MM-DD')}} 00:00:00",
  "end": "{{$now.add(7, 'days').format('YYYY-MM-DD')}} 23:59:59",
  "calendar_type": "",
  "slot_duration": 3600
}
``` 