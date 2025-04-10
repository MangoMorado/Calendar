<?php
require_once 'models/UserModel.php';

class UserController {
    private $model;
    private $currentUser;
    
    public function __construct($conn, $currentUser) {
        $this->model = new UserModel($conn);
        $this->currentUser = $currentUser;
    }
    
    public function index() {
        // Verificar si el usuario es administrador
        if ($this->currentUser['role'] !== 'admin') {
            header('Location: unauthorized.php');
            exit;
        }
        
        $users = $this->model->getAllUsers();
        include 'views/users/index.php';
    }
    
    public function create() {
        // Verificar si el usuario es administrador
        if ($this->currentUser['role'] !== 'admin') {
            header('Location: unauthorized.php');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'user';
            $color = $_POST['color'] ?? '#0d6efd';
            
            // Validaciones básicas
            if (empty($name) || empty($email) || empty($password)) {
                $error = "Todos los campos son requeridos";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "El email no es válido";
            } elseif (strlen($password) < 6) {
                $error = "La contraseña debe tener al menos 6 caracteres";
            } elseif (!preg_match('/^#[a-fA-F0-9]{6}$/', $color)) {
                $error = "El color debe ser un código hexadecimal válido";
            } else {
                if ($this->model->createUser($name, $email, $password, $role, $color)) {
                    // Registrar la acción en el historial del usuario
                    $this->model->updateUserHistory($this->currentUser['id'], "Creó un nuevo usuario: '$name'", [
                        'extra' => "Email: $email, Rol: $role"
                    ]);
                    
                    header('Location: users.php');
                    exit;
                } else {
                    $error = "Error al crear el usuario";
                }
            }
        }
        
        include 'views/users/create.php';
    }
    
    public function edit($id) {
        // Verificar si el usuario es administrador
        if ($this->currentUser['role'] !== 'admin') {
            header('Location: unauthorized.php');
            exit;
        }
        
        $user = $this->model->getUserById($id);
        if (!$user) {
            header('Location: users.php');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $role = $_POST['role'] ?? null;
            $color = $_POST['color'] ?? null;
            $password = $_POST['password'] ?? '';
            
            // Validaciones básicas
            if (empty($name) || empty($email)) {
                $error = "Los campos nombre y email son requeridos";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "El email no es válido";
            } elseif ($color !== null && !preg_match('/^#[a-fA-F0-9]{6}$/', $color)) {
                $error = "El color debe ser un código hexadecimal válido";
            } else {
                if ($this->model->updateUser($id, $name, $email, $role, $color)) {
                    if (!empty($password)) {
                        if (strlen($password) < 6) {
                            $error = "La contraseña debe tener al menos 6 caracteres";
                        } else {
                            $this->model->updatePassword($id, $password);
                        }
                    }
                    if (!isset($error)) {
                        // Registrar la acción en el historial del usuario
                        $this->model->updateUserHistory($this->currentUser['id'], "Actualizó al usuario: '$name'", [
                            'id' => $id,
                            'extra' => "Email: $email" . ($role !== null ? ", Rol: $role" : "")
                        ]);
                        
                        header('Location: users.php');
                        exit;
                    }
                } else {
                    $error = "Error al actualizar el usuario";
                }
            }
        }
        
        include 'views/users/edit.php';
    }
    
    public function delete($id) {
        // Verificar si el usuario es administrador
        if ($this->currentUser['role'] !== 'admin') {
            header('Location: unauthorized.php');
            exit;
        }
        
        // No permitir eliminar el propio usuario
        if ($id == $this->currentUser['id']) {
            $_SESSION['error'] = "No puedes eliminar tu propio usuario";
            header('Location: users.php');
            exit;
        }
        
        // Obtener información del usuario antes de eliminarlo
        $userToDelete = $this->model->getUserById($id);
        
        if ($this->model->deleteUser($id)) {
            // Registrar la acción en el historial del usuario
            if ($userToDelete) {
                $this->model->updateUserHistory($this->currentUser['id'], "Eliminó al usuario: '{$userToDelete['name']}'", [
                    'id' => $id,
                    'extra' => "Email: {$userToDelete['email']}, Rol: {$userToDelete['role']}"
                ]);
            } else {
                $this->model->updateUserHistory($this->currentUser['id'], "Eliminó un usuario (ID: $id)");
            }
            
            $_SESSION['success'] = "Usuario eliminado correctamente";
        } else {
            $_SESSION['error'] = "Error al eliminar el usuario";
        }
        
        header('Location: users.php');
        exit;
    }
    
    /**
     * Retorna el modelo de usuario para permitir acceso desde fuera del controlador
     * 
     * @return UserModel El modelo de usuario
     */
    public function getModel() {
        return $this->model;
    }
} 