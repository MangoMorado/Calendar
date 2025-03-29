# Módulos JavaScript del Calendario

Este directorio contiene todos los módulos JavaScript necesarios para el funcionamiento del calendario.

## Estructura de Archivos

- **calendar-init.js**: Configuración e inicialización del calendario FullCalendar.
- **event-handlers.js**: Funciones para manejar eventos del calendario (selección, clic, arrastre, redimensionamiento).
- **modal-handlers.js**: Manejadores de eventos para el modal de citas (crear, editar, eliminar).
- **upcoming-appointments.js**: Funciones para mostrar y gestionar las próximas citas.
- **utils.js**: Funciones de utilidad como formateo de fechas, mensajes, etc.
- **main.js**: Archivo principal que carga e inicializa todos los módulos.

## Flujo de Ejecución

1. El archivo `scripts.php` carga las dependencias y define variables globales.
2. Se cargan todos los módulos JavaScript en el orden correcto.
3. El archivo `main.js` inicializa todos los componentes cuando el DOM está listo.
4. Los eventos de usuario se manejan en sus respectivos módulos.

## Variables Globales

- `eventsJson`: Contiene los eventos del calendario en formato JSON.
- `calendarSettings`: Configuración del calendario (horarios, duración).
- `currentCalendarType`: Tipo de calendario actual (general, estético, veterinario).
- `calendar`: Instancia del calendario FullCalendar (definida en `calendar-init.js`).

## Dependencias Externas

- FullCalendar 6.1.15
- jQuery (para AJAX y manipulación del DOM)
- Bootstrap (para componentes UI) 