    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Mundo Animal - Todos los derechos reservados</p>
        </div>
    </footer>
    
    <!-- Scripts comunes -->
    <script src="assets/js/helpers/ios-fixes.js"></script>
    <?php if (isAuthenticated()): ?>
    <!-- Scripts de autenticación JWT -->
    <script src="assets/js/helpers/api.js"></script>
    <script src="assets/js/helpers/auth.js"></script>
    <?php endif; ?>
    <script src="assets/js/header.js"></script>
    
    <?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html> 