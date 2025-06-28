<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar autenticación
if (!isAuthenticated()) {
    header('Location: ../login.php');
    exit;
}

// Obtener todos los archivos de prueba
$testFiles = glob(__DIR__ . '/test_*.php');
$testFiles = array_map('basename', $testFiles);
// Agregar el test de imágenes si no está
if (!in_array('test_broadcast_image.php', $testFiles)) {
    $testFiles[] = 'test_broadcast_image.php';
}
sort($testFiles);

// Categorizar los archivos
$categories = [
    'Sesiones' => [],
    'API y Endpoints' => [],
    'Base de Datos' => [],
    'Importación de Contactos' => [],
    'Envío de Mensajes' => [],
    'Otros' => []
];

// Categorizar el test de imagen
foreach ($testFiles as $file) {
    if ($file === 'test_broadcast_image.php') {
        $categories['Envío de Mensajes'][] = $file;
    } elseif (strpos($file, 'session') !== false) {
        $categories['Sesiones'][] = $file;
    } elseif (strpos($file, 'endpoint') !== false || strpos($file, 'api') !== false) {
        $categories['API y Endpoints'][] = $file;
    } elseif (strpos($file, 'db') !== false) {
        $categories['Base de Datos'][] = $file;
    } elseif (strpos($file, 'import') !== false) {
        $categories['Importación de Contactos'][] = $file;
    } elseif (strpos($file, 'send') !== false || strpos($file, 'broadcast') !== false) {
        $categories['Envío de Mensajes'][] = $file;
    } else {
        $categories['Otros'][] = $file;
    }
}

// Eliminar categorías vacías
$categories = array_filter($categories, function($files) {
    return !empty($files);
});

// Generar lista de endpoints para cada test
$apiTestEndpoints = [];
foreach ($testFiles as $file) {
    $apiTestEndpoints[$file] = (strpos($file, 'test_broadcast_image.php') !== false)
        ? '../api/test/test_broadcast_image.php'
        : '../api/test/' . $file;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tests - Calendario</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/modules/base.css">
    <link rel="stylesheet" href="../assets/css/modules/layout.css">
    <link rel="stylesheet" href="../assets/css/modules/utilities.css">
    <style>
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .test-category {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }
        
        .test-category h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
        }
        
        .test-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .test-item {
            margin-bottom: 0.75rem;
        }
        
        .test-link {
            display: block;
            padding: 0.75rem 1rem;
            background: var(--bg-secondary);
            border-radius: var(--border-radius-sm);
            color: var(--text-primary);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }
        
        .test-link:hover {
            background: var(--primary-color);
            color: white;
            transform: translateX(5px);
        }
        
        .test-description {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-top: 0.25rem;
        }
        
        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .btn-secondary {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }
        
        .btn-secondary:hover {
            background: var(--border-color);
        }
        
        .led-indicator {
            display: inline-block;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            margin-right: 8px;
            vertical-align: middle;
            border: 2px solid #ccc;
            background: #eee;
        }
        .led-green { background: #4caf50 !important; border-color: #4caf50 !important; }
        .led-yellow { background: #ffc107 !important; border-color: #ffc107 !important; }
        .led-red { background: #f44336 !important; border-color: #f44336 !important; }
        .led-gray { background: #eee !important; border-color: #ccc !important; }
        .run-all-btn { margin-bottom: 1.5rem; }
        .test-status-msg { font-size: 0.9em; margin-top: 0.25em; color: #555; }
        .led-global { width: 22px; height: 22px; margin-right: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="header-content">
                <h1>🧪 Tests del Sistema</h1>
                <div class="header-actions">
                    <a href="../index.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Inicio
                    </a>
                    <a href="../tools/" class="btn btn-secondary">
                        <i class="fas fa-tools"></i> Herramientas
                    </a>
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="content-header">
                <h2>Archivos de Prueba Disponibles</h2>
                <p>Selecciona un test para ejecutarlo y verificar la funcionalidad del sistema.</p>
            </div>

            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                <span id="global-led" class="led-indicator led-gray led-global" title="Estado global"></span>
                <button id="run-all-tests" class="btn btn-primary run-all-btn">Correr todos los tests</button>
                <span id="global-status-msg" class="test-status-msg"></span>
            </div>

            <div class="test-grid">
                <?php foreach ($categories as $category => $files): ?>
                    <div class="test-category">
                        <h3><?php echo htmlspecialchars($category); ?></h3>
                        <ul class="test-list">
                            <?php foreach ($files as $file): ?>
                                <li class="test-item">
                                    <span id="led-<?php echo htmlspecialchars($file); ?>" class="led-indicator led-gray" title="Sin ejecutar"></span>
                                    <a href="/test/<?php echo htmlspecialchars($file); ?>" class="test-link" target="_blank">
                                        <strong><?php echo htmlspecialchars($file); ?></strong>
                                    </a>
                                    <div id="msg-<?php echo htmlspecialchars($file); ?>" class="test-status-msg"></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="content-footer">
                <div class="alert alert-info">
                    <strong>💡 Consejo:</strong> Los tests se abren en una nueva pestaña para que puedas ejecutar varios simultáneamente.
                </div>
            </div>
        </main>
    </div>

    <script>
    // Endpoints de API para cada test
    const apiTestEndpoints = <?php echo json_encode($apiTestEndpoints); ?>;
    const testFiles = Object.keys(apiTestEndpoints);

    function setLed(id, status) {
        const led = document.getElementById(id);
        if (!led) return;
        led.classList.remove('led-green', 'led-yellow', 'led-red', 'led-gray');
        if (status === 'ok') led.classList.add('led-green');
        else if (status === 'warning') led.classList.add('led-yellow');
        else if (status === 'error') led.classList.add('led-red');
        else led.classList.add('led-gray');
    }

    function setMsg(id, msg) {
        const el = document.getElementById(id);
        if (el) el.textContent = msg;
    }

    function setGlobalLed(status) {
        setLed('global-led', status);
        const msg = document.getElementById('global-status-msg');
        if (status === 'ok') msg.textContent = 'Todos los tests pasaron correctamente.';
        else if (status === 'warning') msg.textContent = 'Algunos tests tienen advertencias.';
        else if (status === 'error') msg.textContent = 'Al menos un test falló.';
        else msg.textContent = '';
    }

    document.getElementById('run-all-tests').addEventListener('click', async function() {
        setGlobalLed('');
        let globalStatus = 'ok';
        let pending = testFiles.length;
        for (const file of testFiles) {
            setLed('led-' + file, '');
            setMsg('msg-' + file, 'Ejecutando...');
            fetch(apiTestEndpoints[file])
                .then(r => r.json())
                .then(data => {
                    setLed('led-' + file, data.status);
                    setMsg('msg-' + file, data.message);
                    if (data.status === 'error') globalStatus = 'error';
                    else if (data.status === 'warning' && globalStatus !== 'error') globalStatus = 'warning';
                })
                .catch(() => {
                    setLed('led-' + file, 'error');
                    setMsg('msg-' + file, 'No se pudo ejecutar el test.');
                    globalStatus = 'error';
                })
                .finally(() => {
                    pending--;
                    if (pending === 0) setGlobalLed(globalStatus);
                });
        }
    });
    </script>
</body>
</html> 