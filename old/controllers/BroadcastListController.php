<?php

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'models/BroadcastListModel.php';
require_once 'models/BroadcastHistoryModel.php';
require_once 'includes/evolution_api.php';
require_once 'includes/chatbot/contactos-validation.php';

class BroadcastListController
{
    private $broadcastListModel;

    private $broadcastHistoryModel;

    private $currentUser;

    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->broadcastListModel = new BroadcastListModel($conn);
        $this->broadcastHistoryModel = new BroadcastHistoryModel($conn);
        $this->currentUser = getCurrentUser();
    }

    /**
     * Método principal que maneja todas las acciones
     */
    public function handleRequest()
    {
        $action = $_GET['action'] ?? 'list';
        // Recuperar mensajes flash de sesión (si existen)
        $message = $_SESSION['success_message'] ?? '';
        $error = $_SESSION['error_message'] ?? '';
        unset($_SESSION['success_message'], $_SESSION['error_message']);

        // Procesar formularios POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->processPostRequest();
            $message = $result['message'] ?? '';
            $error = $result['error'] ?? '';

            // Redirigir si es necesario
            if (isset($result['redirect'])) {
                header('Location: '.$result['redirect']);
                exit;
            }
        }

        // Obtener datos según la acción
        $data = $this->getDataForAction($action);

        // Renderizar vista
        $this->renderView($action, $data, $message, $error);
    }

    /**
     * Procesa todas las solicitudes POST
     */
    private function processPostRequest()
    {
        if (isset($_POST['auto_create_batches'])) {
            return $this->autoCreateBatches();
        } elseif (isset($_POST['create_list'])) {
            return $this->createList();
        } elseif (isset($_POST['update_list'])) {
            return $this->updateList();
        } elseif (isset($_POST['delete_list'])) {
            return $this->deleteList();
        } elseif (isset($_POST['add_contacts'])) {
            return $this->addContacts();
        } elseif (isset($_POST['remove_contacts'])) {
            return $this->removeContacts();
        } elseif (isset($_POST['add_manual_number'])) {
            return $this->addManualNumber();
        } elseif (isset($_POST['clear_contacts'])) {
            return $this->clearContacts();
        }

        return ['error' => 'Acción no válida'];
    }

    /**
     * Crea una nueva lista de difusión
     */
    private function createList()
    {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            return ['error' => 'El nombre de la lista es requerido'];
        }

        $listData = [
            'name' => $name,
            'description' => $description,
            'user_id' => $this->currentUser['id'],
        ];

        $listId = $this->broadcastListModel->createList($listData);
        if ($listId) {
            return [
                'message' => 'Lista creada correctamente',
                'redirect' => '?action=edit&id='.$listId,
            ];
        } else {
            return ['error' => 'Error al crear la lista'];
        }
    }

    /**
     * Actualiza una lista existente
     */
    private function updateList()
    {
        $listId = (int) ($_POST['list_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name)) {
            return ['error' => 'El nombre de la lista es requerido'];
        }

        if (! $this->broadcastListModel->isOwner($listId, $this->currentUser['id'])) {
            return ['error' => 'No tienes permisos para editar esta lista'];
        }

        $listData = [
            'name' => $name,
            'description' => $description,
            'is_active' => $isActive,
        ];

        if ($this->broadcastListModel->updateList($listId, $listData, $this->currentUser['id'])) {
            return ['message' => 'Lista actualizada correctamente'];
        } else {
            return ['error' => 'Error al actualizar la lista'];
        }
    }

    /**
     * Elimina una lista
     */
    private function deleteList()
    {
        $listId = (int) ($_POST['list_id'] ?? 0);

        if (! $this->broadcastListModel->isOwner($listId, $this->currentUser['id'])) {
            return ['error' => 'No tienes permisos para eliminar esta lista'];
        }

        if ($this->broadcastListModel->deleteList($listId, $this->currentUser['id'])) {
            return [
                'message' => 'Lista eliminada correctamente',
                'redirect' => '?action=list',
            ];
        } else {
            return ['error' => 'Error al eliminar la lista'];
        }
    }

    /**
     * Agrega contactos a una lista
     */
    private function addContacts()
    {
        $listId = (int) ($_POST['list_id'] ?? 0);
        $contactIds = $_POST['contact_ids'] ?? [];

        if (! $this->broadcastListModel->isOwner($listId, $this->currentUser['id'])) {
            return ['error' => 'No tienes permisos para modificar esta lista'];
        }

        if ($this->broadcastListModel->addContactsToList($listId, $contactIds)) {
            return ['message' => 'Contactos agregados correctamente'];
        } else {
            return ['error' => 'Error al agregar contactos'];
        }
    }

    /**
     * Remueve contactos de una lista
     */
    private function removeContacts()
    {
        $listId = (int) ($_POST['list_id'] ?? 0);
        $contactIds = $_POST['contact_ids'] ?? [];

        if (! $this->broadcastListModel->isOwner($listId, $this->currentUser['id'])) {
            return ['error' => 'No tienes permisos para modificar esta lista'];
        }

        if ($this->broadcastListModel->removeContactsFromList($listId, $contactIds)) {
            return ['message' => 'Contactos removidos correctamente'];
        } else {
            return ['error' => 'Error al remover contactos'];
        }
    }

    /**
     * Agrega un número manualmente a una lista
     */
    private function addManualNumber()
    {
        $listId = (int) ($_POST['list_id'] ?? 0);
        $number = trim($_POST['manual_number'] ?? '');
        $name = trim($_POST['manual_name'] ?? '');

        if (! $this->broadcastListModel->isOwner($listId, $this->currentUser['id'])) {
            return ['error' => 'No tienes permisos para modificar esta lista'];
        }

        // Validar formato del número usando la función robusta
        $numeroCompleto = $number.'@s.whatsapp.net';
        $validacion = limpiarYValidarNumeroWhatsApp($numeroCompleto);
        if (! $validacion['valid']) {
            return ['error' => 'El número no es válido: '.$validacion['error']];
        }

        // Agregar sufijo de WhatsApp
        $fullNumber = $number.'@s.whatsapp.net';

        // Verificar si el número ya existe en la lista
        $existingContact = $this->broadcastListModel->getContactByNumber($listId, $fullNumber);
        if ($existingContact) {
            return ['error' => 'Este número ya existe en la lista'];
        }

        // Crear o obtener el contacto
        $contactData = [
            'number' => $fullNumber,
            'pushName' => $name ?: 'Contacto Manual',
            'user_id' => $this->currentUser['id'],
        ];

        $contactId = $this->broadcastListModel->createOrGetContact($contactData);
        if (! $contactId) {
            return ['error' => 'Error al crear el contacto'];
        }

        // Agregar el contacto a la lista
        if ($this->broadcastListModel->addContactsToList($listId, [$contactId])) {
            return ['message' => 'Número agregado correctamente a la lista'];
        } else {
            return ['error' => 'Error al agregar el número a la lista'];
        }
    }

    /**
     * Elimina todos los contactos de la tabla contacts
     */
    private function clearContacts()
    {
        // Borrar todos los contactos de la tabla contacts
        $sql = 'DELETE FROM contacts';
        mysqli_query($this->conn, $sql);

        // Opcional: también puedes limpiar otras tablas relacionadas si es necesario
        return ['message' => 'Contactos eliminados correctamente'];
    }

    /**
     * Obtiene los datos necesarios según la acción
     */
    private function getDataForAction($action)
    {
        $data = [
            'stats' => $this->broadcastHistoryModel->getBroadcastStats($this->currentUser['id']),
            'lists' => [],
            'currentList' => null,
            'contactsInList' => [],
            'availableContacts' => [],
        ];

        switch ($action) {
            case 'list':
                $searchTerm = $_GET['search'] ?? '';
                if (! empty($searchTerm)) {
                    $data['lists'] = $this->broadcastListModel->searchLists($this->currentUser['id'], $searchTerm);
                } else {
                    $data['lists'] = $this->broadcastListModel->getListsByUser($this->currentUser['id']);
                }
                break;

            case 'create':
                $data['lists'] = $this->broadcastListModel->getListsByUser($this->currentUser['id']);
                break;

            case 'send':
                $data['lists'] = $this->broadcastListModel->getListsByUser($this->currentUser['id']);
                break;

            case 'edit':
            case 'view':
                $listId = (int) ($_GET['id'] ?? 0);
                $data['currentList'] = $this->broadcastListModel->getListById($listId);
                if (! $data['currentList']) {
                    return ['error' => 'Lista no encontrada'];
                }
                if ($action === 'edit' && ! $this->broadcastListModel->isOwner($listId, $this->currentUser['id'])) {
                    $_SESSION['error_message'] = 'No puedes editar esta lista. Abierta en modo vista.';
                    header('Location: ?action=view&id='.$listId);
                    exit;
                }

                $data['contactsInList'] = $this->broadcastListModel->getContactsInList($listId);
                $data['availableContacts'] = $this->broadcastListModel->getAvailableContacts($listId);
                break;
        }

        return $data;
    }

    /**
     * Renderiza la vista correspondiente
     */
    private function renderView($action, $data, $message, $error)
    {
        $pageTitle = 'Listas de Difusión | Mundo Animal';

        // Incluir el header
        include 'includes/header.php';

        // Incluir la vista correspondiente
        $viewFile = "views/broadcast_lists/{$action}.php";
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            include 'views/broadcast_lists/list.php';
        }

        // Incluir el footer
        include 'includes/footer.php';
    }

    /**
     * Actualiza las listas de difusión automáticas eliminando las anteriores y creando nuevas
     */
    private function autoCreateBatches()
    {
        $userId = $this->currentUser['id'] ?? null;
        if (! $userId) {
            $_SESSION['error_message'] = 'No autorizado';

            return ['redirect' => '?action=list'];
        }

        // 1) Eliminar todas las difusiones automáticas existentes
        $deleted = 0;
        $sqlDelete = "DELETE bl FROM broadcast_lists bl 
                     WHERE bl.description LIKE 'Creada automáticamente%' 
                     AND bl.user_id = ?";
        $stmtDelete = mysqli_prepare($this->conn, $sqlDelete);
        mysqli_stmt_bind_param($stmtDelete, 'i', $userId);

        if (mysqli_stmt_execute($stmtDelete)) {
            $deleted = mysqli_affected_rows($this->conn);
        }

        // 2) Obtener contactos no asignados a ninguna lista
        $contactIds = [];
        $sqlContacts = 'SELECT c.id FROM contacts c WHERE c.id NOT IN (SELECT contact_id FROM broadcast_list_contacts) ORDER BY c.id';
        if ($rc = mysqli_query($this->conn, $sqlContacts)) {
            while ($r = mysqli_fetch_assoc($rc)) {
                $contactIds[] = (int) $r['id'];
            }
        }

        if (empty($contactIds)) {
            $_SESSION['success_message'] = 'No hay contactos disponibles para agrupar';

            return ['redirect' => '?action=list'];
        }

        // 3) Crear nuevas difusiones en lotes de 500
        $batchSize = 500;
        $created = 0;
        $lastNumber = 0;

        for ($i = 0; $i < count($contactIds); $i += $batchSize) {
            $batch = array_slice($contactIds, $i, $batchSize);
            if (empty($batch)) {
                break;
            }

            // Buscar número único siguiente
            $candidate = $lastNumber + 1;
            while (true) {
                $nameA = "Difusion $candidate";
                $nameB = "Difusionen $candidate";
                $check = mysqli_prepare($this->conn, 'SELECT COUNT(*) as cnt FROM broadcast_lists WHERE name IN (?, ?)');
                mysqli_stmt_bind_param($check, 'ss', $nameA, $nameB);
                mysqli_stmt_execute($check);
                $rchk = mysqli_stmt_get_result($check);
                $rowc = mysqli_fetch_assoc($rchk);
                if ((int) $rowc['cnt'] === 0) {
                    $uniqueNumber = $candidate;
                    break;
                }
                $candidate++;
            }
            $lastNumber = $uniqueNumber; // reservar

            // Crear lista con nueva descripción
            $name = "Difusion $uniqueNumber";
            $desc = 'Creada automáticamente';
            $stmtIns = mysqli_prepare($this->conn, 'INSERT INTO broadcast_lists (name, description, user_id) VALUES (?, ?, ?)');
            mysqli_stmt_bind_param($stmtIns, 'ssi', $name, $desc, $userId);
            if (! mysqli_stmt_execute($stmtIns)) {
                continue;
            }
            $listId = mysqli_insert_id($this->conn);

            // Insertar contactos del batch
            $values = [];
            $types = '';
            $params = [];
            foreach ($batch as $cid) {
                $values[] = '(?, ?)';
                $types .= 'ii';
                $params[] = $listId;
                $params[] = $cid;
            }
            $sqlBulk = 'INSERT IGNORE INTO broadcast_list_contacts (list_id, contact_id) VALUES '.implode(', ', $values);
            $stmtBulk = mysqli_prepare($this->conn, $sqlBulk);
            mysqli_stmt_bind_param($stmtBulk, $types, ...$params);
            mysqli_stmt_execute($stmtBulk);

            $created++;
        }

        $_SESSION['success_message'] = "Difusiones actualizadas: $deleted eliminadas, $created nuevas creadas";

        return ['redirect' => '?action=list'];
    }

    /**
     * Obtiene estadísticas para AJAX
     */
    public function getStats()
    {
        return $this->broadcastHistoryModel->getBroadcastStats($this->currentUser['id']);
    }

    /**
     * Obtiene contactos de una lista para AJAX
     */
    public function getContactsInList($listId)
    {
        if (! $this->broadcastListModel->canAccessList($listId, $this->currentUser['id'])) {
            return [];
        }

        return $this->broadcastListModel->getContactsInList($listId);
    }
}
