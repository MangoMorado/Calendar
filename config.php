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
                ('timeFormat', ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssss", $slotMinTime, $slotMaxTime, $slotDuration, $timeFormat);
        
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
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save"></i>&nbsp; Guardar Configuración
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Incluir el footer
include 'includes/footer.php';
?> 