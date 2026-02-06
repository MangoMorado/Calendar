<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    private const WEEKDAY_NAMES = [
        1 => 'Domingo',
        2 => 'Lunes',
        3 => 'Martes',
        4 => 'Miércoles',
        5 => 'Jueves',
        6 => 'Viernes',
        7 => 'Sábado',
    ];

    private function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    /**
     * Muestra el dashboard de analíticas de citas.
     * Solo accesible para Admin y Mango.
     */
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $endDate = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : now()->endOfDay();
        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : now()->subYear()->startOfDay();

        $baseQuery = Appointment::query()
            ->whereHas('calendar', fn ($q) => $q->where('include_in_analytics', true))
            ->whereBetween('start_time', [$startDate, $endDate]);

        $totalAppointments = $baseQuery->count();
        $daysInRange = max(1, $startDate->diffInDays($endDate) + 1);
        $monthsInRange = max(1, $startDate->diffInMonths($endDate) + 1);
        $yearsInRange = max(1, $startDate->floatDiffInYears($endDate));

        $secondsExpr = $this->isSqlite()
            ? '(julianday(end_time) - julianday(start_time)) * 86400'
            : 'TIMESTAMPDIFF(SECOND, start_time, end_time)';
        $timeBalanceSeconds = (clone $baseQuery)
            ->selectRaw("SUM({$secondsExpr}) as total")
            ->value('total') ?? 0;
        $timeBalanceHours = round((float) $timeBalanceSeconds / 3600, 2);

        $appointmentsByMonth = $this->getAppointmentsByMonth($startDate, $endDate);
        $balanceByCalendar = $this->getBalanceByCalendar($startDate, $endDate);
        $balanceByUser = $this->getBalanceByUser($startDate, $endDate);
        $peakHours = $this->getPeakHours($startDate, $endDate);
        $distributionByWeekday = $this->getDistributionByWeekday($startDate, $endDate);

        $mostActiveWeekday = collect($distributionByWeekday)->sortByDesc('count')->first();
        $mostActiveWeekdayName = $mostActiveWeekday?->name ?? '—';

        $minutesExpr = $this->isSqlite()
            ? '(julianday(end_time) - julianday(start_time)) * 1440'
            : 'TIMESTAMPDIFF(MINUTE, start_time, end_time)';
        $avgDurationMinutes = (clone $baseQuery)
            ->selectRaw("AVG({$minutesExpr}) as avg_duration")
            ->value('avg_duration');
        $avgDurationMinutes = $avgDurationMinutes !== null ? round((float) $avgDurationMinutes, 1) : 0;

        $allDayCount = (clone $baseQuery)->where('all_day', true)->count();

        return Inertia::render('analytics', [
            'filters' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'totalAppointments' => $totalAppointments,
            'timeBalanceHours' => $timeBalanceHours,
            'appointmentsByMonth' => $appointmentsByMonth,
            'balanceByCalendar' => $balanceByCalendar,
            'balanceByUser' => $balanceByUser,
            'avgPerDay' => round($totalAppointments / $daysInRange, 2),
            'avgPerMonth' => round($totalAppointments / $monthsInRange, 2),
            'avgPerYear' => round($totalAppointments / $yearsInRange, 2),
            'peakHours' => $peakHours,
            'distributionByWeekday' => $distributionByWeekday,
            'mostActiveWeekday' => $mostActiveWeekdayName,
            'avgDurationMinutes' => $avgDurationMinutes,
            'allDayCount' => $allDayCount,
        ]);
    }

    /**
     * @param  array{month: int, year: int, count: int}[]  $result
     */
    private function getAppointmentsByMonth(CarbonInterface $start, CarbonInterface $end): array
    {
        $yearExpr = $this->isSqlite() ? "cast(strftime('%Y', start_time) as int)" : 'YEAR(start_time)';
        $monthExpr = $this->isSqlite() ? "cast(strftime('%m', start_time) as int)" : 'MONTH(start_time)';

        $rows = Appointment::query()
            ->whereHas('calendar', fn ($q) => $q->where('include_in_analytics', true))
            ->whereBetween('start_time', [$start, $end])
            ->selectRaw("{$yearExpr} as year, {$monthExpr} as month, COUNT(*) as count")
            ->groupBy(DB::raw($yearExpr), DB::raw($monthExpr))
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        return $rows->map(fn ($r) => [
            'month' => (int) $r->month,
            'year' => (int) $r->year,
            'count' => (int) $r->count,
        ])->values()->all();
    }

    /**
     * @return array{calendar_id: int, calendar_name: string, count: int, hours: float}[]
     */
    private function getBalanceByCalendar(CarbonInterface $start, CarbonInterface $end): array
    {
        $secondsExpr = $this->isSqlite()
            ? '(julianday(appointments.end_time) - julianday(appointments.start_time)) * 86400'
            : 'TIMESTAMPDIFF(SECOND, appointments.start_time, appointments.end_time)';

        $rows = Appointment::query()
            ->join('calendars', 'appointments.calendar_id', '=', 'calendars.id')
            ->where('calendars.include_in_analytics', true)
            ->whereBetween('appointments.start_time', [$start, $end])
            ->selectRaw("appointments.calendar_id, calendars.name as calendar_name, COUNT(*) as count, SUM({$secondsExpr}) as total_seconds")
            ->groupBy('appointments.calendar_id', 'calendars.name')
            ->orderByDesc('count')
            ->get();

        return $rows->map(fn ($r) => [
            'calendar_id' => (int) $r->calendar_id,
            'calendar_name' => $r->calendar_name,
            'count' => (int) $r->count,
            'hours' => round((float) $r->total_seconds / 3600, 2),
        ])->values()->all();
    }

    /**
     * @return array{user_id: int|null, user_name: string, count: int, hours: float}[]
     */
    private function getBalanceByUser(CarbonInterface $start, CarbonInterface $end): array
    {
        $secondsExpr = $this->isSqlite()
            ? '(julianday(appointments.end_time) - julianday(appointments.start_time)) * 86400'
            : 'TIMESTAMPDIFF(SECOND, appointments.start_time, appointments.end_time)';
        $coalesceExpr = $this->isSqlite() ? "COALESCE(users.name, 'Sin asignar')" : 'COALESCE(users.name, "Sin asignar")';

        $rows = Appointment::query()
            ->join('calendars', 'appointments.calendar_id', '=', 'calendars.id')
            ->leftJoin('users', 'appointments.user_id', '=', 'users.id')
            ->where('calendars.include_in_analytics', true)
            ->whereBetween('appointments.start_time', [$start, $end])
            ->selectRaw("appointments.user_id, {$coalesceExpr} as user_name, COUNT(*) as count, SUM({$secondsExpr}) as total_seconds")
            ->groupBy('appointments.user_id', 'users.name')
            ->orderByDesc('count')
            ->get();

        return $rows->map(fn ($r) => [
            'user_id' => $r->user_id !== null ? (int) $r->user_id : null,
            'user_name' => $r->user_name ?? 'Sin asignar',
            'count' => (int) $r->count,
            'hours' => round((float) ($r->total_seconds ?? 0) / 3600, 2),
        ])->values()->all();
    }

    /**
     * @return array{hour: int, count: int}[]
     */
    private function getPeakHours(CarbonInterface $start, CarbonInterface $end): array
    {
        $hourExpr = $this->isSqlite() ? "cast(strftime('%H', start_time) as int)" : 'HOUR(start_time)';

        $rows = Appointment::query()
            ->whereHas('calendar', fn ($q) => $q->where('include_in_analytics', true))
            ->whereBetween('start_time', [$start, $end])
            ->selectRaw("{$hourExpr} as hour, COUNT(*) as count")
            ->groupBy(DB::raw($hourExpr))
            ->orderBy('hour')
            ->get();

        $map = $rows->keyBy('hour');
        $result = [];
        for ($h = 0; $h < 24; $h++) {
            $result[] = [
                'hour' => $h,
                'count' => (int) ($map->get($h)?->count ?? 0),
            ];
        }

        return $result;
    }

    /**
     * @return array{weekday: int, name: string, count: int}[]
     */
    private function getDistributionByWeekday(CarbonInterface $start, CarbonInterface $end): array
    {
        $weekdayExpr = $this->isSqlite()
            ? '(cast(strftime(\'%w\', start_time) as int) + 1)'
            : 'DAYOFWEEK(start_time)';

        $rows = Appointment::query()
            ->whereHas('calendar', fn ($q) => $q->where('include_in_analytics', true))
            ->whereBetween('start_time', [$start, $end])
            ->selectRaw("{$weekdayExpr} as weekday, COUNT(*) as count")
            ->groupBy(DB::raw($weekdayExpr))
            ->orderBy('weekday')
            ->get();

        $map = $rows->keyBy('weekday');
        $result = [];
        for ($w = 1; $w <= 7; $w++) {
            $count = (int) ($map->get((string) $w)?->count ?? $map->get($w)?->count ?? 0);
            $result[] = [
                'weekday' => $w,
                'name' => self::WEEKDAY_NAMES[$w] ?? '—',
                'count' => $count,
            ];
        }

        return $result;
    }
}
