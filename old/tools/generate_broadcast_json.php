<?php
/**
 * Herramienta para generar JSON de listas de difusión
 * Permite seleccionar una lista y exportar todos sus datos en formato JSON
 */

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../models/BroadcastListModel.php';

// Verificar autenticación
if (! isAuthenticated()) {
    header('Location: ../login.php');
    exit;
}

$currentUser = getCurrentUser();
$broadcastListModel = new BroadcastListModel($conn);

// Obtener todas las listas del usuario
$lists = $broadcastListModel->getListsByUser($currentUser['id']);

// Procesar la generación del JSON
$selectedListId = null;
$jsonData = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_json'])) {
    $selectedListId = (int) $_POST['list_id'];

    if ($selectedListId > 0) {
        try {
            // Obtener la lista seleccionada
            $list = $broadcastListModel->getListById($selectedListId);

            if ($list) {
                // Obtener los contactos de la lista
                $contacts = $broadcastListModel->getContactsInList($selectedListId);

                // Preparar datos para el JSON
                $jsonData = [
                    'list_info' => [
                        'id' => $list['id'],
                        'name' => $list['name'],
                        'description' => $list['description'],
                        'is_active' => (bool) $list['is_active'],
                        'created_at' => $list['created_at'],
                        'updated_at' => $list['updated_at'],
                        'user_name' => $list['user_name'],
                    ],
                    'contacts' => [],
                    'export_info' => [
                        'exported_at' => date('Y-m-d H:i:s'),
                        'total_contacts' => count($contacts),
                        'exported_by' => $currentUser['name'],
                        'exported_by_email' => $currentUser['email'],
                    ],
                ];

                // Agregar contactos
                foreach ($contacts as $contact) {
                    $jsonData['contacts'][] = [
                        'id' => $contact['id'],
                        'number' => $contact['number'],
                        'pushName' => $contact['pushName'],
                        'waName' => $contact['waName'] ?? null,
                        'added_at' => $contact['added_at'],
                        'created_at' => $contact['created_at'],
                    ];
                }
            } else {
                $error = 'No se pudo encontrar la lista seleccionada';
            }
        } catch (Exception $e) {
            $error = 'Error al generar el JSON: '.$e->getMessage();
        }
    } else {
        $error = 'Debes seleccionar una lista válida';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar JSON de Lista de Difusión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .json-display {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            max-height: 500px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .copy-btn {
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .contact-count {
            font-size: 2rem;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-file-earmark-code"></i> 
                            Generar JSON de Lista de Difusión
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <p class="text-muted">
                                    Esta herramienta te permite exportar una lista de difusión completa en formato JSON.
                                    Selecciona una lista y obtén todos los datos incluyendo información de contactos.
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="../broadcast_lists.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> Volver a Listas
                                </a>
                            </div>
                        </div>

                        <?php if ($error) { ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php } ?>

                        <!-- Formulario de selección -->
                        <form method="POST" class="mb-4">
                            <div class="row">
                                <div class="col-md-8">
                                    <label for="list_id" class="form-label">Seleccionar Lista de Difusión</label>
                                    <select name="list_id" id="list_id" class="form-select" required>
                                        <option value="">-- Selecciona una lista --</option>
                                        <?php foreach ($lists as $list) { ?>
                                            <option value="<?php echo $list['id']; ?>" 
                                                    <?php echo ($selectedListId == $list['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($list['name']); ?> 
                                                (<?php echo $list['contact_count']; ?> contactos)
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" name="generate_json" class="btn btn-primary w-100">
                                        <i class="bi bi-file-earmark-code"></i> Generar JSON
                                    </button>
                                </div>
                            </div>
                        </form>

                        <?php if ($jsonData) { ?>
                            <!-- Estadísticas de la lista -->
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="card stats-card">
                                        <div class="card-body text-center">
                                            <div class="contact-count"><?php echo count($jsonData['contacts']); ?></div>
                                            <div>Total de Contactos</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-info text-white">
                                        <div class="card-body text-center">
                                            <div class="contact-count"><?php echo $jsonData['list_info']['name']; ?></div>
                                            <div>Nombre de la Lista</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-success text-white">
                                        <div class="card-body text-center">
                                            <div class="contact-count"><?php echo date('d/m/Y', strtotime($jsonData['export_info']['exported_at'])); ?></div>
                                            <div>Fecha de Exportación</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- JSON generado -->
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="bi bi-code-slash"></i> JSON Generado
                                    </h5>
                                    <div class="copy-btn">
                                        <button class="btn btn-success btn-sm" onclick="copyToClipboard(event)">
                                            <i class="bi bi-clipboard"></i> Copiar JSON
                                        </button>
                                        <button class="btn btn-info btn-sm ms-2" onclick="downloadJSON()">
                                            <i class="bi bi-download"></i> Descargar
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="json-display" id="jsonDisplay">
<?php echo json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Información adicional -->
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="bi bi-info-circle"></i> Información de la Lista</h6>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>ID:</strong> <?php echo $jsonData['list_info']['id']; ?></p>
                                            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($jsonData['list_info']['name']); ?></p>
                                            <p><strong>Descripción:</strong> <?php echo htmlspecialchars($jsonData['list_info']['description'] ?: 'Sin descripción'); ?></p>
                                            <p><strong>Estado:</strong> 
                                                <span class="badge bg-<?php echo $jsonData['list_info']['is_active'] ? 'success' : 'danger'; ?>">
                                                    <?php echo $jsonData['list_info']['is_active'] ? 'Activa' : 'Inactiva'; ?>
                                                </span>
                                            </p>
                                            <p><strong>Creada:</strong> <?php echo date('d/m/Y H:i', strtotime($jsonData['list_info']['created_at'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="bi bi-people"></i> Resumen de Contactos</h6>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>Total de contactos:</strong> <?php echo count($jsonData['contacts']); ?></p>
                                            <p><strong>Exportado por:</strong> <?php echo htmlspecialchars($jsonData['export_info']['exported_by']); ?></p>
                                            <p><strong>Fecha de exportación:</strong> <?php echo date('d/m/Y H:i', strtotime($jsonData['export_info']['exported_at'])); ?></p>
                                            <p><strong>Formato:</strong> JSON con codificación UTF-8</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToClipboard(event) {
            const jsonText = document.getElementById('jsonDisplay').textContent;
            navigator.clipboard.writeText(jsonText).then(function() {
                // Cambiar temporalmente el texto del botón
                const btn = event.target;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check"></i> ¡Copiado!';
                btn.classList.remove('btn-success');
                btn.classList.add('btn-success');
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                }, 2000);
            }).catch(function(err) {
                console.error('Error al copiar: ', err);
                alert('Error al copiar al portapapeles');
            });
        }

        function downloadJSON() {
            const jsonText = document.getElementById('jsonDisplay').textContent;
            const blob = new Blob([jsonText], { type: 'application/json' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'lista_difusion_<?php echo $selectedListId ?: 'seleccionar'; ?>_<?php echo date('Y-m-d_H-i-s'); ?>.json';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        }

        // Auto-seleccionar la lista si solo hay una
        document.addEventListener('DOMContentLoaded', function() {
            const select = document.getElementById('list_id');
            if (select.options.length === 2) { // Solo una opción + la opción vacía
                select.selectedIndex = 1;
            }
        });
    </script>
</body>
</html>
