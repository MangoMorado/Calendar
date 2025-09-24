<?php
// Cargar hora guardada desde settings
$notifTime = '09:00';
$sql = "SELECT setting_value FROM settings WHERE setting_key = 'notifications_send_time' LIMIT 1";
$res = mysqli_query($conn, $sql);
if ($res && $row = mysqli_fetch_assoc($res)) {
    $notifTime = $row['setting_value'] ?: $notifTime;
}

// Cargar estado del recordatorio diario (por defecto OFF)
$dailyReminderEnabled = false;
$sql = "SELECT setting_value FROM settings WHERE setting_key = 'notifications_daily_appointments_enabled' LIMIT 1";
$res = mysqli_query($conn, $sql);
if ($res && $row = mysqli_fetch_assoc($res)) {
    $dailyReminderEnabled = ($row['setting_value'] == '1');
}
?>

<div class="form-section">
    <h2><i class="bi bi-bell"></i> Notificaciones</h2>
    <p class="text-muted">Configura la hora diaria para el envío de notificaciones.</p>

    <form id="formNotificaciones" class="row g-3" onsubmit="return false;">
        <div class="col-12 col-md-6">
            <label for="notifications_send_time" class="form-label">Hora de envío</label>
            <input type="time" id="notifications_send_time" name="notifications_send_time" class="form-control" value="<?php echo htmlspecialchars($notifTime); ?>" required>
            <div class="form-text">Formato 24h. Ej: 09:00</div>
        </div>
        <div class="col-12">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="daily_reminder_enabled" name="daily_reminder_enabled" <?php echo $dailyReminderEnabled ? 'checked' : ''; ?>>
                <label class="form-check-label" for="daily_reminder_enabled">
                    Recordatorio: Citas del día
                </label>
            </div>
            <div class="form-text">Envía un recordatorio diario con las citas del día.</div>
        </div>
        <div class="col-12">
            <button id="btnGuardarNotif" class="btn btn-success" type="submit">
                <i class="bi bi-save"></i> Guardar
            </button>
            <button id="btnEnviarAhora" class="btn btn-primary ms-2" type="button">
                <i class="bi bi-send"></i> Enviar ahora
            </button>
        </div>
    </form>

    <div id="notifAlert" class="mt-3"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formNotificaciones');
    const btn = document.getElementById('btnGuardarNotif');
    const alertBox = document.getElementById('notifAlert');
    form.addEventListener('submit', function() {
        const time = document.getElementById('notifications_send_time').value;
        const enabled = document.getElementById('daily_reminder_enabled').checked ? '1' : '0';
        btn.disabled = true;
        const fd = new FormData();
        fd.append('action', 'save_notifications_settings');
        fd.append('notifications_send_time', time);
        fd.append('notifications_daily_appointments_enabled', enabled);
        fetch('chatbot_actions.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                const type = data.success ? 'success' : 'danger';
                const msg = data.message || (data.success ? 'Guardado' : 'Error al guardar');
                alertBox.innerHTML = `<div class="alert alert-${type}">${msg}</div>`;
                if (window.showNotification) window.showNotification(msg, data.success ? 'success' : 'error');
            })
            .catch(() => {
                alertBox.innerHTML = `<div class="alert alert-danger">Error de conexión</div>`;
            })
            .finally(() => { btn.disabled = false; });
    });

    // Enviar ahora (forzar ejecución del cron)
    document.getElementById('btnEnviarAhora').addEventListener('click', function() {
        this.disabled = true;
        const fd = new FormData();
        fd.append('action', 'trigger_notifications_now');
        fetch('chatbot_actions.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                const ok = !!data.success;
                const msg = data.message || (ok ? 'Enviado' : 'Error al enviar');
                alertBox.innerHTML = `<div class="alert alert-${ok?'success':'danger'}">${msg}</div>`;
                if (window.showNotification) window.showNotification(msg, ok ? 'success' : 'error');
            })
            .catch(() => {
                alertBox.innerHTML = `<div class="alert alert-danger">Error de conexión</div>`;
            })
            .finally(() => { this.disabled = false; });
    });
});
</script>


