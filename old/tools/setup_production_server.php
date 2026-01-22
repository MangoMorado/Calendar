<?php

/**
 * Script de Configuración del Servidor de Producción
 *
 * Este script ayuda a configurar el servidor de producción para el CI/CD
 *
 * USO:
 * 1. Subir este archivo al servidor de producción
 * 2. Ejecutar: php setup_production_server.php
 * 3. Seguir las instrucciones en pantalla
 */
echo "🚀 CONFIGURACIÓN DEL SERVIDOR DE PRODUCCIÓN\n";
echo "==========================================\n\n";

// Verificar si estamos en producción
if (php_sapi_name() === 'cli') {
    echo "✅ Ejecutando desde línea de comandos\n";
} else {
    echo "❌ Este script debe ejecutarse desde línea de comandos\n";
    exit(1);
}

// Verificar sistema operativo
$os = php_uname('s');
echo "🖥️  Sistema operativo: $os\n";

// Verificar PHP
echo '🐘 Versión de PHP: '.PHP_VERSION."\n";

// Verificar extensiones necesarias
$required_extensions = ['mysqli', 'json', 'curl', 'openssl'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (! extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

if (! empty($missing_extensions)) {
    echo '❌ Extensiones faltantes: '.implode(', ', $missing_extensions)."\n";
    echo "💡 Instala las extensiones faltantes antes de continuar\n";
    exit(1);
} else {
    echo "✅ Todas las extensiones requeridas están disponibles\n";
}

// Verificar directorios
$directories = [
    '/var/www/html' => 'Directorio web principal',
    '/var/www/backups' => 'Directorio de backups',
    '/tmp' => 'Directorio temporal',
];

echo "\n📁 Verificando directorios:\n";
foreach ($directories as $dir => $description) {
    if (is_dir($dir)) {
        echo "✅ $description: $dir\n";
    } else {
        echo "❌ $description: $dir (NO EXISTE)\n";
        echo "💡 Creando directorio...\n";
        if (mkdir($dir, 0755, true)) {
            echo "✅ Directorio creado exitosamente\n";
        } else {
            echo "❌ Error al crear directorio\n";
        }
    }
}

// Verificar permisos
echo "\n🔐 Verificando permisos:\n";
$web_dir = '/var/www/html';
if (is_dir($web_dir)) {
    $perms = substr(sprintf('%o', fileperms($web_dir)), -4);
    echo "📁 Permisos de $web_dir: $perms\n";

    if ($perms !== '0755') {
        echo "💡 Cambiando permisos a 0755...\n";
        if (chmod($web_dir, 0755)) {
            echo "✅ Permisos cambiados exitosamente\n";
        } else {
            echo "❌ Error al cambiar permisos\n";
        }
    }
}

// Verificar servicios
echo "\n🔧 Verificando servicios:\n";
$services = ['apache2', 'nginx', 'php8.1-fpm', 'mysql', 'mariadb'];

foreach ($services as $service) {
    $status = shell_exec("systemctl is-active $service 2>/dev/null");
    if (trim($status) === 'active') {
        echo "✅ $service: Activo\n";
    } else {
        echo "❌ $service: Inactivo o no encontrado\n";
    }
}

// Crear usuario para CI/CD
echo "\n👤 Configurando usuario para CI/CD:\n";
$ci_user = 'ci-deploy';
$ci_group = 'www-data';

// Verificar si el usuario existe
$user_exists = shell_exec("id $ci_user 2>/dev/null");
if (empty($user_exists)) {
    echo "💡 Creando usuario $ci_user...\n";
    $create_user = "useradd -m -s /bin/bash -G $ci_group $ci_user";
    if (system($create_user) === false) {
        echo "❌ Error al crear usuario\n";
    } else {
        echo "✅ Usuario creado exitosamente\n";
    }
} else {
    echo "✅ Usuario $ci_user ya existe\n";
}

// Configurar clave SSH
echo "\n🔑 Configurando acceso SSH:\n";
$ssh_dir = "/home/$ci_user/.ssh";
if (! is_dir($ssh_dir)) {
    mkdir($ssh_dir, 0700, true);
    chown($ssh_dir, "$ci_user:$ci_group");
}

$authorized_keys = "$ssh_dir/authorized_keys";
if (! file_exists($authorized_keys)) {
    touch($authorized_keys);
    chmod($authorized_keys, 0600);
    chown($authorized_keys, "$ci_user:$ci_group");
    echo "✅ Archivo authorized_keys creado\n";
} else {
    echo "✅ Archivo authorized_keys ya existe\n";
}

echo "\n📝 INSTRUCCIONES PARA COMPLETAR LA CONFIGURACIÓN:\n";
echo "================================================\n";
echo "1. 🔑 Genera una clave SSH para CI/CD:\n";
echo "   ssh-keygen -t rsa -b 4096 -C 'ci-deploy@github-actions'\n\n";

echo "2. 📋 Copia la clave pública a authorized_keys:\n";
echo "   cat ~/.ssh/id_rsa.pub >> /home/$ci_user/.ssh/authorized_keys\n\n";

echo "3. 🔐 Copia la clave PRIVADA a GitHub Secrets como SSH_PRIVATE_KEY\n\n";

echo "4. 🗄️  Configura las variables de base de datos en GitHub:\n";
echo "   - DB_HOST_PROD\n";
echo "   - DB_USER_PROD\n";
echo "   - DB_PASS_PROD\n";
echo "   - DB_NAME_PROD\n\n";

echo "5. 🌐 Configura las variables del servidor en GitHub:\n";
echo "   - SSH_HOST (IP o dominio del servidor)\n";
echo "   - SSH_USER ($ci_user)\n\n";

echo "6. 🚀 Haz push a la rama main para probar el despliegue automático\n\n";

echo "✅ Configuración del servidor completada\n";
echo "🎯 El servidor está listo para recibir despliegues automáticos\n";
