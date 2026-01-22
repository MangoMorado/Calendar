<?php
/**
 * Appointment Modal Template
 * Este archivo contiene la estructura HTML del modal para crear/editar citas
 */

// Función para generar el HTML del modal de citas
function renderAppointmentModal()
{
    ob_start();
    ?>
    <!-- Modal para crear/editar citas -->
    <div id="appointmentModal" class="modal">
        <div class="modal-content">
            <span class="close"><i class="bi bi-x-lg"></i></span>
            <h2 id="modalTitle"><i class="bi bi-calendar-plus"></i> Crear Cita</h2>
            
            <form id="appointmentForm" method="post">
                <input type="hidden" name="id" id="appointmentId">
                
                <div class="form-group">
                    <label for="title"><i class="bi bi-type"></i> Título:</label>
                    <input type="text" id="title" name="title" class="form-control" required placeholder="Nombre de la cita">
                </div>
                
                <div class="form-group">
                    <label for="description"><i class="bi bi-text-paragraph"></i> Descripción:</label>
                    <textarea id="description" name="description" class="form-control" rows="3" placeholder="Detalles adicionales"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="user_id"><i class="bi bi-person"></i> Usuario Asignado:</label>
                    <select id="user_id" name="user_id" class="form-control">
                        <option value="">-- Selecciona un usuario --</option>
                        <?php
                        // Los usuarios se cargarán dinámicamente desde JavaScript
    ?>
                    </select>
                    <div id="colorPreview" class="color-preview mt-2">
                        <span class="color-circle"></span>
                        <span class="color-code"></span>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" id="allDayEvent" name="all_day" class="form-check-input">
                        <label for="allDayEvent" class="form-check-label"><i class="bi bi-calendar-day"></i> Todo el día</label>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="startTime"><i class="bi bi-clock"></i> Hora de Inicio:</label>
                        <input type="datetime-local" id="startTime" name="start_time" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="endTime"><i class="bi bi-clock-history"></i> Hora de Fin:</label>
                        <input type="datetime-local" id="endTime" name="end_time" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="calendarType"><i class="bi bi-calendar3"></i> Tipo de Calendario:</label>
                    <select id="calendarType" name="calendar_type" class="form-control">
                        <option value="general">Calendario General</option>
                        <option value="estetico">Calendario Estético</option>
                        <option value="veterinario">Calendario Veterinario</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" id="deleteAppointment" class="btn btn-danger" style="display: none;">
                        <i class="bi bi-trash"></i> Eliminar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
