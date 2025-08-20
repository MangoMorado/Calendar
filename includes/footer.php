    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Mundo Animal - Todos los derechos reservados</p>
        </div>
    </footer>
    
    <!-- Scripts comunes -->
    <script src="assets/js/helpers/ios-fixes.js"></script>
    <!-- Cargar utilidades de UI y exponer funciones globales para scripts inline -->
    <script type="module">
        import * as ui from './assets/js/modules/ui.js';
        if (ui && typeof ui.showNotification === 'function') {
            window.showNotification = window.showNotification || ui.showNotification;
        }
        if (ui && typeof ui.limpiarBackdrops === 'function') {
            window.limpiarBackdrops = window.limpiarBackdrops || ui.limpiarBackdrops;
        }
    </script>
    <?php if (isAuthenticated()): ?>
    <!-- Scripts de autenticación JWT -->
    <script src="assets/js/helpers/api.js"></script>
    <script src="assets/js/helpers/auth.js"></script>
    <?php endif; ?>
    <script src="assets/js/header.js"></script>
    
    <?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html> 