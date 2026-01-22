<?php
// Activar errores en desarrollo
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/php_error.log');
error_reporting(E_ALL);

require_once __DIR__.'/includes/bootstrap.php';
require_once __DIR__.'/includes/auth.php';

// Requiere sesión iniciada
requireAuth();

// Título de la página
$pageTitle = 'Estadísticas | Mundo Animal';

// Obtener datos agregados de citas por mes (de todos los calendarios)
$labels = [];
$values = [];
$totalAppointments = 0;

// Usamos mysqli (consistente con el resto del sitio)
$sql = "SELECT DATE_FORMAT(start_time, '%Y-%m') as ym, COUNT(*) as total
        FROM appointments
        GROUP BY ym
        ORDER BY ym ASC";

if ($result = mysqli_query($conn, $sql)) {
    // Mapa de meses a español abreviado
    $months = [
        '01' => 'Ene', '02' => 'Feb', '03' => 'Mar', '04' => 'Abr', '05' => 'May', '06' => 'Jun',
        '07' => 'Jul', '08' => 'Ago', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dic',
    ];

    while ($row = mysqli_fetch_assoc($result)) {
        $ym = $row['ym']; // YYYY-MM
        $year = substr($ym, 0, 4);
        $month = substr($ym, 5, 2);
        $label = (isset($months[$month]) ? $months[$month] : $month).' '.$year;
        $labels[] = $label;
        $values[] = (int) $row['total'];
        $totalAppointments += (int) $row['total'];
    }
    mysqli_free_result($result);
}

// Totales por tipo de calendario
$typeTotals = [
    'general' => 0,
    'veterinario' => 0,
    'estetico' => 0,
];

$sqlTypes = 'SELECT calendar_type, COUNT(*) as total FROM appointments GROUP BY calendar_type';
if ($resTypes = mysqli_query($conn, $sqlTypes)) {
    while ($row = mysqli_fetch_assoc($resTypes)) {
        $type = $row['calendar_type'] ?: 'general';
        if (isset($typeTotals[$type])) {
            $typeTotals[$type] = (int) $row['total'];
        }
    }
    mysqli_free_result($resTypes);
}

// Top 3 usuarios con más citas
$topUsers = [];
$sqlTop = "SELECT u.id, u.name, COALESCE(u.color, '#5D69F7') as color, COUNT(a.id) as total
           FROM appointments a
           JOIN users u ON a.user_id = u.id
           GROUP BY u.id, u.name, u.color
           ORDER BY total DESC
           LIMIT 3";
if ($resTop = mysqli_query($conn, $sqlTop)) {
    while ($row = mysqli_fetch_assoc($resTop)) {
        $topUsers[] = $row;
    }
    mysqli_free_result($resTop);
}

// Promedio de citas por día (desde la primera cita hasta hoy)
$avgPerDay = 0;
$avgFirstDate = null;
$avgDaysSpan = 0;
$sqlAvg = 'SELECT MIN(DATE(start_time)) AS first_day, COUNT(*) AS total FROM appointments';
if ($resAvg = mysqli_query($conn, $sqlAvg)) {
    if ($row = mysqli_fetch_assoc($resAvg)) {
        $avgFirstDate = $row['first_day'];
        $totalCount = (int) $row['total'];
        if ($avgFirstDate) {
            $startDt = new DateTime($avgFirstDate);
            $endDt = new DateTime('today');
            $diff = $startDt->diff($endDt);
            $avgDaysSpan = ((int) $diff->days) + 1; // incluir hoy
            if ($avgDaysSpan > 0) {
                $avgPerDay = $totalCount / $avgDaysSpan;
            }
        }
    }
    mysqli_free_result($resAvg);
}

// Distribución por día de la semana (promedio por semana)
$weekdayAverages = [];
$weekdayCounts = array_fill(1, 7, 0); // 1=Domingo ... 7=Sábado (MySQL DAYOFWEEK)
$weeksSpan = max(1, (int) ceil(($avgDaysSpan ?: 1) / 7));

$sqlDows = 'SELECT DAYOFWEEK(start_time) AS dow, COUNT(*) AS total FROM appointments GROUP BY dow';
if ($resDow = mysqli_query($conn, $sqlDows)) {
    while ($row = mysqli_fetch_assoc($resDow)) {
        $dow = (int) $row['dow'];
        if ($dow >= 1 && $dow <= 7) {
            $weekdayCounts[$dow] = (int) $row['total'];
        }
    }
    mysqli_free_result($resDow);
}

// Mapa MySQL 1..7 -> texto Lun..Dom (queremos Lunes..Domingo)
$dowOrder = [2, 3, 4, 5, 6, 7, 1]; // L a D
$dowNames = [
    1 => 'Domingo', 2 => 'Lunes', 3 => 'Martes', 4 => 'Miércoles', 5 => 'Jueves', 6 => 'Viernes', 7 => 'Sábado',
];

$maxAvg = 0;
foreach ($dowOrder as $dow) {
    $avg = $weeksSpan > 0 ? ($weekdayCounts[$dow] / $weeksSpan) : 0;
    $weekdayAverages[] = [
        'name' => $dowNames[$dow],
        'avg' => $avg,
        'total' => $weekdayCounts[$dow],
    ];
    if ($avg > $maxAvg) {
        $maxAvg = $avg;
    }
}

// Series para el segundo gráfico (controlado por botones)
// 12 meses (por mes)
$labels12m = [];
$values12m = [];

$now = new DateTime;
$now->modify('first day of this month');
$monthMap = [];
for ($i = 11; $i >= 0; $i--) {
    $key = $now->format('Y-m');
    $labels12m[] = $now->format('M Y');
    $monthMap[$key] = 0;
    $now->modify('-1 month');
}
$sql12 = "SELECT DATE_FORMAT(start_time, '%Y-%m') ym, COUNT(*) total
              FROM appointments
              WHERE start_time >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
              GROUP BY ym";
if ($rs = mysqli_query($conn, $sql12)) {
    while ($r = mysqli_fetch_assoc($rs)) {
        if (isset($monthMap[$r['ym']])) {
            $monthMap[$r['ym']] = (int) $r['total'];
        }
    }
    mysqli_free_result($rs);
}
// Re-ordenar a la misma secuencia de labels
$values12m = array_values($monthMap);

// Últimos 30 días (por día)
$labels30d = [];
$values30d = [];

$map = [];
$start = new DateTime('-29 days');
$end = new DateTime('today');
for ($d = clone $start; $d <= $end; $d->modify('+1 day')) {
    $key = $d->format('Y-m-d');
    $labels30d[] = $d->format('d/m');
    $map[$key] = 0;
}
$sql30 = 'SELECT DATE(start_time) d, COUNT(*) total
              FROM appointments
              WHERE start_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
              GROUP BY d';
if ($rs = mysqli_query($conn, $sql30)) {
    while ($r = mysqli_fetch_assoc($rs)) {
        if (isset($map[$r['d']])) {
            $map[$r['d']] = (int) $r['total'];
        }
    }
    mysqli_free_result($rs);
}
$values30d = array_values($map);

// Últimos 7 días (por día)
$labels7d = [];
$values7d = [];

$map = [];
$start = new DateTime('-6 days');
$end = new DateTime('today');
for ($d = clone $start; $d <= $end; $d->modify('+1 day')) {
    $key = $d->format('Y-m-d');
    $labels7d[] = $d->format('d/m');
    $map[$key] = 0;
}
$sql7 = 'SELECT DATE(start_time) d, COUNT(*) total
             FROM appointments
             WHERE start_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY d';
if ($rs = mysqli_query($conn, $sql7)) {
    while ($r = mysqli_fetch_assoc($rs)) {
        if (isset($map[$r['d']])) {
            $map[$r['d']] = (int) $r['total'];
        }
    }
    mysqli_free_result($rs);
}
$values7d = array_values($map);

// Distribución por hora (0-23) y hora pico
$hourLabels = [];
$hourValues = [];
$peakHour = null;
$peakHourCount = 0;

$map = array_fill(0, 24, 0);
$sqlH = 'SELECT HOUR(start_time) h, COUNT(*) total FROM appointments GROUP BY h';
if ($rs = mysqli_query($conn, $sqlH)) {
    while ($r = mysqli_fetch_assoc($rs)) {
        $h = (int) $r['h'];
        if ($h >= 0 && $h <= 23) {
            $map[$h] = (int) $r['total'];
        }
    }
    mysqli_free_result($rs);
}
for ($h = 0; $h < 24; $h++) {
    $hourLabels[] = str_pad((string) $h, 2, '0', STR_PAD_LEFT).':00';
    $hourValues[] = $map[$h];
    if ($map[$h] > $peakHourCount) {
        $peakHourCount = $map[$h];
        $peakHour = $h;
    }
}

// Día de mayor actividad (por total de citas)
$busiestDay = null;        // 'Y-m-d'
$busiestDayTotal = 0;
$busiestDayTypeTotals = ['general' => 0, 'veterinario' => 0, 'estetico' => 0];
$busiestDayHours = [];     // array [['label'=>HH:00,'count'=>N]] top 5

// Obtener día con más citas
$sqlBD = 'SELECT DATE(start_time) d, COUNT(*) total
              FROM appointments
              GROUP BY d
              ORDER BY total DESC, d DESC
              LIMIT 1';
if ($rs = mysqli_query($conn, $sqlBD)) {
    if ($r = mysqli_fetch_assoc($rs)) {
        $busiestDay = $r['d'];
        $busiestDayTotal = (int) $r['total'];
    }
    mysqli_free_result($rs);
}

if ($busiestDay) {
    // Totales por tipo en ese día
    $sqlBDT = "SELECT calendar_type, COUNT(*) total
                   FROM appointments
                   WHERE DATE(start_time) = '".mysqli_real_escape_string($conn, $busiestDay)."'
                   GROUP BY calendar_type";
    if ($rs = mysqli_query($conn, $sqlBDT)) {
        while ($r = mysqli_fetch_assoc($rs)) {
            $t = $r['calendar_type'] ?: 'general';
            if (isset($busiestDayTypeTotals[$t])) {
                $busiestDayTypeTotals[$t] = (int) $r['total'];
            }
        }
        mysqli_free_result($rs);
    }

    // Top 5 horas de ese día
    $sqlBDH = "SELECT HOUR(start_time) h, COUNT(*) total
                   FROM appointments
                   WHERE DATE(start_time) = '".mysqli_real_escape_string($conn, $busiestDay)."'
                   GROUP BY h
                   ORDER BY total DESC, h ASC
                   LIMIT 5";
    if ($rs = mysqli_query($conn, $sqlBDH)) {
        while ($r = mysqli_fetch_assoc($rs)) {
            $label = str_pad((string) ((int) $r['h']), 2, '0', STR_PAD_LEFT).':00';
            $busiestDayHours[] = ['label' => $label, 'count' => (int) $r['total']];
        }
        mysqli_free_result($rs);
    }
}

// Incluir header
include __DIR__.'/includes/header.php';
?>

<main class="container-fluid mt-3">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h4 mb-0">Estadísticas de Citas</h2>
                        <div class="text-end">
                            <div class="small text-muted">Citas agendadas (total)</div>
                            <div class="fs-3 fw-bold text-dark"><?php echo number_format($totalAppointments, 0, ',', '.'); ?></div>
                        </div>
                    </div>
                    <div class="w-100" style="position: relative; height: 500px;">
                        <canvas id="appointmentsChart" class="w-100 h-100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="h5 mb-1">Balances en el tiempo</h3>
                            <p class="text-muted small mb-0">Cambiar rango afecta solo este gráfico</p>
                        </div>
                        <div class="btn-group" role="group" aria-label="Rangos">
                            <button id="btnRange12m" type="button" class="btn btn-success">12 meses</button>
                            <button id="btnRange30d" type="button" class="btn btn-secondary">30 días</button>
                            <button id="btnRange7d" type="button" class="btn btn-secondary">7 días</button>
                        </div>
                    </div>
                    <div class="w-100" style="position: relative; height: 340px;">
                        <canvas id="appointmentsRangeChart" class="w-100 h-100"></canvas>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="h5 mb-1">Horas pico</h3>
                            <p class="text-muted small mb-0">Distribución por hora del día</p>
                        </div>
                        <?php if ($peakHour !== null) { ?>
                            <span class="badge bg-success">Pico: <?php echo str_pad((string) $peakHour, 2, '0', STR_PAD_LEFT).':00'; ?></span>
                        <?php } ?>
                    </div>
                    <div class="w-100" style="position: relative; height: 280px;">
                        <canvas id="appointmentsHourChart" class="w-100 h-100"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <h3 class="h5 mb-1">Balances por calendario</h3>
                        <p class="text-muted small mb-0">Totales de citas por tipo</p>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-light d-flex justify-content-between align-items-center" disabled>
                            <span><i class="bi bi-circle-fill me-2" style="color:#5D69F7;"></i> General</span>
                            <strong><?php echo number_format($typeTotals['general'], 0, ',', '.'); ?></strong>
                        </button>
                        <button type="button" class="btn btn-light d-flex justify-content-between align-items-center" disabled>
                            <span><i class="bi bi-circle-fill me-2" style="color:#2E86C1;"></i> Veterinario</span>
                            <strong><?php echo number_format($typeTotals['veterinario'], 0, ',', '.'); ?></strong>
                        </button>
                        <button type="button" class="btn btn-light d-flex justify-content-between align-items-center" disabled>
                            <span><i class="bi bi-circle-fill me-2" style="color:#8E44AD;"></i> Estético</span>
                            <strong><?php echo number_format($typeTotals['estetico'], 0, ',', '.'); ?></strong>
                        </button>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <div class="mb-3">
                        <h3 class="h5 mb-1">Balance por usuario</h3>
                        <p class="text-muted small mb-0">Top 3 usuarios con más citas</p>
                    </div>
                    <div class="list-group">
                        <?php if (empty($topUsers)) { ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-muted">Sin datos</span>
                                <span class="badge bg-secondary">0</span>
                            </div>
                        <?php } else { ?>
                            <?php foreach ($topUsers as $u) { ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span class="me-2" style="display:inline-block;width:10px;height:10px;border-radius:50%;background-color: <?php echo htmlspecialchars($u['color']); ?>;"></span>
                                        <span><?php echo htmlspecialchars($u['name']); ?></span>
                                    </div>
                                    <span class="badge bg-primary"><?php echo number_format((int) $u['total'], 0, ',', '.'); ?></span>
                                </div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <div class="mb-2">
                        <h3 class="h5 mb-1">Promedio de citas por día</h3>
                        <p class="text-muted small mb-0">
                            <?php if ($avgFirstDate) { ?>
                                Desde <?php echo date('d/m/Y', strtotime($avgFirstDate)); ?> hasta hoy (<?php echo (int) $avgDaysSpan; ?> días)
                            <?php } else { ?>
                                Sin datos de citas
                            <?php } ?>
                        </p>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between">
                        <div class="fs-3 fw-bold text-dark"><?php echo number_format($avgPerDay, 2, ',', '.'); ?></div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <div class="mb-2">
                        <h3 class="h5 mb-1">Distribución por día de la semana</h3>
                        <p class="text-muted small mb-0">Promedio por semana (día pico resaltado)</p>
                    </div>
                    <?php if (empty($weekdayAverages)) { ?>
                        <p class="text-muted mb-0">Sin datos suficientes</p>
                    <?php } else { ?>
                        <div class="list-group">
                            <?php foreach ($weekdayAverages as $w) { ?>
                                <?php $pct = ($maxAvg > 0) ? min(100, ($w['avg'] / $maxAvg) * 100) : 0; ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span><?php echo htmlspecialchars($w['name']); ?></span>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if ($maxAvg > 0 && abs($w['avg'] - $maxAvg) < 1e-9) { ?>
                                                <span class="badge bg-success">Pico</span>
                                            <?php } ?>
                                            <span class="badge bg-primary"><?php echo number_format($w['avg'], 2, ',', '.'); ?></span>
                                        </div>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo (int) $pct; ?>%" aria-valuenow="<?php echo (int) $pct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <div class="mb-2 d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="h5 mb-1">Día de mayor actividad</h3>
                            <p class="text-muted small mb-0">Fecha con más citas</p>
                        </div>
                        <?php if ($busiestDay) { ?>
                            <span class="badge bg-success"><?php echo date('d/m/Y', strtotime($busiestDay)); ?></span>
                        <?php } ?>
                    </div>
                    <?php if (! $busiestDay) { ?>
                        <p class="text-muted mb-0">Sin datos</p>
                    <?php } else { ?>
                        <div class="d-flex align-items-baseline justify-content-between mb-3">
                            <div class="fs-3 fw-bold text-dark"><?php echo number_format($busiestDayTotal, 0, ',', '.'); ?></div>
                            <span class="text-muted small">citas</span>
                        </div>
                        <div class="d-grid gap-2 mb-3">
                            <div class="d-flex justify-content-between"><span>General</span><strong><?php echo (int) $busiestDayTypeTotals['general']; ?></strong></div>
                            <div class="d-flex justify-content-between"><span>Veterinario</span><strong><?php echo (int) $busiestDayTypeTotals['veterinario']; ?></strong></div>
                            <div class="d-flex justify-content-between"><span>Estético</span><strong><?php echo (int) $busiestDayTypeTotals['estetico']; ?></strong></div>
                        </div>
                        <?php if (! empty($busiestDayHours)) { ?>
                            <div class="mb-2"><span class="text-muted small">Horas más concurridas</span></div>
                            <div class="list-group">
                                <?php foreach ($busiestDayHours as $hh) { ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><?php echo htmlspecialchars($hh['label']); ?></span>
                                        <span class="badge bg-primary"><?php echo (int) $hh['count']; ?></span>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
// Cargar Chart.js y script inline
$extraScripts = (
    '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.0/dist/chart.umd.min.js"></script>'.
    "\n<script>\n".
    'const ctx = document.getElementById("appointmentsChart").getContext("2d");'.
    "\nconst chartData = {\n  labels: ".json_encode($labels, JSON_UNESCAPED_UNICODE).",\n  datasets: [{\n    label: 'Citas completas por mes',\n    data: ".json_encode($values).",\n    borderColor: '#5D69F7',\n    backgroundColor: 'rgba(93, 105, 247, 0.15)',\n    fill: true,\n    tension: 0.3,\n    pointRadius: 3,\n    pointBackgroundColor: '#5D69F7',\n    pointBorderColor: '#5D69F7'\n  }]\n};\n".
    "\nconst chartOptions = {\n  responsive: true,\n  maintainAspectRatio: false,\n  plugins: {\n    legend: { display: false },\n    tooltip: {\n      callbacks: {\n        label: function(context) {\n          const v = context.parsed.y || 0;\n          return ' ' + v.toLocaleString('es-CO') + ' citas';\n        }\n      }\n    }\n  },\n  scales: {\n    x: {\n      title: { display: true, text: 'Mes' }\n    },\n    y: {\n      title: { display: true, text: 'Número de citas' },\n      beginAtZero: true,\n      ticks: { precision: 0 }\n    }\n  }\n};\n".
    "\nnew Chart(ctx, { type: 'line', data: chartData, options: chartOptions });\n".
    "\n// Segundo gráfico (barras con rangos)\n".
    'const rangeCtx = document.getElementById("appointmentsRangeChart").getContext("2d");'.
    "\nconst datasets = {\n  '12m': { labels: ".json_encode(array_values($labels12m), JSON_UNESCAPED_UNICODE).', data: '.json_encode(array_values($values12m))." },\n  '30d': { labels: ".json_encode(array_values($labels30d), JSON_UNESCAPED_UNICODE).', data: '.json_encode(array_values($values30d))." },\n  '7d': { labels: ".json_encode(array_values($labels7d), JSON_UNESCAPED_UNICODE).', data: '.json_encode(array_values($values7d))." }\n};\n".
    "\nconst makeBarConfig = (ds) => ({\n  labels: ds.labels,\n  datasets: [{\n    label: 'Citas',\n    data: ds.data,\n    backgroundColor: 'rgba(93, 105, 247, 0.5)',\n    borderColor: '#5D69F7',\n    borderWidth: 1,\n    maxBarThickness: 28,\n    borderRadius: 6\n  }]\n});\n".
    "\nconst rangeOptions = {\n  responsive: true,\n  maintainAspectRatio: false,\n  plugins: { legend: { display: false } },\n  scales: {\n    x: { grid: { display: false } },\n    y: { beginAtZero: true, ticks: { precision: 0 } }\n  }\n};\n".
    "\nlet currentRange = '12m';\nlet rangeChart = new Chart(rangeCtx, { type: 'bar', data: makeBarConfig(datasets[currentRange]), options: rangeOptions });\n".
    "\nfunction setActive(btn){\n  document.getElementById('btnRange12m').className = 'btn btn-secondary';\n  document.getElementById('btnRange30d').className = 'btn btn-secondary';\n  document.getElementById('btnRange7d').className = 'btn btn-secondary';\n  btn.className = 'btn btn-success';\n}\n".
    "\nfunction switchRange(r){\n  currentRange = r;\n  const ds = datasets[r];\n  rangeChart.data = makeBarConfig(ds);\n  rangeChart.update();\n}\n".
    "\ndocument.getElementById('btnRange12m').addEventListener('click', function(){ setActive(this); switchRange('12m'); });\n".
    "\ndocument.getElementById('btnRange30d').addEventListener('click', function(){ setActive(this); switchRange('30d'); });\n".
    "\ndocument.getElementById('btnRange7d').addEventListener('click', function(){ setActive(this); switchRange('7d'); });\n".
    "\n// Tercer gráfico: distribución por hora\n".
    'const hourCtx = document.getElementById("appointmentsHourChart").getContext("2d");'.
    "\nconst hourData = { labels: ".json_encode($hourLabels, JSON_UNESCAPED_UNICODE).", datasets: [{\n  label: 'Citas',\n  data: ".json_encode($hourValues).",\n  backgroundColor: 'rgba(46, 134, 193, 0.5)',\n  borderColor: '#2E86C1',\n  borderWidth: 1,\n  maxBarThickness: 20,\n  borderRadius: 4\n}]};\n".
    "\nconst hourOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 } } } };\n".
    "\nnew Chart(hourCtx, { type: 'bar', data: hourData, options: hourOptions });\n".
    "</script>\n"
);

include __DIR__.'/includes/footer.php';
?>


