<?php
// Incluir archivos necesarios
require_once 'includes/auth.php';

// Verificar que el usuario esté autenticado
requireAuth();

// Obtener información del usuario actual
$currentUser = getCurrentUser();

// Verificar que el usuario sea administrador
if ($currentUser['role'] !== 'admin') {
    // Redirigir a la página de acceso no autorizado
    header("Location: unauthorized.php");
    exit;
}

// Parámetros de paginación
$registrosPorPagina = 20;
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaActual - 1) * $registrosPorPagina;

// Parámetros de filtrado
$tipoFiltro = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$usuarioFiltro = isset($_GET['usuario']) ? (int)$_GET['usuario'] : 0;

// Obtener usuarios para el filtro
global $conn;
$usuarios = array();
$sql = "SELECT id, name, email FROM users ORDER BY name";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $usuarios[] = $row;
}

// Obtener todos los registros de historial de todos los usuarios
$historiales = array();
$totalRegistros = 0;

// Construir la consulta SQL
$countSql = "SELECT COUNT(*) as total FROM users";
$dataSql = "SELECT u.id, u.name, u.email, u.history FROM users u";

// Aplicar filtros si existen
$whereClause = "";
$whereParams = array();
$whereTypes = "";

if ($usuarioFiltro > 0) {
    $whereClause .= ($whereClause ? " AND " : " WHERE ") . "u.id = ?";
    $whereParams[] = $usuarioFiltro;
    $whereTypes .= "i";
}

$countSql .= $whereClause;

// Obtener el total de registros para la paginación
$stmt = mysqli_prepare($conn, $countSql);
if (!empty($whereParams)) {
    mysqli_stmt_bind_param($stmt, $whereTypes, ...$whereParams);
}
mysqli_stmt_execute($stmt);
$countResult = mysqli_stmt_get_result($stmt);
$countRow = mysqli_fetch_assoc($countResult);
$totalRegistros = $countRow['total'];

// Calcular el número total de páginas
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

// Preparar la consulta de datos con paginación
if (empty($whereClause)) {
    $dataSql .= " ORDER BY u.name LIMIT ? OFFSET ?";
    $dataParams = [$registrosPorPagina, $offset];
    $dataTypes = "ii";
} else {
    $dataSql .= $whereClause . " ORDER BY u.name LIMIT ? OFFSET ?";
    $dataParams = $whereParams;
    $dataParams[] = $registrosPorPagina;
    $dataParams[] = $offset;
    $dataTypes = $whereTypes . "ii";
}

// Obtener los datos para la página actual
$stmt = mysqli_prepare($conn, $dataSql);
mysqli_stmt_bind_param($stmt, $dataTypes, ...$dataParams);
mysqli_stmt_execute($stmt);
$dataResult = mysqli_stmt_get_result($stmt);

while ($user = mysqli_fetch_assoc($dataResult)) {
    if (!empty($user['history'])) {
        $historyLines = explode("\n", $user['history']);
        
        // Aplicar filtro por tipo de acción
        if (!empty($tipoFiltro)) {
            $historyLines = array_filter($historyLines, function($entry) use ($tipoFiltro) {
                return (strpos(strtolower($entry), strtolower($tipoFiltro)) !== false);
            });
        }
        
        foreach ($historyLines as $entry) {
            // Extraer timestamp
            $timestamp = '';
            if (preg_match('/\[(.*?)\]/', $entry, $matches)) {
                $timestamp = $matches[1];
            }
            
            $historiales[] = [
                'usuario_id' => $user['id'],
                'usuario_nombre' => $user['name'],
                'usuario_email' => $user['email'],
                'timestamp' => $timestamp,
                'entrada' => $entry
            ];
        }
    }
}

// Ordenar por timestamp (más reciente primero)
usort($historiales, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

// Limitar los resultados para la paginación actual
$historiales = array_slice($historiales, 0, $registrosPorPagina);

// Definir título de la página
$pageTitle = 'Historial de Actividades | Mundo Animal';

// Estilos adicionales específicos para esta página
$extraStyles = '
<link rel="stylesheet" href="assets/css/historial.css">
';

// Incluir el header
include 'includes/header.php';
?>
    
<main class="container history-container">
    <div class="history-header">
        <div class="history-title">
            <div class="history-icon">
                <i class="bi bi-clock-history"></i>
            </div>
            <h1>Historial de Actividades</h1>
        </div>
    </div>
    
    <form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <div class="filters">
            <div class="filter-group">
                <label for="tipo">Tipo de Actividad:</label>
                <select name="tipo" id="tipo" class="filter-select">
                    <option value="">Todos los tipos</option>
                    <option value="cita" <?php echo $tipoFiltro === 'cita' ? 'selected' : ''; ?>>Citas</option>
                    <option value="sesión" <?php echo $tipoFiltro === 'sesión' ? 'selected' : ''; ?>>Sesiones</option>
                    <option value="perfil" <?php echo $tipoFiltro === 'perfil' ? 'selected' : ''; ?>>Perfil</option>
                    <option value="contraseña" <?php echo $tipoFiltro === 'contraseña' ? 'selected' : ''; ?>>Contraseña</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="usuario">Usuario:</label>
                <select name="usuario" id="usuario" class="filter-select">
                    <option value="0">Todos los usuarios</option>
                    <?php foreach ($usuarios as $usuario) : ?>
                        <option value="<?php echo $usuario['id']; ?>" <?php echo $usuarioFiltro === $usuario['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($usuario['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="filter-button">
                <i class="bi bi-funnel"></i> Filtrar
            </button>
        </div>
    </form>
    
    <?php if (empty($historiales)) : ?>
        <div class="empty-history">
            <i class="bi bi-info-circle"></i> No se encontraron registros de actividad
        </div>
    <?php else : ?>
        <ul class="history-list">
            <?php foreach ($historiales as $historial) : 
                // Determinar el icono basado en el tipo de actividad
                $icon = 'bi-activity';
                $actionClass = '';
                
                if (strpos($historial['entrada'], 'Creó una cita') !== false) {
                    $icon = 'bi-calendar-plus';
                    $actionClass = 'action-create';
                } elseif (strpos($historial['entrada'], 'Actualizó una cita') !== false) {
                    $icon = 'bi-calendar-check';
                    $actionClass = 'action-update';
                } elseif (strpos($historial['entrada'], 'Eliminó una cita') !== false) {
                    $icon = 'bi-calendar-x';
                    $actionClass = 'action-delete';
                } elseif (strpos($historial['entrada'], 'Inicio de sesión') !== false) {
                    $icon = 'bi-box-arrow-in-right';
                    $actionClass = 'action-login';
                } elseif (strpos($historial['entrada'], 'Cierre de sesión') !== false) {
                    $icon = 'bi-box-arrow-right';
                    $actionClass = 'action-logout';
                } elseif (strpos($historial['entrada'], 'Actualización de perfil') !== false) {
                    $icon = 'bi-person-gear';
                    $actionClass = 'action-profile';
                } elseif (strpos($historial['entrada'], 'Cambio de contraseña') !== false) {
                    $icon = 'bi-key';
                    $actionClass = 'action-password';
                }
                
                // Formatear timestamp
                $formattedTimestamp = date('d/m/Y H:i', strtotime($historial['timestamp']));
                
                // Extraer la acción principal sin el timestamp
                $mainAction = preg_replace('/\[.*?\]\s*/', '', $historial['entrada']);
                
                // Extraer ID si existe
                $idInfo = '';
                if (preg_match('/\(ID: (\d+)\)/', $mainAction, $matches)) {
                    $idInfo = $matches[0];
                    $mainAction = str_replace($idInfo, '', $mainAction);
                }
                
                // Extraer información de fecha si existe
                $dateInfo = '';
                if (preg_match('/Fecha: (\d{2}\/\d{2}\/\d{4}\s\d{2}:\d{2})/', $historial['entrada'], $matches)) {
                    $dateInfo = $matches[0];
                }
                
                // Extraer información extra si existe
                $extraInfo = '';
                if (preg_match('/- (.+?)(?=$|\s-\s)/', $historial['entrada'], $matches)) {
                    // Verificar que no sea la fecha (que ya extrajimos)
                    if (strpos($matches[0], 'Fecha:') === false) {
                        $extraInfo = $matches[1];
                    }
                }
            ?>
                <li class="history-item <?php echo $actionClass; ?>">
                    <div class="history-user">
                        <div class="user-name"><?php echo htmlspecialchars($historial['usuario_nombre']); ?></div>
                        <div class="user-email"><?php echo htmlspecialchars($historial['usuario_email']); ?></div>
                    </div>
                    <div class="history-icon-item">
                        <i class="bi <?php echo $icon; ?>"></i>
                    </div>
                    <div class="history-content">
                        <div class="history-main">
                            <?php echo htmlspecialchars(trim($mainAction)); ?>
                        </div>
                        <div class="history-details">
                            <span class="history-time">
                                <i class="bi bi-clock"></i> <?php echo htmlspecialchars($formattedTimestamp); ?>
                            </span>
                            <?php if (!empty($dateInfo)) : ?>
                                <span class="history-date">
                                    <i class="bi bi-calendar-event"></i> <?php echo htmlspecialchars($dateInfo); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($extraInfo)) : ?>
                                <span class="history-extra">
                                    <i class="bi bi-info-circle"></i> <?php echo htmlspecialchars($extraInfo); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($idInfo)) : ?>
                                <span class="history-id">
                                    <i class="bi bi-hash"></i> <?php echo htmlspecialchars($idInfo); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        
        <?php if ($totalPaginas > 1) : ?>
            <div class="pagination">
                <?php if ($paginaActual > 1) : ?>
                    <a href="?pagina=1<?php echo !empty($tipoFiltro) ? '&tipo=' . urlencode($tipoFiltro) : ''; ?><?php echo $usuarioFiltro > 0 ? '&usuario=' . $usuarioFiltro : ''; ?>">
                        <i class="bi bi-chevron-double-left"></i>
                    </a>
                    <a href="?pagina=<?php echo $paginaActual - 1; ?><?php echo !empty($tipoFiltro) ? '&tipo=' . urlencode($tipoFiltro) : ''; ?><?php echo $usuarioFiltro > 0 ? '&usuario=' . $usuarioFiltro : ''; ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php 
                    $rango = 2;
                    $inicio = max(1, $paginaActual - $rango);
                    $fin = min($totalPaginas, $paginaActual + $rango);
                    
                    if ($inicio > 1) {
                        echo '<span>...</span>';
                    }
                    
                    for ($i = $inicio; $i <= $fin; $i++) : 
                ?>
                    <a href="?pagina=<?php echo $i; ?><?php echo !empty($tipoFiltro) ? '&tipo=' . urlencode($tipoFiltro) : ''; ?><?php echo $usuarioFiltro > 0 ? '&usuario=' . $usuarioFiltro : ''; ?>" 
                       class="<?php echo $i === $paginaActual ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php 
                    endfor;
                    
                    if ($fin < $totalPaginas) {
                        echo '<span>...</span>';
                    }
                ?>
                
                <?php if ($paginaActual < $totalPaginas) : ?>
                    <a href="?pagina=<?php echo $paginaActual + 1; ?><?php echo !empty($tipoFiltro) ? '&tipo=' . urlencode($tipoFiltro) : ''; ?><?php echo $usuarioFiltro > 0 ? '&usuario=' . $usuarioFiltro : ''; ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <a href="?pagina=<?php echo $totalPaginas; ?><?php echo !empty($tipoFiltro) ? '&tipo=' . urlencode($tipoFiltro) : ''; ?><?php echo $usuarioFiltro > 0 ? '&usuario=' . $usuarioFiltro : ''; ?>">
                        <i class="bi bi-chevron-double-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php
// Scripts adicionales
$extraScripts = '
<script src="assets/js/historial.js"></script>
';

// Incluir el footer
include 'includes/footer.php';
?> 