<?php
// Incluir archivos de configuración, funciones y autenticación
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Verificar que el usuario esté autenticado y sea administrador
requireAuth();
$currentUser = getCurrentUser();
if ($currentUser['role'] !== 'admin') {
    header('Location: unauthorized.php');
    exit;
}

// Procesar el formulario si se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = false;
    $message = '';
    
    // Obtener los valores del formulario
    $slotMinTime = $_POST['slotMinTime'] ?? '00:00:00';
    $slotMaxTime = $_POST['slotMaxTime'] ?? '24:00:00';
    $slotDuration = $_POST['slotDuration'] ?? '00:30:00';
    $timeFormat = $_POST['timeFormat'] ?? '12h';
    
    // Procesar días hábiles seleccionados
    $businessDays = [];
    for ($i = 0; $i <= 6; $i++) {
        if (isset($_POST['businessDay' . $i])) {
            $businessDays[] = $i;
        }
    }
    $businessDaysJson = json_encode($businessDays);
    
    // Validar los valores
    if (strtotime($slotMaxTime) <= strtotime($slotMinTime)) {
        $message = 'La hora máxima debe ser posterior a la hora mínima';
    } else {
        // Guardar la configuración en la base de datos
        $sql = "INSERT INTO settings (setting_key, setting_value) 
                VALUES 
                ('slotMinTime', ?),
                ('slotMaxTime', ?),
                ('slotDuration', ?),
                ('timeFormat', ?),
                ('businessDays', ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssss", $slotMinTime, $slotMaxTime, $slotDuration, $timeFormat, $businessDaysJson);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = true;
            $message = 'Configuración actualizada correctamente';
        } else {
            $message = 'Error al guardar la configuración';
        }
    }
}

// Obtener la configuración actual
$settings = [];
$sql = "SELECT setting_key, setting_value FROM settings";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Valores por defecto si no existen en la base de datos
$slotMinTime = $settings['slotMinTime'] ?? '00:00:00';
$slotMaxTime = $settings['slotMaxTime'] ?? '24:00:00';
$slotDuration = $settings['slotDuration'] ?? '00:30:00';
$timeFormat = $settings['timeFormat'] ?? '12h';

// Obtener días hábiles o establecer por defecto (lunes a viernes)
$businessDays = isset($settings['businessDays']) ? json_decode($settings['businessDays'], true) : [1, 2, 3, 4, 5];
if (!is_array($businessDays)) {
    $businessDays = [1, 2, 3, 4, 5]; // Lunes a viernes por defecto
}

// Definir título de la página
$pageTitle = 'Configuración del Sistema | Mundo Animal';

// Incluir el header
include 'includes/header.php';
?>

<div class="container">
    <div class="config-header">
        <h1><i class="bi bi-gear"></i> Configuración del Sistema</h1>
        <p class="text-muted">Ajusta los parámetros del calendario y otras configuraciones del sistema.</p>
    </div>

    <?php if (isset($message)): ?>
        <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="config-card">
        <form method="post" class="config-form">
            <div class="form-section">
                <h2><i class="bi bi-clock"></i> Configuración de Horarios</h2>
                
                <div class="form-group">
                    <label for="slotMinTime">Hora de Inicio:</label>
                    <input type="time" id="slotMinTime" name="slotMinTime" class="form-control" 
                           value="<?php echo $slotMinTime; ?>" required>
                    <small class="form-text text-muted">Hora en que comienza el calendario</small>
                </div>

                <div class="form-group">
                    <label for="slotMaxTime">Hora de Fin:</label>
                    <input type="time" id="slotMaxTime" name="slotMaxTime" class="form-control" 
                           value="<?php echo $slotMaxTime; ?>" required>
                    <small class="form-text text-muted">Hora en que termina el calendario</small>
                </div>

                <div class="form-group">
                    <label for="slotDuration">Duración de los Slots:</label>
                    <select id="slotDuration" name="slotDuration" class="form-control" required>
                        <option value="00:15:00" <?php echo $slotDuration === '00:15:00' ? 'selected' : ''; ?>>15 minutos</option>
                        <option value="00:30:00" <?php echo $slotDuration === '00:30:00' ? 'selected' : ''; ?>>30 minutos</option>
                        <option value="01:00:00" <?php echo $slotDuration === '01:00:00' ? 'selected' : ''; ?>>1 hora</option>
                    </select>
                    <small class="form-text text-muted">Intervalo de tiempo entre slots</small>
                </div>

                <div class="form-group">
                    <label for="timeFormat">Formato de Hora:</label>
                    <select id="timeFormat" name="timeFormat" class="form-control" required>
                        <option value="12h" <?php echo $timeFormat === '12h' ? 'selected' : ''; ?>>12 horas (AM/PM)</option>
                        <option value="24h" <?php echo $timeFormat === '24h' ? 'selected' : ''; ?>>24 horas</option>
                    </select>
                    <small class="form-text text-muted">Formato de visualización de las horas</small>
                </div>
                
                <div class="form-group">
                    <label>Días hábiles:</label>
                    <div class="business-days-container">
                        <table class="table table-borderless business-days-table">
                            <tbody>
                                <tr>
                                    <?php 
                                    $days = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
                                    for ($i = 0; $i < 7; $i++) {
                                        // Ajustar el índice para que domingo sea 0, lunes 1, etc.
                                        $dayIndex = $i + 1;
                                        if ($dayIndex == 7) $dayIndex = 0;
                                        $checked = in_array($dayIndex, $businessDays) ? 'checked' : '';
                                    ?>
                                        <td class="business-day-cell">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="businessDay<?php echo $dayIndex; ?>" 
                                                      name="businessDay<?php echo $dayIndex; ?>" <?php echo $checked; ?>>
                                                <label class="form-check-label" for="businessDay<?php echo $dayIndex; ?>">
                                                    <?php echo $days[$i]; ?>
                                                </label>
                                            </div>
                                        </td>
                                    <?php } ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <small class="form-text text-muted">Selecciona los días que se mostrarán en el calendario</small>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save"></i>&nbsp; Guardar Configuración
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.business-days-container {
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 15px;
    margin-bottom: 10px;
    background-color: #f9f9f9;
}

.business-days-table {
    width: 100%;
    margin-bottom: 0;
}

.business-day-cell {
    padding: 10px;
    text-align: center;
    width: 14.28%; /* 100% / 7 días */
}

.form-check {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.form-check-input {
    margin-top: 0;
    margin-right: 0;
    margin-bottom: 8px;
    transform: scale(1.2);
}

.form-check-label {
    margin-left: 0;
    font-weight: 500;
}

/* Estilos responsivos para pantallas pequeñas */
@media (max-width: 767px) {
    .business-days-table {
        display: flex;
        flex-wrap: wrap;
    }
    
    .business-days-table tbody, .business-days-table tr {
        display: flex;
        flex-wrap: wrap;
        width: 100%;
    }
    
    .business-day-cell {
        flex: 0 0 33.33%;
        max-width: 33.33%;
        padding: 8px 4px;
    }
}

/* Estilos para pantallas muy pequeñas */
@media (max-width: 480px) {
    .business-day-cell {
        flex: 0 0 50%;
        max-width: 50%;
    }
}

.quick-selection-buttons {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.quick-selection-buttons .btn {
    min-width: 80px;
    padding: 3px 10px;
    border-radius: 20px;
    background-color: #f8f9fa;
    transition: all 0.2s ease;
}

.quick-selection-buttons .btn:hover {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}
</style>

<!-- Scripts para mejorar la experiencia de usuario en la selección de días -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Añadir efecto visual al hacer hover sobre las celdas de días
    const dayCells = document.querySelectorAll('.business-day-cell');
    dayCells.forEach(cell => {
        cell.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#e9ecef';
        });
        cell.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
        
        // Permitir hacer clic en toda la celda para marcar/desmarcar
        cell.addEventListener('click', function(e) {
            // Si se hizo clic directamente en el input o label, no hacer nada adicional
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'LABEL') {
                return;
            }
            
            // Encontrar el checkbox dentro de esta celda y cambiar su estado
            const checkbox = this.querySelector('input[type="checkbox"]');
            checkbox.checked = !checkbox.checked;
        });
    });
    
    // Botones rápidos para seleccionar/deseleccionar grupos comunes
    function addQuickSelectionButtons() {
        const container = document.querySelector('.business-days-container');
        const quickButtons = document.createElement('div');
        quickButtons.className = 'quick-selection-buttons';
        quickButtons.innerHTML = `
            <button type="button" class="btn btn-sm btn-outline-secondary" id="selectWeekdays">L-V</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="selectWeekend">S-D</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAll">Todos</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAll">Ninguno</button>
        `;
        
        container.insertBefore(quickButtons, container.firstChild);
        
        // Agregar eventos a los botones
        document.getElementById('selectWeekdays').addEventListener('click', function() {
            for (let i = 1; i <= 5; i++) {
                document.getElementById(`businessDay${i}`).checked = true;
            }
            document.getElementById('businessDay0').checked = false;
            document.getElementById('businessDay6').checked = false;
        });
        
        document.getElementById('selectWeekend').addEventListener('click', function() {
            document.getElementById('businessDay0').checked = true;
            document.getElementById('businessDay6').checked = true;
            for (let i = 1; i <= 5; i++) {
                document.getElementById(`businessDay${i}`).checked = false;
            }
        });
        
        document.getElementById('selectAll').addEventListener('click', function() {
            for (let i = 0; i <= 6; i++) {
                document.getElementById(`businessDay${i}`).checked = true;
            }
        });
        
        document.getElementById('deselectAll').addEventListener('click', function() {
            for (let i = 0; i <= 6; i++) {
                document.getElementById(`businessDay${i}`).checked = false;
            }
        });
    }
    
    addQuickSelectionButtons();
});
</script>

<?php
// Incluir el footer
include 'includes/footer.php';
?> 