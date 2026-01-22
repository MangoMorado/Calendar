<?php
/**
 * Herramienta para limpiar contactos inválidos de la base de datos
 * Identifica y elimina números que no cumplen con la validación robusta
 */

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/chatbot/contactos-validation.php';

// Verificar autenticación
if (! isAuthenticated()) {
    header('Location: ../login.php');
    exit;
}

$currentUser = getCurrentUser();

// Procesar la limpieza
$action = $_POST['action'] ?? '';
$stats = null;
$errores = [];

if ($action === 'scan') {
    // Escanear contactos inválidos
    $sql = 'SELECT id, number, pushName, created_at FROM contacts ORDER BY created_at DESC';
    $result = mysqli_query($conn, $sql);

    $total = 0;
    $validos = 0;
    $invalidos = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $total++;
        if (validarNumeroWhatsApp($row['number'])) {
            $validos++;
        } else {
            $invalidos[] = $row;
        }
    }

    $stats = [
        'total' => $total,
        'validos' => $validos,
        'invalidos' => count($invalidos),
        'detalles_invalidos' => $invalidos,
    ];
} elseif ($action === 'clean') {
    // Limpiar contactos inválidos
    $sql = 'SELECT id, number FROM contacts';
    $result = mysqli_query($conn, $sql);

    $eliminados = 0;
    $errores = [];

    while ($row = mysqli_fetch_assoc($result)) {
        if (! validarNumeroWhatsApp($row['number'])) {
            // Eliminar contacto inválido
            $deleteSql = 'DELETE FROM contacts WHERE id = ?';
            $stmt = mysqli_prepare($conn, $deleteSql);
            mysqli_stmt_bind_param($stmt, 'i', $row['id']);

            if (mysqli_stmt_execute($stmt)) {
                $eliminados++;
            } else {
                $errores[] = "Error eliminando contacto ID {$row['id']}: ".mysqli_error($conn);
            }
        }
    }

    $stats = [
        'eliminados' => $eliminados,
        'errores' => count($errores),
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Limpieza de Contactos Inválidos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .contacto-invalido {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .warning-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0">
                            <i class="bi bi-exclamation-triangle"></i> 
                            Limpieza de Contactos Inválidos
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <p class="text-muted">
                                    Esta herramienta identifica y elimina contactos que no cumplen con la validación robusta de números de WhatsApp.
                                    <strong>⚠️ Usa con precaución, la eliminación es irreversible.</strong>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="../broadcast_lists.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> Volver a Listas
                                </a>
                            </div>
                        </div>

                        <!-- Alertas de seguridad -->
                        <div class="alert alert-warning">
                            <h6><i class="bi bi-shield-exclamation"></i> Importante:</h6>
                            <ul class="mb-0">
                                <li>Esta herramienta eliminará permanentemente contactos inválidos</li>
                                <li>Se consideran inválidos números como "718584497008509@s.whatsapp.net"</li>
                                <li>Revisa los resultados antes de proceder con la limpieza</li>
                                <li>Se recomienda hacer una copia de seguridad antes de usar</li>
                            </ul>
                        </div>

                        <!-- Botones de acción -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="scan">
                                    <button type="submit" class="btn btn-info w-100">
                                        <i class="bi bi-search"></i> Escanear Contactos
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <?php if ($stats && isset($stats['detalles_invalidos'])) { ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de que quieres eliminar TODOS los contactos inválidos? Esta acción es irreversible.')">
                                        <input type="hidden" name="action" value="clean">
                                        <button type="submit" class="btn btn-danger w-100">
                                            <i class="bi bi-trash"></i> Limpiar Contactos Inválidos
                                        </button>
                                    </form>
                                <?php } else { ?>
                                    <button class="btn btn-danger w-100" disabled>
                                        <i class="bi bi-trash"></i> Limpiar Contactos Inválidos
                                    </button>
                                <?php } ?>
                            </div>
                        </div>

                        <?php if ($stats) { ?>
                            <!-- Estadísticas -->
                            <div class="row mb-4">
                                <?php if (isset($stats['total'])) { ?>
                                    <!-- Estadísticas del escaneo -->
                                    <div class="col-md-4">
                                        <div class="card stats-card">
                                            <div class="card-body text-center">
                                                <div class="h2"><?php echo $stats['total']; ?></div>
                                                <div>Total de Contactos</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card bg-success text-white">
                                            <div class="card-body text-center">
                                                <div class="h2"><?php echo $stats['validos']; ?></div>
                                                <div>Contactos Válidos</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card warning-card">
                                            <div class="card-body text-center">
                                                <div class="h2"><?php echo $stats['invalidos']; ?></div>
                                                <div>Contactos Inválidos</div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } elseif (isset($stats['eliminados'])) { ?>
                                    <!-- Estadísticas de la limpieza -->
                                    <div class="col-md-6">
                                        <div class="card bg-success text-white">
                                            <div class="card-body text-center">
                                                <div class="h2"><?php echo $stats['eliminados']; ?></div>
                                                <div>Contactos Eliminados</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card bg-info text-white">
                                            <div class="card-body text-center">
                                                <div class="h2"><?php echo $stats['errores']; ?></div>
                                                <div>Errores</div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>

                            <?php if (isset($stats['detalles_invalidos']) && ! empty($stats['detalles_invalidos'])) { ?>
                                <!-- Detalles de contactos inválidos -->
                                <div class="card">
                                    <div class="card-header bg-warning text-dark">
                                        <h5 class="mb-0">
                                            <i class="bi bi-exclamation-triangle"></i> 
                                            Contactos Inválidos Detectados
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted mb-3">
                                            Los siguientes contactos no cumplen con la validación robusta y serán eliminados:
                                        </p>
                                        
                                        <div class="row">
                                            <?php foreach ($stats['detalles_invalidos'] as $contacto) { ?>
                                                <div class="col-md-6 mb-3">
                                                    <div class="contacto-invalido">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div>
                                                                <strong>ID:</strong> <?php echo $contacto['id']; ?><br>
                                                                <strong>Número:</strong> <code><?php echo htmlspecialchars($contacto['number']); ?></code><br>
                                                                <strong>Nombre:</strong> <?php echo htmlspecialchars($contacto['pushName'] ?: 'Sin nombre'); ?><br>
                                                                <strong>Creado:</strong> <?php echo date('d/m/Y H:i', strtotime($contacto['created_at'])); ?>
                                                            </div>
                                                            <div class="text-end">
                                                                <span class="badge bg-warning text-dark">Inválido</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>

                            <?php if (isset($stats['eliminados'])) { ?>
                                <!-- Resultado de la limpieza -->
                                <div class="alert alert-success">
                                    <h6><i class="bi bi-check-circle"></i> Limpieza Completada:</h6>
                                    <p class="mb-0">
                                        Se eliminaron <strong><?php echo $stats['eliminados']; ?></strong> contactos inválidos de la base de datos.
                                        <?php if ($stats['errores'] > 0) { ?>
                                            <br><strong>⚠️ Nota:</strong> Se encontraron <?php echo $stats['errores']; ?> errores durante el proceso.
                                        <?php } ?>
                                    </p>
                                </div>
                            <?php } ?>

                            <?php if (! empty($errores)) { ?>
                                <!-- Errores -->
                                <div class="alert alert-danger">
                                    <h6><i class="bi bi-exclamation-triangle"></i> Errores Encontrados:</h6>
                                    <ul class="mb-0">
                                        <?php foreach ($errores as $error) { ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php } ?>
                                    </ul>
                                </div>
                            <?php } ?>
                        <?php } ?>

                        <!-- Información adicional -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="bi bi-info-circle"></i> ¿Qué se considera inválido?</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="mb-0">
                                            <li>Números que no siguen el formato internacional</li>
                                            <li>Números de prueba o secuenciales</li>
                                            <li>Números que no corresponden a países válidos</li>
                                            <li>Números con formato incorrecto de WhatsApp</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="bi bi-shield-check"></i> Validaciones Implementadas</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="mb-0">
                                            <li>Formato de WhatsApp (@s.whatsapp.net)</li>
                                            <li>Indicativos de países válidos</li>
                                            <li>Longitud de números por país</li>
                                            <li>Detección de números de prueba</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
