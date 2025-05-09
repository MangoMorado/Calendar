<?php
$pageTitle = 'Gestión de Usuarios | Mundo Animal';
include 'includes/header.php';
?>

<div class="container">
    <div class="users-header">
        <h1><i class="bi bi-people"></i> Gestión de Usuarios</h1>
        <a href="users.php?action=create" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Nuevo Usuario
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <div class="users-table-container">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Color</th>
                        <th>Calendario</th>
                        <th>Fecha de Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="role-badge <?php echo $user['role'] === 'admin' ? 'admin' : 'user'; ?>">
                                    <?php echo $user['role'] === 'admin' ? 'Administrador' : 'Usuario'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="color-preview">
                                    <span class="color-circle" style="background-color: <?php echo htmlspecialchars($user['color']); ?>"></span>
                                    <span class="color-code"><?php echo htmlspecialchars($user['color']); ?></span>
                                </div>
                            </td>
                            <td>
                                <form method="post" class="calendar-toggle-form">
                                    <input type="hidden" name="toggle_calendar" value="1">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" 
                                               id="calendarVisible_<?php echo $user['id']; ?>" 
                                               name="calendar_visible" 
                                               <?php echo ($user['calendar_visible'] == 1) ? 'checked' : ''; ?>
                                               onchange="this.form.submit()">
                                        <label class="form-check-label" for="calendarVisible_<?php echo $user['id']; ?>">
                                            <?php echo ($user['calendar_visible'] == 1) ? 'Activo' : 'Inactivo'; ?>
                                        </label>
                                    </div>
                                </form>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="users.php?action=edit&id=<?php echo $user['id']; ?>" 
                                       class="btn btn-sm btn-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($user['id'] != $currentUser['id']): ?>
                                        <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('¿Estás seguro de que deseas eliminar este usuario?')"
                                           title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .calendar-toggle-form {
        margin: 0;
    }
    
    .form-check-input {
        cursor: pointer;
    }
    
    .form-switch .form-check-input {
        width: 3em;
        height: 1.5em;
    }
    
    .form-check-label {
        font-size: 0.9em;
        margin-left: 0.5em;
    }
</style>

<script>
    // Actualizar texto de etiqueta cuando cambia el checkbox
    document.querySelectorAll('.form-check-input').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const label = this.nextElementSibling;
            label.textContent = this.checked ? 'Activo' : 'Inactivo';
        });
    });
</script>

<?php include 'includes/footer.php'; ?> 