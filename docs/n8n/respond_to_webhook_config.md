# 📡 Configuración del Nodo "Respond to Webhook" en n8n

## 🎯 Objetivo
Configurar el nodo "Respond to Webhook" para que devuelva respuestas JSON consistentes al sistema, evitando errores de parsing y mejorando la comunicación entre n8n y tu aplicación.

## 📋 Pasos para Configurar

### Paso 1: Agregar el Nodo "Respond to Webhook"

1. **En tu workflow de n8n**, busca el nodo "Respond to Webhook" en la biblioteca de nodos
2. **Arrastra el nodo** al final de tu workflow (después del nodo "Wait" o el último nodo de procesamiento)
3. **Conecta el nodo** desde el último nodo de tu rama principal

### Paso 2: Configuración Básica del Nodo

#### Configuración General:
- **Node Name**: `Respond to Webhook`
- **HTTP Status Code**: `200`
- **Response Headers**: 
  - `Content-Type`: `application/json`

#### Response Body (JSON):
```json
{
  "success": true,
  "message": "Difusión procesada correctamente",
  "data": {
    "broadcast_id": "={{ $('Normalizar Datos').item.json.broadcast_id }}",
    "total_contacts": "={{ $('Normalizar Datos').item.json.contactos.length }}",
    "status": "completed",
    "processed_at": "={{ new Date().toISOString() }}",
    "workflow_execution_id": "={{ $executionId }}"
  }
}
```

### Paso 3: Configuración Avanzada (Opcional)

#### Para Workflows con Diferentes Tipos de Contenido:
```json
{
  "success": true,
  "message": "Difusión procesada correctamente",
  "data": {
    "broadcast_id": "={{ $('Normalizar Datos').item.json.broadcast_id }}",
    "total_contacts": "={{ $('Normalizar Datos').item.json.contactos.length }}",
    "content_type": "={{ $('Normalizar Datos').item.json.mediatype }}",
    "status": "completed",
    "processed_at": "={{ new Date().toISOString() }}",
    "workflow_execution_id": "={{ $executionId }}"
  }
}
```

#### Para Workflows con Información de Envío:
```json
{
  "success": true,
  "message": "Difusión procesada correctamente",
  "data": {
    "broadcast_id": "={{ $('Normalizar Datos').item.json.broadcast_id }}",
    "total_contacts": "={{ $('Normalizar Datos').item.json.contactos.length }}",
    "sent_count": "={{ $('Split por Contacto').all().length }}",
    "failed_count": 0,
    "status": "completed",
    "processed_at": "={{ new Date().toISOString() }}",
    "workflow_execution_id": "={{ $executionId }}"
  }
}
```

## 🚨 Manejo de Errores

### Paso 4: Agregar Respuesta de Error

1. **Agrega un nodo "Error Trigger"** en cada rama donde pueda ocurrir un error
2. **Conecta el Error Trigger** a otro nodo "Respond to Webhook"
3. **Configura la respuesta de error**:

#### Configuración del Nodo de Error:
- **Node Name**: `Respond to Webhook (Error)`
- **HTTP Status Code**: `400` o `500`
- **Response Headers**: 
  - `Content-Type`: `application/json`

#### Response Body para Error:
```json
{
  "success": false,
  "message": "Error al procesar la difusión",
  "error": "={{ $json.error || $json.message || 'Error desconocido' }}",
  "data": {
    "broadcast_id": "={{ $('Normalizar Datos').item.json.broadcast_id || 'N/A' }}",
    "failed_at": "={{ new Date().toISOString() }}",
    "workflow_execution_id": "={{ $executionId }}"
  }
}
```

## 🔧 Variables Disponibles en n8n

### Variables del Webhook:
- `$json.body.broadcast_id` - ID de la difusión
- `$json.body.contactos` - Array de contactos
- `$json.body.texto` - Mensaje de texto
- `$json.body.imagen_base64` - Imagen en base64
- `$json.body.mediatype` - Tipo de medio

### Variables del Sistema:
- `$executionId` - ID de ejecución del workflow
- `$workflowId` - ID del workflow
- `$nodeName` - Nombre del nodo actual
- `$now` - Timestamp actual

### Variables de Nodos Anteriores:
- `$('Nombre del Nodo').item.json` - Datos del nodo específico
- `$('Nombre del Nodo').all()` - Todos los items del nodo

## 📊 Estructura Recomendada del Workflow

```
Webhook → Validación → Switch por Tipo → Normalización → Split → Envío → Wait → Respond to Webhook
                ↓
            Error Trigger → Respond to Webhook (Error)
```

## ✅ Verificación de la Configuración

### 1. Prueba Directa en n8n:
1. Haz clic en el nodo "Respond to Webhook"
2. Haz clic en "Test step"
3. Verifica que la respuesta sea JSON válido
4. Confirma que el HTTP Status Code sea 200

### 2. Prueba desde tu Sistema:
1. Envía una difusión desde tu aplicación
2. Verifica que no aparezca el error "Error al enviar la difusión"
3. Revisa los logs de n8n para confirmar el procesamiento
4. Verifica que el estado en la base de datos sea correcto

## 🎨 Ejemplos de Respuestas

### Respuesta de Éxito (Texto):
```json
{
  "success": true,
  "message": "Difusión procesada correctamente",
  "data": {
    "broadcast_id": "12345",
    "total_contacts": 50,
    "content_type": "text",
    "status": "completed",
    "processed_at": "2024-01-15T10:30:00.000Z",
    "workflow_execution_id": "abc123def456"
  }
}
```

### Respuesta de Éxito (Imagen):
```json
{
  "success": true,
  "message": "Difusión procesada correctamente",
  "data": {
    "broadcast_id": "12346",
    "total_contacts": 25,
    "content_type": "image",
    "status": "completed",
    "processed_at": "2024-01-15T10:35:00.000Z",
    "workflow_execution_id": "abc123def457"
  }
}
```

### Respuesta de Error:
```json
{
  "success": false,
  "message": "Error al procesar la difusión",
  "error": "No se pudo conectar con WhatsApp",
  "data": {
    "broadcast_id": "12347",
    "failed_at": "2024-01-15T10:40:00.000Z",
    "workflow_execution_id": "abc123def458"
  }
}
```

## 🔍 Troubleshooting

### Problema: "No se puede tener múltiples respuestas"
**Solución**: Asegúrate de que solo haya un nodo "Respond to Webhook" por rama de ejecución.

### Problema: "Error de sintaxis JSON"
**Solución**: Verifica que todas las variables n8n estén correctamente formateadas con `={{ }}`.

### Problema: "Variable no encontrada"
**Solución**: Asegúrate de que el nombre del nodo en la variable coincida exactamente con el nombre del nodo en tu workflow.

## 📝 Notas Importantes

- **Solo una respuesta por ejecución**: n8n solo permite una respuesta por ejecución de workflow
- **Variables dinámicas**: Usa las variables n8n para datos dinámicos
- **Headers obligatorios**: Siempre incluye `Content-Type: application/json`
- **Logging**: El sistema registrará la respuesta completa para debugging 