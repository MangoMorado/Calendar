# API de Disponibilidad de Horarios para Mundo Animal

## Resumen

Hemos implementado un nuevo sistema para gestionar la disponibilidad de horarios en el calendario de Mundo Animal. La solución consta de:

1. **Nuevo endpoint de API** (`/api/availability.php`) que calcula automáticamente los horarios disponibles
2. **Script de prueba** (`test/test_availability.php`) para verificar el funcionamiento
3. **Documentación** para integrar con el flujo de n8n existente
4. **Instrucciones** para actualizar el bot MundiBot

## Problema resuelto

El flujo de n8n actual tenía un problema: al consultar la agenda, devolvía las citas ocupadas, pero no calculaba correctamente los espacios disponibles. Esto causaba confusión en MundiBot y podía llevar a agendar citas en horarios no disponibles o confundir al usuario final.

Con la nueva implementación, el cálculo de disponibilidad se realiza directamente en el backend, siguiendo todas las reglas de negocio:
- Horario de atención de 8:00 a 18:00, lunes a sábado
- Máximo 2 citas simultáneas
- No permitir citas con menos de 3 horas de anticipación
- Respetar la duración de cada cita (1 hora por defecto)

## Componentes

### 1. Nuevo Endpoint de API

Archivo: `/api/availability.php`

Este endpoint:
- Recibe parámetros de rango de fechas y tipo de calendario
- Consulta las citas existentes en ese rango
- Calcula automáticamente qué slots están disponibles
- Devuelve un JSON con los horarios disponibles, incluyendo cuántos espacios hay en cada slot

### 2. Script de prueba

Archivo: `/test/test_availability.php`

Este script:
- Obtiene un token de autenticación
- Realiza una solicitud al nuevo endpoint
- Muestra los resultados formateados
- Sirve como ejemplo de uso del API

### 3. Documentación de API

Archivo: `/docs/api/availability.md`

Documentación detallada sobre:
- Cómo usar el endpoint
- Parámetros requeridos y opcionales
- Estructura de la respuesta
- Ejemplos de solicitud y respuesta
- Notas importantes y manejo de errores

### 4. Instrucciones para n8n

Archivo: `/docs/n8n/updated_agent_calendar.md`

Guía paso a paso para:
- Actualizar el nodo HTTP en n8n que consulta la agenda
- Modificar el prompt del agente para procesar correctamente la nueva respuesta
- Probar el flujo actualizado

## Cómo probar

1. **Verificar el nuevo endpoint**:
   ```bash
   php test/test_availability.php
   ```

2. **Actualizar el flujo de n8n** siguiendo las instrucciones en `/docs/n8n/updated_agent_calendar.md`

3. **Probar el flujo completo** iniciando una conversación con MundiBot y preguntando por disponibilidad de horarios

## Ventajas de la nueva implementación

1. **Separación de responsabilidades**: El cálculo de disponibilidad se realiza en el backend, no en el flujo de n8n
2. **Mayor precisión**: Se aplican todas las reglas de negocio de manera consistente
3. **Mejor experiencia de usuario**: MundiBot puede mostrar directamente los horarios disponibles
4. **Mantenibilidad**: Si cambian las reglas de negocio, solo hay que modificar el endpoint, no el flujo de n8n
5. **Reutilización**: El mismo endpoint puede ser utilizado por otras aplicaciones o interfaces

## Próximos pasos

1. Monitorear el uso del nuevo endpoint para verificar su rendimiento
2. Considerar agregar opciones adicionales como:
   - Filtrado por especialista/veterinario
   - Duración variable de citas según el tipo de servicio
   - Bloqueo de horarios para mantenimiento o descanso

## Contacto

Si tienes preguntas o necesitas ayuda para implementar estos cambios, por favor contacta al equipo de desarrollo. 