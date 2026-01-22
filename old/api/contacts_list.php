<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../includes/auth.php';

// Verificar autenticación
if (! isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

try {
    global $conn;

    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'get_contacts':
            handleGetContacts($conn);
            break;

        case 'search_contacts':
            handleSearchContacts($conn);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor: '.$e->getMessage()]);
}

function handleGetContacts($conn)
{
    $page = max(1, intval($_POST['page'] ?? 1));
    $perPage = min(100, max(10, intval($_POST['per_page'] ?? 50)));
    $search = trim($_POST['search'] ?? '');
    $offset = ($page - 1) * $perPage;
    $onlyNamed = isset($_POST['only_named']) && $_POST['only_named'] == '1';

    // Construir la consulta base
    $whereClause = 'WHERE 1=1';
    $searchParam = '';

    if (! empty($search)) {
        $whereClause .= ' AND (pushName LIKE ? OR number LIKE ?)';
        $searchParam = "%{$search}%";
    }
    if ($onlyNamed) {
        $whereClause .= " AND pushName IS NOT NULL AND pushName != ''";
    }

    // Contar total de registros
    $countQuery = "SELECT COUNT(*) as total FROM contacts {$whereClause}";
    if (! empty($search)) {
        $countStmt = mysqli_prepare($conn, $countQuery);
        mysqli_stmt_bind_param($countStmt, 'ss', $searchParam, $searchParam);
        mysqli_stmt_execute($countStmt);
        $countResult = mysqli_stmt_get_result($countStmt);
    } else {
        $countResult = mysqli_query($conn, $countQuery);
    }
    $totalContacts = mysqli_fetch_assoc($countResult)['total'];

    // Calcular total de páginas
    $totalPages = ceil($totalContacts / $perPage);

    // Obtener contactos de la página actual
    $query = "SELECT id, pushName, number FROM contacts {$whereClause} ORDER BY pushName ASC, number ASC LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $query);

    if (! empty($search)) {
        mysqli_stmt_bind_param($stmt, 'ssii', $searchParam, $searchParam, $perPage, $offset);
    } else {
        mysqli_stmt_bind_param($stmt, 'ii', $perPage, $offset);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $contacts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $contacts[] = $row;
    }

    echo json_encode([
        'success' => true,
        'contacts' => $contacts,
        'total_contacts' => $totalContacts,
        'total_pages' => $totalPages,
        'current_page' => $page,
        'per_page' => $perPage,
    ]);
}

function handleSearchContacts($conn)
{
    $search = trim($_POST['search'] ?? '');
    $limit = min(50, max(5, intval($_POST['limit'] ?? 20)));

    if (empty($search) || strlen($search) < 2) {
        echo json_encode([
            'success' => true,
            'contacts' => [],
            'message' => 'Búsqueda muy corta',
        ]);

        return;
    }

    $query = 'SELECT id, pushName, number 
              FROM contacts 
              WHERE pushName LIKE ? OR number LIKE ? 
              ORDER BY 
                CASE 
                    WHEN pushName LIKE ? THEN 1
                    WHEN pushName LIKE ? THEN 2
                    ELSE 3
                END,
                pushName ASC, number ASC 
              LIMIT ?';

    $exactMatch = $search;
    $startsWith = $search.'%';
    $contains = '%'.$search.'%';

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ssssi', $contains, $contains, $exactMatch, $startsWith, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $contacts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $contacts[] = $row;
    }

    echo json_encode([
        'success' => true,
        'contacts' => $contacts,
        'total_found' => count($contacts),
    ]);
}
?> 