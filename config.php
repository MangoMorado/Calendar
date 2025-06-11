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
    
    // Obtener los valores del formulario de calendario
    $slotMinTime = $_POST['slotMinTime'] ?? '00:00:00';
    $slotMaxTime = $_POST['slotMaxTime'] ?? '24:00:00';
    $slotDuration = $_POST['slotDuration'] ?? '00:30:00';
    $timeFormat = $_POST['timeFormat'] ?? '12h';
    $timezone = $_POST['timezone'] ?? 'America/Bogota';
    $n8nApiKey = $_POST['n8n_api_key'] ?? '';
    $n8nUrl = $_POST['n8n_url'] ?? '';
    $selectedWorkflow = $_POST['selected_workflow'] ?? '';
    $evolutionApiUrl = $_POST['evolution_api_url'] ?? '';
    $evolutionApiKey = $_POST['evolution_api_key'] ?? '';
    $selectedEvolutionInstance = $_POST['selected_evolution_instance'] ?? '';
    
    // Extraer el nombre de la instancia seleccionada
    $evolutionInstanceName = '';
    if (!empty($selectedEvolutionInstance) && strpos($selectedEvolutionInstance, '|') !== false) {
        $parts = explode('|', $selectedEvolutionInstance);
        if (count($parts) >= 2) {
            $evolutionInstanceName = $parts[0]; // El nombre está en la primera parte
        }
    }
    
    // Procesar días hábiles seleccionados
    $businessDays = [];
    for ($i = 0; $i <= 6; $i++) {
        if (isset($_POST['businessDay' . $i])) {
            $businessDays[] = $i;
        }
    }
    $businessDaysJson = json_encode($businessDays);
    
    // Obtener valores de configuración de sesiones
    $sessionTimeout = $_POST['session_timeout'] ?? '3600';
    $rememberMeTimeout = $_POST['remember_me_timeout'] ?? '604800';
    $maxSessionsPerUser = $_POST['max_sessions_per_user'] ?? '5';
    $requireLoginOnVisit = isset($_POST['require_login_on_visit']) ? 1 : 0;
    $sessionCleanupInterval = $_POST['session_cleanup_interval'] ?? '86400';
    
    // Validar los valores del calendario
    if (strtotime($slotMaxTime) <= strtotime($slotMinTime)) {
        $message = 'La hora máxima debe ser posterior a la hora mínima';
    } else {
        // Guardar la configuración del calendario en la base de datos
        $sql = "INSERT INTO settings (setting_key, setting_value) 
                VALUES 
                ('slotMinTime', ?),
                ('slotMaxTime', ?),
                ('slotDuration', ?),
                ('timeFormat', ?),
                ('businessDays', ?),
                ('timezone', ?),
                ('n8n_api_key', ?),
                ('n8n_url', ?),
                ('selected_workflow', ?),
                ('evolution_api_url', ?),
                ('evolution_api_key', ?),
                ('selected_evolution_instance', ?),
                ('evolution_instance_name', ?),
                ('session_timeout', ?),
                ('remember_me_timeout', ?),
                ('max_sessions_per_user', ?),
                ('require_login_on_visit', ?),
                ('session_cleanup_interval', ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssssssssssssssss", $slotMinTime, $slotMaxTime, $slotDuration, $timeFormat, $businessDaysJson, $timezone, $n8nApiKey, $n8nUrl, $selectedWorkflow, $evolutionApiUrl, $evolutionApiKey, $selectedEvolutionInstance, $evolutionInstanceName, $sessionTimeout, $rememberMeTimeout, $maxSessionsPerUser, $requireLoginOnVisit, $sessionCleanupInterval);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = true;
            $message = 'Configuración actualizada correctamente';
        } else {
            $message = 'Error al guardar la configuración';
        }
    }
}

// Obtener la configuración actual del calendario
$settings = [];
$sql = "SELECT setting_key, setting_value FROM settings";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Valores por defecto del calendario si no existen en la base de datos
$slotMinTime = $settings['slotMinTime'] ?? '00:00:00';
$slotMaxTime = $settings['slotMaxTime'] ?? '24:00:00';
$slotDuration = $settings['slotDuration'] ?? '00:30:00';
$timeFormat = $settings['timeFormat'] ?? '12h';
$timezone = $settings['timezone'] ?? 'America/Bogota';
$n8nApiKey = $settings['n8n_api_key'] ?? '';
$n8nUrl = $settings['n8n_url'] ?? '';
$selectedWorkflow = $settings['selected_workflow'] ?? '';
$evolutionApiUrl = $settings['evolution_api_url'] ?? '';
$evolutionApiKey = $settings['evolution_api_key'] ?? '';
$selectedEvolutionInstance = $settings['selected_evolution_instance'] ?? '';
$evolutionInstanceName = $settings['evolution_instance_name'] ?? '';

// Obtener días hábiles o establecer por defecto (lunes a viernes)
$businessDays = isset($settings['businessDays']) ? json_decode($settings['businessDays'], true) : [1, 2, 3, 4, 5];
if (!is_array($businessDays)) {
    $businessDays = [1, 2, 3, 4, 5]; // Lunes a viernes por defecto
}

// Obtener configuración actual de sesiones desde la base de datos
$sessionTimeout = (int)($settings['session_timeout'] ?? 3600);
$rememberMeTimeout = (int)($settings['remember_me_timeout'] ?? 604800);
$maxSessionsPerUser = (int)($settings['max_sessions_per_user'] ?? 5);
$requireLoginOnVisit = (bool)($settings['require_login_on_visit'] ?? 1);
$sessionCleanupInterval = (int)($settings['session_cleanup_interval'] ?? 86400);

// Obtener workflows de n8n si tenemos URL y API KEY
$workflows = [];
if (!empty($n8nUrl) && !empty($n8nApiKey)) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, rtrim($n8nUrl, '/') . '/api/v1/workflows');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: application/json',
        'X-N8N-API-KEY: ' . $n8nApiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $workflowsData = json_decode($response, true);
        if (is_array($workflowsData) && isset($workflowsData['data'])) {
            $workflows = $workflowsData['data'];
        }
    }
}

// Obtener instancias de Evolution API si tenemos URL y API KEY
$evolutionInstances = [];
if (!empty($evolutionApiUrl) && !empty($evolutionApiKey)) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, rtrim($evolutionApiUrl, '/') . '/instance/fetchInstances');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: application/json',
        'apikey: ' . $evolutionApiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $instancesData = json_decode($response, true);
        if (is_array($instancesData)) {
            // Filtrar solo las instancias que tienen los campos requeridos
            foreach ($instancesData as $instance) {
                if (is_array($instance) && isset($instance['name']) && isset($instance['token'])) {
                    $evolutionInstances[] = [
                        'instance' => $instance['name'],
                        'apikey' => $instance['token']
                    ];
                }
            }
        }
    }
}

// Definir título de la página
$pageTitle = 'Configuración del Sistema | Mundo Animal';

// Incluir el header
include 'includes/header.php';
?>

<div class="container">
    <div class="config-header">
        <h1><i class="bi bi-gear"></i> Configuración del Sistema</h1>
        <p class="text-muted">Ajusta los parámetros del calendario y del sistema de sesiones.</p>
    </div>

    <?php if (isset($message)): ?>
        <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="config-card">
        <form method="post" class="config-form">
            <!-- Configuración de Calendario -->
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
                    <label for="timezone">Zona Horaria:</label>
                    <select id="timezone" name="timezone" class="form-control" required>
                        <option value="America/Bogota" <?php echo $timezone === 'America/Bogota' ? 'selected' : ''; ?>>America/Bogota (Colombia)</option>
                        <option value="America/Mexico_City" <?php echo $timezone === 'America/Mexico_City' ? 'selected' : ''; ?>>America/Mexico_City (CDMX)</option>
                        <option value="America/Caracas" <?php echo $timezone === 'America/Caracas' ? 'selected' : ''; ?>>America/Caracas (Venezuela)</option>
                        <option value="America/Argentina/Buenos_Aires" <?php echo $timezone === 'America/Argentina/Buenos_Aires' ? 'selected' : ''; ?>>America/Argentina/Buenos_Aires</option>
                        <option value="America/Lima" <?php echo $timezone === 'America/Lima' ? 'selected' : ''; ?>>America/Lima (Perú)</option>
                        <option value="America/Santiago" <?php echo $timezone === 'America/Santiago' ? 'selected' : ''; ?>>America/Santiago (Chile)</option>
                        <option value="UTC" <?php echo $timezone === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                        <!-- Puedes agregar más zonas horarias si lo deseas -->
                    </select>
                    <small class="form-text text-muted">Selecciona la zona horaria principal del sistema</small>
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

            <!-- Configuración de Sesiones -->
            <div class="form-section">
                <h2><i class="bi bi-shield-lock"></i> Configuración de Sesiones</h2>
                
                <div class="form-group">
                    <label for="session_timeout">Tiempo de Sesión Normal:</label>
                    <select id="session_timeout" name="session_timeout" class="form-control" required>
                        <option value="300" <?php echo $sessionTimeout === 300 ? 'selected' : ''; ?>>5 minutos</option>
                        <option value="900" <?php echo $sessionTimeout === 900 ? 'selected' : ''; ?>>15 minutos</option>
                        <option value="1800" <?php echo $sessionTimeout === 1800 ? 'selected' : ''; ?>>30 minutos</option>
                        <option value="3600" <?php echo $sessionTimeout === 3600 ? 'selected' : ''; ?>>1 hora</option>
                        <option value="7200" <?php echo $sessionTimeout === 7200 ? 'selected' : ''; ?>>2 horas</option>
                        <option value="14400" <?php echo $sessionTimeout === 14400 ? 'selected' : ''; ?>>4 horas</option>
                        <option value="28800" <?php echo $sessionTimeout === 28800 ? 'selected' : ''; ?>>8 horas</option>
                        <option value="86400" <?php echo $sessionTimeout === 86400 ? 'selected' : ''; ?>>24 horas</option>
                        <option value="0" <?php echo $sessionTimeout === 0 ? 'selected' : ''; ?>>Sin restricciones</option>
                    </select>
                    <small class="form-text text-muted">Tiempo que dura una sesión normal sin "recordar equipo"</small>
                </div>

                <div class="form-group">
                    <label for="remember_me_timeout">Tiempo de "Recordar Equipo":</label>
                    <select id="remember_me_timeout" name="remember_me_timeout" class="form-control" required>
                        <option value="3600" <?php echo $rememberMeTimeout === 3600 ? 'selected' : ''; ?>>1 hora</option>
                        <option value="7200" <?php echo $rememberMeTimeout === 7200 ? 'selected' : ''; ?>>2 horas</option>
                        <option value="14400" <?php echo $rememberMeTimeout === 14400 ? 'selected' : ''; ?>>4 horas</option>
                        <option value="28800" <?php echo $rememberMeTimeout === 28800 ? 'selected' : ''; ?>>8 horas</option>
                        <option value="86400" <?php echo $rememberMeTimeout === 86400 ? 'selected' : ''; ?>>1 día</option>
                        <option value="172800" <?php echo $rememberMeTimeout === 172800 ? 'selected' : ''; ?>>2 días</option>
                        <option value="604800" <?php echo $rememberMeTimeout === 604800 ? 'selected' : ''; ?>>1 semana</option>
                        <option value="1209600" <?php echo $rememberMeTimeout === 1209600 ? 'selected' : ''; ?>>2 semanas</option>
                        <option value="2592000" <?php echo $rememberMeTimeout === 2592000 ? 'selected' : ''; ?>>1 mes</option>
                        <option value="-1" <?php echo $rememberMeTimeout === -1 ? 'selected' : ''; ?>>Siempre</option>
                    </select>
                    <small class="form-text text-muted">Tiempo que dura una sesión con "recordar equipo" activado</small>
                </div>

                <div class="form-group">
                    <label for="max_sessions_per_user">Máximo de Sesiones por Usuario:</label>
                    <select id="max_sessions_per_user" name="max_sessions_per_user" class="form-control" required>
                        <?php for ($i = 1; $i <= 20; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $maxSessionsPerUser === $i ? 'selected' : ''; ?>>
                                <?php echo $i; ?> sesión<?php echo $i > 1 ? 'es' : ''; ?>
                            </option>
                        <?php endfor; ?>
                        <option value="0" <?php echo $maxSessionsPerUser === 0 ? 'selected' : ''; ?>>Sin límites</option>
                    </select>
                    <small class="form-text text-muted">Número máximo de sesiones activas que puede tener un usuario simultáneamente</small>
                </div>

                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" id="require_login_on_visit" name="require_login_on_visit" 
                               class="form-check-input" <?php echo $requireLoginOnVisit ? 'checked' : ''; ?>>
                        <label for="require_login_on_visit" class="form-check-label">
                            Requerir inicio de sesión en cada visita
                        </label>
                    </div>
                    <small class="form-text text-muted">Si está activado, los usuarios deberán iniciar sesión cada vez que visiten la página</small>
                </div>

                <div class="form-group">
                    <label for="session_cleanup_interval">Intervalo de Limpieza:</label>
                    <select id="session_cleanup_interval" name="session_cleanup_interval" class="form-control" required>
                        <option value="3600" <?php echo $sessionCleanupInterval === 3600 ? 'selected' : ''; ?>>1 hora</option>
                        <option value="7200" <?php echo $sessionCleanupInterval === 7200 ? 'selected' : ''; ?>>2 horas</option>
                        <option value="14400" <?php echo $sessionCleanupInterval === 14400 ? 'selected' : ''; ?>>4 horas</option>
                        <option value="28800" <?php echo $sessionCleanupInterval === 28800 ? 'selected' : ''; ?>>8 horas</option>
                        <option value="86400" <?php echo $sessionCleanupInterval === 86400 ? 'selected' : ''; ?>>1 día</option>
                        <option value="172800" <?php echo $sessionCleanupInterval === 172800 ? 'selected' : ''; ?>>2 días</option>
                    </select>
                    <small class="form-text text-muted">Con qué frecuencia se limpian automáticamente las sesiones expiradas</small>
                </div>
            </div>

            <!-- Configuración de n8n -->
            <div class="form-section">
                <h2><i class="bi bi-robot"></i> Integración n8n</h2>
                <div class="form-group">
                    <label for="n8n_url">URL de n8n:</label>
                    <input type="url" id="n8n_url" name="n8n_url" class="form-control" 
                           value="<?php echo htmlspecialchars($n8nUrl); ?>" 
                           placeholder="https://tu-instancia-n8n.com" required>
                    <small class="form-text text-muted">URL base de tu instancia de n8n (ej: https://n8n.tudominio.com)</small>
                </div>
                
                <div class="form-group">
                    <label for="n8n_api_key">n8n API KEY:</label>
                    <input type="password" id="n8n_api_key" name="n8n_api_key" class="form-control" 
                           value="<?php echo htmlspecialchars($n8nApiKey); ?>" autocomplete="off" required>
                    <small class="form-text text-muted">Clave de API para autenticar peticiones a n8n. Se guarda de forma segura.</small>
                </div>
                
                <?php if (!empty($workflows)): ?>
                <div class="form-group">
                    <label for="selected_workflow">Workflow Activo:</label>
                    <select id="selected_workflow" name="selected_workflow" class="form-control">
                        <option value="">Selecciona un workflow</option>
                        <?php foreach ($workflows as $workflow): ?>
                            <option value="<?php echo htmlspecialchars($workflow['id'] . '|' . $workflow['name']); ?>" 
                                    <?php echo $selectedWorkflow === ($workflow['id'] . '|' . $workflow['name']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($workflow['name']); ?> (ID: <?php echo $workflow['id']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">Workflow de n8n que se utilizará para el chatbot</small>
                </div>
                <?php elseif (!empty($n8nUrl) && !empty($n8nApiKey)): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    No se pudieron obtener los workflows. Verifica la URL y la API KEY.
                </div>
                <?php endif; ?>
            </div>

            <!-- Configuración de Evolution API -->
            <div class="form-section">
                <h2><i class="bi bi-chat-dots"></i> Integración Evolution API</h2>
                <div class="form-group">
                    <label for="evolution_api_url">URL de Evolution API:</label>
                    <input type="url" id="evolution_api_url" name="evolution_api_url" class="form-control" 
                           value="<?php echo htmlspecialchars($evolutionApiUrl); ?>" 
                           placeholder="https://tu-evolution-api.com" required>
                    <small class="form-text text-muted">URL base de tu instancia de Evolution API</small>
                </div>
                
                <div class="form-group">
                    <label for="evolution_api_key">Evolution API Key:</label>
                    <input type="password" id="evolution_api_key" name="evolution_api_key" class="form-control" 
                           value="<?php echo htmlspecialchars($evolutionApiKey); ?>" autocomplete="off" required>
                    <small class="form-text text-muted">Clave de API para autenticar peticiones a Evolution API. Se guarda de forma segura.</small>
                </div>
                
                <?php if (!empty($evolutionInstances)): ?>
                <div class="form-group">
                    <label for="selected_evolution_instance">Instancia Evolution:</label>
                    <select id="selected_evolution_instance" name="selected_evolution_instance" class="form-control">
                        <option value="">Selecciona una instancia</option>
                        <?php foreach ($evolutionInstances as $instance): ?>
                            <option value="<?php echo htmlspecialchars($instance['instance'] . '|' . $instance['apikey']); ?>" 
                                    <?php echo $selectedEvolutionInstance === ($instance['instance'] . '|' . $instance['apikey']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($instance['instance']); ?> (API: <?php echo substr($instance['apikey'], 0, 8) . '...'; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">Instancia de Evolution API que se utilizará para las difusiones</small>
                </div>
                <?php elseif (!empty($evolutionApiUrl) && !empty($evolutionApiKey)): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    No se pudieron obtener las instancias. Verifica la URL y la API KEY.
                </div>
                <?php endif; ?>
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

/* Estilos para la configuración de sesiones */
.form-section {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.form-section h2 {
    color: #495057;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e9ecef;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-section h2 i {
    color: #007bff;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
    display: block;
}

.form-control {
    border: 1px solid #ced4da;
    border-radius: 6px;
    padding: 10px 12px;
    font-size: 14px;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    outline: 0;
}

.form-text {
    font-size: 12px;
    color: #6c757d;
    margin-top: 5px;
}

.form-check {
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-check-input {
    margin: 0;
}

.form-check-label {
    margin: 0;
    font-weight: 500;
}

.form-actions {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
    margin-top: 30px;
}

.btn-success {
    background-color: #28a745;
    border-color: #28a745;
    padding: 12px 30px;
    font-weight: 600;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.btn-success:hover {
    background-color: #218838;
    border-color: #1e7e34;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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