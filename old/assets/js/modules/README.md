# Módulos ES6 del Calendario

Este directorio contiene todos los módulos JavaScript modernos basados en ES6 para el funcionamiento del calendario.

## Estructura de Módulos

- **calendar.js**: Componente principal del calendario, implementación con FullCalendar.
- **events.js**: Manejadores de eventos de usuario (clic, arrastre, redimensionamiento).
- **modal.js**: Gestión del modal para crear y editar citas.
- **appointments.js**: Componente de próximas citas y manejo de listados.
- **utils.js**: Funciones utilitarias para formateo de fechas y operaciones comunes.
- **ui.js**: Componentes de interfaz como notificaciones y modales.

## Flujo de Ejecución

1. El archivo `app.js` es el punto de entrada principal que utiliza módulos ES6.
2. Cada módulo importa solo lo que necesita de otros módulos.
3. El estado y la configuración se pasan explícitamente entre módulos.
4. Los eventos del calendario se manejan siguiendo un patrón modular.

## Arquitectura

La nueva arquitectura utiliza ES6 modules con las siguientes características:

- **Importaciones/Exportaciones Explícitas**: Solo se expone lo necesario.
- **Sin Variables Globales**: El estado se pasa entre módulos.
- **Referencias Centralizadas**: Los elementos DOM se gestionan centralmente.
- **Manejo de Errores Mejorado**: Mejor gestión de errores y logging.

## Extensibilidad

Para añadir nuevas funcionalidades:

1. Crear o extender el módulo apropiado.
2. Exportar solo las funciones necesarias.
3. Importar en `app.js` u otros módulos según sea necesario.

## Ventajas sobre la arquitectura anterior

- **Mejor Modularidad**: Código más organizado y mantenible.
- **Menos Acoplamiento**: Los módulos dependen menos entre sí.
- **Mejor Rendimiento**: Solo se carga lo necesario cuando se necesita.
- **Mejor Testabilidad**: Funciones puras y con menos efectos secundarios.

## Dependencias Externas

- FullCalendar 6.1.17 (cargado globalmente)
- Sin dependencia de jQuery para la lógica principal
- Bootstrap para algunos componentes UI 