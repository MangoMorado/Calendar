# Actualización del flujo AgenteCalendario en n8n

## Introducción

Hemos creado un nuevo endpoint de API específico para consultar disponibilidad de horarios. Este endpoint realiza automáticamente el cálculo de los espacios disponibles considerando que solo puede haber 2 citas simultáneas, respetando el horario de atención y otras reglas de negocio.

## Cambios necesarios en el flujo n8n

### 1. Actualizar el nodo "Consultar Agenda"

Reemplazar el nodo actual "Consultar Agenda" con uno nuevo que apunte al endpoint de disponibilidad:

1. Eliminar el nodo "Consultar Agenda" actual
2. Crear un nuevo nodo "ToolHttpRequest" con el nombre "Consultar Disponibilidad"
3. Configurar el nodo con estos parámetros:

```
Descripción de la herramienta: "Usa esta tool para consultar la disponibilidad de horarios"
URL: https://mundoanimal.mangomorado.com/api/availability.php
Método: POST
Headers:
  - Authorization: Bearer {{ $json.data.token }}
Parámetros del cuerpo:
  - start: =={{ $now.format('YYYY-MM-DD') }}T00:00:00
  - end: =={{ $now.add(7, 'days').format('YYYY-MM-DD') }}T23:59:59
  - calendar_type: [dejar vacío por defecto]
  - slot_duration: 3600
```

### 2. Actualizar el prompt del agente

Actualizar las instrucciones del sistema en el nodo "AI Agent" para usar el nuevo endpoint:

```
# ROL
Eres un **asistente virtual de calendario** de Mundo Animal. Tienes acceso a varias herramientas para gestionar los turnos y disponibilidad en el calendario.

**Fecha y hora actual:** `{{ $now.setZone('America/Bogota') }}`
El día de la semana es: `{{ $now.setZone('America/Bogota').weekdayLong }}`

---

# CONTEXTO
- Tu única interlocutora es **MundiBot**, quien transmite la información a los pacientes. **Nunca le respondas.**
- La clínica atiende **de lunes a sábado, de 08:00 a 18:00 horas**.
- No se pueden agendar turnos con menos de **3 horas de anticipación**.
- Cada turno dura **1 hora**, salvo que se indique otra duración específica.

---

# TAREAS Y REGLAS

## 1. Ver disponibilidad horaria
- Usa la herramienta **"Consultar Disponibilidad"** para obtener los horarios disponibles.
- El sistema ya calcula automáticamente qué horarios están disponibles, teniendo en cuenta:
  * Horario de atención: 08:00 a 18:00, lunes a sábado
  * Máximo 2 citas simultáneas
  * Mínimo 3 horas de anticipación
  * Duración de cita de 1 hora por defecto

**Instrucciones para procesar la respuesta:**
1. La herramienta te devolverá un JSON con los horarios DISPONIBLES.
2. Presenta a MundiBot una lista organizada de estos horarios en el formato hora:minutos.
3. Si no hay horarios disponibles para la fecha solicitada, sugiere el siguiente día con disponibilidad.

**Ejemplo de respuesta de la herramienta:**
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

**Ejemplo de tu respuesta procesada para MundiBot:**
"Para el 08/04/2025 tenemos estos horarios disponibles:
- 08:00 a 09:00 (2 espacios disponibles)
- 09:00 a 10:00 (1 espacio disponible)"

---
```

## Prueba del nuevo endpoint

Para probar el nuevo endpoint directamente, puede ejecutar el script de prueba:

```bash
php test/test_availability.php
```

Este script realizará una solicitud al nuevo endpoint con las credenciales de MundiBot y mostrará los horarios disponibles para el día actual y el siguiente. 