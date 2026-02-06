#!/bin/bash
set -e

echo "=============================================="
echo "       🍽️  PAMI - Iniciando servicios"
echo "=============================================="

# ============================================
# CREAR DIRECTORIOS
# ============================================
echo ""
echo "=== Creando directorios ==="

# Directorios de Laravel framework
mkdir -p /app/storage/logs
mkdir -p /app/storage/framework/cache/data
mkdir -p /app/storage/framework/sessions
mkdir -p /app/storage/framework/views
mkdir -p /app/bootstrap/cache

# ============================================
# CONFIGURAR LARAVEL
# ============================================
echo ""
echo "=== Configurando Laravel ==="

# Ejecutar migraciones
php /app/artisan migrate --force 2>/dev/null || echo "⚠️ Migraciones fallaron o ya estaban aplicadas"

# ============================================
# INICIAR NGINX Y PHP-FPM
# ============================================
echo ""
echo "=== Iniciando Nginx y PHP-FPM ==="
node /assets/scripts/prestart.mjs /assets/nginx.template.conf /nginx.conf
php-fpm -y /assets/php-fpm.conf &
nginx -c /nginx.conf &

# ============================================
# RESUMEN
# ============================================
echo ""
echo "=============================================="
echo "       ✅ PAMI iniciado correctamente"
echo "=============================================="
echo "   - PHP-FPM: Ejecutándose"
echo "   - Nginx: Ejecutándose"
echo ""
echo "=============================================="

wait
