# Bibliotecas Locales

Este directorio contiene las bibliotecas externas utilizadas por la aplicación. Algunas bibliotecas se cargan desde CDN, pero también se mantienen copias locales como fallback en caso de que la carga desde CDN falle.

## FullCalendar 6.1.15

Si necesitas añadir la versión local de FullCalendar, sigue estos pasos:

1. Descarga FullCalendar 6.1.15 desde la [página de releases](https://github.com/fullcalendar/fullcalendar/releases/tag/v6.1.15)
2. Crea el directorio `assets/lib/fullcalendar-6.1.15/` si no existe
3. Copia los siguientes archivos a ese directorio:
   - `index.global.min.js` - El archivo principal de FullCalendar
   - Crea un subdirectorio `locales` y copia dentro `es.global.min.js`

Alternativamente, puedes descargar directamente los archivos desde estos CDNs:
- FullCalendar Core: `https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js`
- Locales (español): `https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/locales/es.global.min.js`
- CSS: `https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css`

## Notas Importantes

- La aplicación intentará cargar FullCalendar desde CDN primero. Solo si falla, usará la versión local.
- Si la aplicación muestra errores de carga, verifica en la consola del navegador si hay errores 404 relacionados con FullCalendar y asegúrate de que los archivos locales existen.
- A diferencia de lo que dice la documentación oficial, FullCalendar 6 requiere un archivo CSS separado para funcionar correctamente en algunas implementaciones. Por eso hemos incluido el CSS específico en cada página.
- La funcionalidad de interacción (arrastrar y soltar eventos) está incluida en el paquete principal de FullCalendar 6, por lo que no es necesario cargar un script adicional. 