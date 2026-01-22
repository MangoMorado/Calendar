<?php
// Definir la ruta base del proyecto
define('BASE_PATH', dirname(dirname(__FILE__)));

// Incluir archivos de configuración y autenticación
require_once BASE_PATH.'/config/database.php';
require_once BASE_PATH.'/includes/functions.php';
require_once BASE_PATH.'/includes/auth.php';

// Iniciar sesión solo si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado usando la función del sistema
if (! isAuthenticated()) {
    // Obtener la URL base del proyecto
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol.$host.dirname(dirname($_SERVER['PHP_SELF']));

    // Guardar la URL actual para redirigir después del login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];

    header('Location: '.$baseUrl.'/login.php');
    exit;
}

// Obtener información del usuario actual usando la función del sistema
$currentUser = getCurrentUser();
if (! $currentUser) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol.$host.dirname(dirname($_SERVER['PHP_SELF']));

    header('Location: '.$baseUrl.'/login.php');
    exit;
}

// Obtener la URL base para JavaScript
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$jsBaseUrl = $protocol.$host.dirname(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ejemplos de API - Mundo Animal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .example-card {
            margin-bottom: 2rem;
        }
        .response-area {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.25rem;
            margin-top: 1rem;
            max-height: 300px;
            overflow-y: auto;
        }
        pre {
            margin: 0;
        }
        .user-info {
            margin-bottom: 2rem;
            padding: 1rem;
            background-color: #e9ecef;
            border-radius: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <!-- Información del usuario -->
        <div class="user-info">
            <h4>Información del Usuario</h4>
            <p>Bienvenido, <?php echo htmlspecialchars($currentUser['name'] ?? $currentUser['email']); ?></p>
            <p>Rol: <?php echo htmlspecialchars($currentUser['role']); ?></p>
            <a href="../logout.php" class="btn btn-danger btn-sm">Cerrar Sesión</a>
        </div>

        <h1 class="mb-4">Ejemplos de API - Mundo Animal</h1>
        
        <!-- Obtener Eventos -->
        <div class="card example-card">
            <div class="card-header">
                <h5 class="mb-0">1. Obtener Eventos del Calendario</h5>
            </div>
            <div class="card-body">
                <form id="getEventsForm">
                    <div class="mb-3">
                        <label class="form-label">Fecha Inicio</label>
                        <input type="datetime-local" class="form-control" id="startDate" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha Fin</label>
                        <input type="datetime-local" class="form-control" id="endDate" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de Calendario</label>
                        <select class="form-select" id="calendarType">
                            <option value="estetico">Estético</option>
                            <option value="veterinario">Veterinario</option>
                            <option value="general">General</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Obtener Eventos</button>
                </form>
                <div class="response-area">
                    <pre id="getEventsResponse"></pre>
                </div>
            </div>
        </div>

        <!-- Crear Cita -->
        <div class="card example-card">
            <div class="card-header">
                <h5 class="mb-0">2. Crear Nueva Cita</h5>
            </div>
            <div class="card-body">
                <form id="createAppointmentForm">
                    <div class="mb-3">
                        <label class="form-label">Título</label>
                        <input type="text" class="form-control" id="appointmentTitle" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" id="appointmentDescription"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha y Hora Inicio</label>
                        <input type="datetime-local" class="form-control" id="appointmentStart" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha y Hora Fin</label>
                        <input type="datetime-local" class="form-control" id="appointmentEnd" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de Calendario</label>
                        <select class="form-select" id="appointmentCalendarType" required>
                            <option value="estetico">Estético</option>
                            <option value="veterinario">Veterinario</option>
                            <option value="general">General</option>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="appointmentAllDay">
                        <label class="form-check-label">Todo el día</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ID de Usuario</label>
                        <input type="number" class="form-control" id="appointmentUserId" 
                               value="<?php echo htmlspecialchars($currentUser['id']); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Crear Cita</button>
                </form>
                <div class="response-area">
                    <pre id="createAppointmentResponse"></pre>
                </div>
            </div>
        </div>

        <!-- Obtener Detalles de Cita -->
        <div class="card example-card">
            <div class="card-header">
                <h5 class="mb-0">3. Obtener Detalles de Cita</h5>
            </div>
            <div class="card-body">
                <form id="getAppointmentForm">
                    <div class="mb-3">
                        <label class="form-label">ID de la Cita</label>
                        <input type="number" class="form-control" id="appointmentId" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Obtener Detalles</button>
                </form>
                <div class="response-area">
                    <pre id="getAppointmentResponse"></pre>
                </div>
            </div>
        </div>

        <!-- Obtener Notas -->
        <div class="card example-card">
            <div class="card-header">
                <h5 class="mb-0">4. Obtener Notas</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <button onclick="getAllNotes()" class="btn btn-primary me-2">Obtener Todas las Notas</button>
                    <button onclick="getSpecificNote()" class="btn btn-primary">Obtener Nota Específica</button>
                </div>
                <div class="response-area">
                    <pre id="getNotesResponse"></pre>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        const baseUrl = <?php echo json_encode($jsBaseUrl); ?>;
        const userId = <?php echo json_encode($currentUser['id']); ?>;

        // Función auxiliar para mostrar respuestas
        function showResponse(elementId, data, isError = false) {
            const element = document.getElementById(elementId);
            element.textContent = JSON.stringify(data, null, 2);
            element.style.color = isError ? 'red' : 'inherit';
        }

        // Función para manejar errores de respuesta
        async function handleResponse(response) {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('La respuesta no es JSON. Probablemente necesitas iniciar sesión.');
            }
            
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Error en la petición');
            }
            
            return data;
        }

        // Obtener Eventos
        document.getElementById('getEventsForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const start = document.getElementById('startDate').value;
            const end = document.getElementById('endDate').value;
            const calendarType = document.getElementById('calendarType').value;

            try {
                const response = await fetch(`${baseUrl}/api/appointments.php?action=get_events&start=${start}&end=${end}&calendar_type=${calendarType}`, {
                    credentials: 'include'
                });
                const data = await handleResponse(response);
                showResponse('getEventsResponse', data);
            } catch (error) {
                showResponse('getEventsResponse', { error: error.message }, true);
                if (error.message.includes('iniciar sesión')) {
                    window.location.href = `${baseUrl}/login.php`;
                }
            }
        });

        // Crear Cita
        document.getElementById('createAppointmentForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData();
            formData.append('action', 'create');
            formData.append('title', document.getElementById('appointmentTitle').value);
            formData.append('description', document.getElementById('appointmentDescription').value);
            formData.append('start_time', document.getElementById('appointmentStart').value);
            formData.append('end_time', document.getElementById('appointmentEnd').value);
            formData.append('calendar_type', document.getElementById('appointmentCalendarType').value);
            formData.append('all_day', document.getElementById('appointmentAllDay').checked);
            formData.append('user_id', document.getElementById('appointmentUserId').value);

            try {
                const response = await fetch(`${baseUrl}/api/appointments.php`, {
                    method: 'POST',
                    credentials: 'include',
                    body: formData
                });
                const data = await handleResponse(response);
                showResponse('createAppointmentResponse', data);
            } catch (error) {
                showResponse('createAppointmentResponse', { error: error.message }, true);
                if (error.message.includes('iniciar sesión')) {
                    window.location.href = `${baseUrl}/login.php`;
                }
            }
        });

        // Obtener Detalles de Cita
        document.getElementById('getAppointmentForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = document.getElementById('appointmentId').value;

            try {
                const response = await fetch(`${baseUrl}/api/get_appointment.php?id=${id}`, {
                    credentials: 'include'
                });
                const data = await handleResponse(response);
                showResponse('getAppointmentResponse', data);
            } catch (error) {
                showResponse('getAppointmentResponse', { error: error.message }, true);
                if (error.message.includes('iniciar sesión')) {
                    window.location.href = `${baseUrl}/login.php`;
                }
            }
        });

        // Obtener Todas las Notas
        async function getAllNotes() {
            try {
                const response = await fetch(`${baseUrl}/api/notes.php?action=get_notes`, {
                    credentials: 'include'
                });
                const data = await handleResponse(response);
                showResponse('getNotesResponse', data);
            } catch (error) {
                showResponse('getNotesResponse', { error: error.message }, true);
                if (error.message.includes('iniciar sesión')) {
                    window.location.href = `${baseUrl}/login.php`;
                }
            }
        }

        // Obtener Nota Específica
        async function getSpecificNote() {
            const id = prompt('Ingrese el ID de la nota:');
            if (!id) return;

            try {
                const response = await fetch(`${baseUrl}/api/notes.php?action=get_note&id=${id}`, {
                    credentials: 'include'
                });
                const data = await handleResponse(response);
                showResponse('getNotesResponse', data);
            } catch (error) {
                showResponse('getNotesResponse', { error: error.message }, true);
                if (error.message.includes('iniciar sesión')) {
                    window.location.href = `${baseUrl}/login.php`;
                }
            }
        }

        // Establecer fechas por defecto
        const now = new Date();
        const tomorrow = new Date(now);
        tomorrow.setDate(tomorrow.getDate() + 1);

        document.getElementById('startDate').value = now.toISOString().slice(0, 16);
        document.getElementById('endDate').value = tomorrow.toISOString().slice(0, 16);
        document.getElementById('appointmentStart').value = now.toISOString().slice(0, 16);
        document.getElementById('appointmentEnd').value = new Date(now.getTime() + 3600000).toISOString().slice(0, 16);
    </script>
</body>
</html> 