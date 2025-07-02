<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'models/BroadcastListModel.php';
require_once 'models/BroadcastHistoryModel.php';
require_once 'includes/evolution_api.php';

class BroadcastListController {
    private $broadcastListModel;
    private $broadcastHistoryModel;
    private $currentUser;
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->broadcastListModel = new BroadcastListModel($conn);
        $this->broadcastHistoryModel = new BroadcastHistoryModel($conn);
        $this->currentUser = getCurrentUser();
    }

    /**
     * Método principal que maneja todas las acciones
     */
    public function handleRequest() {
        $action = $_GET['action'] ?? 'list';
        $message = '';
        $error = '';

        // Procesar formularios POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->processPostRequest();
            $message = $result['message'] ?? '';
            $error = $result['error'] ?? '';
            
            // Redirigir si es necesario
            if (isset($result['redirect'])) {
                header('Location: ' . $result['redirect']);
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
    private function processPostRequest() {
        if (isset($_POST['create_list'])) {
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
    private function createList() {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name)) {
            return ['error' => 'El nombre de la lista es requerido'];
        }
        
        $listData = [
            'name' => $name,
            'description' => $description,
            'user_id' => $this->currentUser['id']
        ];
        
        $listId = $this->broadcastListModel->createList($listData);
        if ($listId) {
            return [
                'message' => 'Lista creada correctamente',
                'redirect' => '?action=edit&id=' . $listId
            ];
        } else {
            return ['error' => 'Error al crear la lista'];
        }
    }

    /**
     * Actualiza una lista existente
     */
    private function updateList() {
        $listId = (int)($_POST['list_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name)) {
            return ['error' => 'El nombre de la lista es requerido'];
        }
        
        if (!$this->broadcastListModel->canAccessList($listId, $this->currentUser['id'])) {
            return ['error' => 'No tienes permisos para editar esta lista'];
        }
        
        $listData = [
            'name' => $name,
            'description' => $description,
            'is_active' => $isActive
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
    private function deleteList() {
        $listId = (int)($_POST['list_id'] ?? 0);
        
        if (!$this->broadcastListModel->canAccessList($listId, $this->currentUser['id'])) {
            return ['error' => 'No tienes permisos para eliminar esta lista'];
        }
        
        if ($this->broadcastListModel->deleteList($listId, $this->currentUser['id'])) {
            return [
                'message' => 'Lista eliminada correctamente',
                'redirect' => '?action=list'
            ];
        } else {
            return ['error' => 'Error al eliminar la lista'];
        }
    }

    /**
     * Agrega contactos a una lista
     */
    private function addContacts() {
        $listId = (int)($_POST['list_id'] ?? 0);
        $contactIds = $_POST['contact_ids'] ?? [];
        
        if (!$this->broadcastListModel->canAccessList($listId, $this->currentUser['id'])) {
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
    private function removeContacts() {
        $listId = (int)($_POST['list_id'] ?? 0);
        $contactIds = $_POST['contact_ids'] ?? [];
        
        if (!$this->broadcastListModel->canAccessList($listId, $this->currentUser['id'])) {
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
    private function addManualNumber() {
        $listId = (int)($_POST['list_id'] ?? 0);
        $number = trim($_POST['manual_number'] ?? '');
        $name = trim($_POST['manual_name'] ?? '');
        
        if (!$this->broadcastListModel->canAccessList($listId, $this->currentUser['id'])) {
            return ['error' => 'No tienes permisos para modificar esta lista'];
        }
        
        // Validar formato del número
        if (empty($number) || !preg_match('/^\d{10,15}$/', $number)) {
            return ['error' => 'El número debe tener entre 10 y 15 dígitos numéricos'];
        }
        
        // Agregar sufijo de WhatsApp
        $fullNumber = $number . '@s.whatsapp.net';
        
        // Verificar si el número ya existe en la lista
        $existingContact = $this->broadcastListModel->getContactByNumber($listId, $fullNumber);
        if ($existingContact) {
            return ['error' => 'Este número ya existe en la lista'];
        }
        
        // Crear o obtener el contacto
        $contactData = [
            'number' => $fullNumber,
            'pushName' => $name ?: 'Contacto Manual',
            'user_id' => $this->currentUser['id']
        ];
        
        $contactId = $this->broadcastListModel->createOrGetContact($contactData);
        if (!$contactId) {
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
    private function clearContacts() {
        // Borrar todos los contactos de la tabla contacts
        $sql = "DELETE FROM contacts";
        mysqli_query($this->conn, $sql);
        // Opcional: también puedes limpiar otras tablas relacionadas si es necesario
        return ['message' => 'Contactos eliminados correctamente'];
    }

    /**
     * Obtiene los datos necesarios según la acción
     */
    private function getDataForAction($action) {
        $data = [
            'stats' => $this->broadcastHistoryModel->getBroadcastStats($this->currentUser['id']),
            'lists' => [],
            'currentList' => null,
            'contactsInList' => [],
            'availableContacts' => []
        ];

        switch ($action) {
            case 'list':
                $searchTerm = $_GET['search'] ?? '';
                if (!empty($searchTerm)) {
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
                $listId = (int)($_GET['id'] ?? 0);
                $data['currentList'] = $this->broadcastListModel->getListById($listId, $this->currentUser['id']);
                
                if (!$data['currentList']) {
                    return ['error' => 'Lista no encontrada o no tienes permisos para acceder'];
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
    private function renderView($action, $data, $message, $error) {
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
     * Obtiene estadísticas para AJAX
     */
    public function getStats() {
        return $this->broadcastHistoryModel->getBroadcastStats($this->currentUser['id']);
    }

    /**
     * Obtiene contactos de una lista para AJAX
     */
    public function getContactsInList($listId) {
        if (!$this->broadcastListModel->canAccessList($listId, $this->currentUser['id'])) {
            return [];
        }
        return $this->broadcastListModel->getContactsInList($listId);
    }
} 