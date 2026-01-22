<?php
// Detectar si se está llamando desde un endpoint (API, AJAX, CLI o variable global)
$isApi = (isset($GLOBALS['__TEST_API_MODE__']) && $GLOBALS['__TEST_API_MODE__'] === true)
      || isset($_SERVER['HTTP_X_REQUESTED_WITH'])
      || php_sapi_name() === 'cli'
      || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/auth.php';

// --- Lógica de test principal ---
$testResult = [
    'sesiones_permanentes' => false,
    'autenticacion' => false,
    'usuario' => null,
    'errores' => [],
    'advertencias' => [],
    'detalles' => [],
];

// 1. Verificar sesiones permanentes
$userIds = [1, 4, 5, 6];
$sessions = 'SELECT us.*, u.name, u.email FROM user_sessions us JOIN users u ON us.user_id = u.id WHERE us.user_id IN ('.implode(',', $userIds).') ORDER BY us.user_id';
$result = mysqli_query($conn, $sessions);
if (mysqli_num_rows($result) > 0) {
    $testResult['sesiones_permanentes'] = true;
    $testResult['detalles'][] = 'Se encontraron sesiones permanentes en la base de datos.';
} else {
    $testResult['errores'][] = 'No se encontraron sesiones permanentes. Ejecuta primero create_permanent_sessions.php';
}

// 2. Probar autenticación
$userIdToTest = 1;
$userSql = 'SELECT id, name, email, role FROM users WHERE id = ?';
$stmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($stmt, 'i', $userIdToTest);
mysqli_stmt_execute($stmt);
$resultUser = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($resultUser);
if ($user) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['user'] = $user;
    if (isAuthenticated()) {
        $testResult['autenticacion'] = true;
        $testResult['usuario'] = $user;
        $testResult['detalles'][] = 'Usuario autenticado correctamente.';
    } else {
        $testResult['errores'][] = 'No se pudo autenticar al usuario.';
    }
} else {
    $testResult['errores'][] = 'Usuario de prueba no encontrado.';
}

// --- Respuesta para API ---
if ($isApi) {
    if ($testResult['sesiones_permanentes']) {
        echo 'ok';
    } else {
        echo 'error';
    }
    exit;
}

// --- Página HTML para navegador ---
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test de Sesiones Permanentes</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/modules/base.css">
    <link rel="stylesheet" href="../assets/css/modules/layout.css">
    <link rel="stylesheet" href="../assets/css/modules/utilities.css">
    <style>
        body { background: #f8f9fa; }
        .test-summary { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px #0001; padding: 2rem; max-width: 700px; margin: 2rem auto; }
        .test-status-ok { color: #4caf50; font-weight: bold; }
        .test-status-error { color: #f44336; font-weight: bold; }
        .test-status-warning { color: #ffc107; font-weight: bold; }
        .test-section { margin-bottom: 1.5rem; }
        .test-section h2 { margin-bottom: 0.5rem; }
        .test-details { background: #f5f5f5; border-radius: 5px; padding: 1rem; }
        .test-list { margin: 0.5rem 0 0 1.5rem; }
    </style>
</head>
<body>
    <div class="test-summary">
        <h1>🧪 Test de Sesiones Permanentes</h1>
        <div class="test-section">
            <h2>Resumen</h2>
            <p>
                Estado general:
                <?php if ($testResult['sesiones_permanentes']) { ?>
                    <span class="test-status-ok">✔️ OK</span>
                <?php } else { ?>
                    <span class="test-status-error">❌ Error</span>
                <?php } ?>
            </p>
        </div>
        <div class="test-section">
            <h2>Sesiones Permanentes</h2>
            <?php if ($testResult['sesiones_permanentes']) { ?>
                <div class="test-status-ok">Se encontraron sesiones permanentes en la base de datos.</div>
            <?php } else { ?>
                <div class="test-status-error">No se encontraron sesiones permanentes. Ejecuta primero <code>create_permanent_sessions.php</code></div>
            <?php } ?>
        </div>
        <div class="test-section">
            <h2>Autenticación</h2>
            <?php if ($testResult['autenticacion']) { ?>
                <div class="test-status-ok">Usuario autenticado correctamente.</div>
                <div class="test-details">
                    <strong>Usuario:</strong> <?php echo htmlspecialchars($testResult['usuario']['name']); ?><br>
                    <strong>Email:</strong> <?php echo htmlspecialchars($testResult['usuario']['email']); ?><br>
                    <strong>Rol:</strong> <?php echo htmlspecialchars($testResult['usuario']['role']); ?><br>
                </div>
            <?php } else { ?>
                <div class="test-status-error">No se pudo autenticar al usuario de prueba.</div>
            <?php } ?>
        </div>
        <?php if (! empty($testResult['errores'])) { ?>
        <div class="test-section">
            <h2>Errores</h2>
            <ul class="test-list">
                <?php foreach ($testResult['errores'] as $err) { ?>
                    <li class="test-status-error"><?php echo htmlspecialchars($err); ?></li>
                <?php } ?>
            </ul>
        </div>
        <?php } ?>
        <?php if (! empty($testResult['advertencias'])) { ?>
        <div class="test-section">
            <h2>Advertencias</h2>
            <ul class="test-list">
                <?php foreach ($testResult['advertencias'] as $warn) { ?>
                    <li class="test-status-warning"><?php echo htmlspecialchars($warn); ?></li>
                <?php } ?>
            </ul>
        </div>
        <?php } ?>
        <div class="test-section">
            <h2>Detalles</h2>
            <ul class="test-list">
                <?php foreach ($testResult['detalles'] as $det) { ?>
                    <li><?php echo htmlspecialchars($det); ?></li>
                <?php } ?>
            </ul>
        </div>
        <div class="test-section">
            <h2>Sugerencias</h2>
            <ul class="test-list">
                <li>Si no hay sesiones permanentes, ejecuta <code>create_permanent_sessions.php</code> y vuelve a probar.</li>
                <li>Verifica que el usuario de prueba exista y tenga permisos adecuados.</li>
                <li>Si el test falla, revisa la configuración de sesiones y autenticación.</li>
            </ul>
        </div>
    </div>
</body>
</html> 