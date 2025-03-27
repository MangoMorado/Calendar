<?php
// Los usuarios ya están disponibles a través de $calendarData['users']
$users = $calendarData['users'];
?>

<div class="modal fade" id="appointmentModal" tabindex="-1" aria-labelledby="appointmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="appointmentModalLabel">Nueva Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="appointmentForm">
                    <input type="hidden" name="id" value="">
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Título</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="user_id" class="form-label">Usuario</label>
                        <select class="form-select" id="user_id" name="user_id">
                            <option value="">Selecciona un usuario</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" 
                                        data-color="<?php echo htmlspecialchars($user['color']); ?>">
                                    <?php echo htmlspecialchars($user['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="colorPreview" class="mt-2">
                            <div class="color-circle"></div>
                            <span class="color-code"></span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="calendar_type" class="form-label">Tipo de Calendario</label>
                        <select class="form-select" id="calendar_type" name="calendar_type" required>
                            <option value="general">General</option>
                            <option value="estetico">Estético</option>
                            <option value="veterinario">Veterinario</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_time" class="form-label">Hora de Inicio</label>
                                <input type="datetime-local" class="form-control" id="start_time" name="start_time" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_time" class="form-label">Hora de Fin</label>
                                <input type="datetime-local" class="form-control" id="end_time" name="end_time" required>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" form="appointmentForm">Guardar</button>
            </div>
        </div>
    </div>
</div> 