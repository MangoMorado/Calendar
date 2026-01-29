<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Calendar;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(Request $request): Response
    {
        $selectedCalendarId = $request->get('calendar_id');
        $userId = $request->user()?->id;

        $visibleCalendarQuery = function ($query) use ($userId) {
            $query->where('is_active', true)
                ->where(function ($q) use ($userId) {
                    $q->where('visibility', 'todos')
                        ->orWhere(function ($q2) use ($userId) {
                            $q2->where('visibility', 'solo_yo')
                                ->where('user_id', $userId);
                        });
                });
        };

        $calendars = Calendar::query()
            ->where('is_active', true)
            ->where(function ($q) use ($userId) {
                $q->where('visibility', 'todos')
                    ->orWhere(function ($q2) use ($userId) {
                        $q2->where('visibility', 'solo_yo')
                            ->where('user_id', $userId);
                    });
            })
            ->with('user')
            ->orderBy('name')
            ->get();

        $appointmentsQuery = Appointment::with(['calendar', 'user'])
            ->whereHas('calendar', $visibleCalendarQuery);

        if ($selectedCalendarId) {
            $appointmentsQuery->where('calendar_id', $selectedCalendarId);
        }

        // Obtener todas las citas para el calendario
        $appointments = $appointmentsQuery
            ->orderBy('start_time')
            ->get();

        // Formatear citas para FullCalendar
        $events = $appointments->map(function ($appointment) {
            return [
                'id' => (string) $appointment->id,
                'title' => $appointment->title,
                'start' => $appointment->start_time->toIso8601String(),
                'end' => $appointment->end_time->toIso8601String(),
                'description' => $appointment->description,
                'allDay' => $appointment->all_day,
                'backgroundColor' => $appointment->color ?? $appointment->calendar->color ?? '#5D69F7',
                'borderColor' => $appointment->color ?? $appointment->calendar->color ?? '#5D69F7',
                'extendedProps' => [
                    'calendarId' => $appointment->calendar_id,
                    'calendarName' => $appointment->calendar->name,
                    'userId' => $appointment->user_id,
                    'userName' => $appointment->user?->name ?? 'Sin asignar',
                ],
            ];
        });

        $upcomingAppointments = Appointment::with(['calendar', 'user'])
            ->whereHas('calendar', $visibleCalendarQuery)
            ->where('start_time', '>=', now())
            ->orderBy('start_time')
            ->limit(10)
            ->get()
            ->map(function ($appointment) {
                $use24h = ($appointment->calendar->time_format ?? '12') === '24';
                $dateFormat = $use24h ? 'd/m/Y H:i' : 'd/m/Y h:i A';

                return [
                    'id' => $appointment->id,
                    'title' => $appointment->title,
                    'start_time' => $appointment->start_time->format('Y-m-d H:i:s'),
                    'start_time_formatted' => $appointment->start_time->format($dateFormat),
                    'description' => $appointment->description,
                    'color' => $appointment->color ?? $appointment->calendar->color ?? '#5D69F7',
                    'calendar_name' => $appointment->calendar->name,
                    'user_name' => $appointment->user?->name ?? 'Sin asignar',
                ];
            });

        $users = User::orderBy('name')->get(['id', 'name']);

        $calendarConfig = $this->buildCalendarConfig($calendars);
        $canCreateCalendar = $request->user()?->can('create', Calendar::class) ?? false;

        return Inertia::render('dashboard', [
            'events' => $events,
            'calendars' => $calendars,
            'selectedCalendarId' => $selectedCalendarId,
            'upcomingAppointments' => $upcomingAppointments,
            'users' => $users,
            'calendarConfig' => $calendarConfig,
            'canCreateCalendar' => $canCreateCalendar,
        ]);
    }

    /**
     * Construir configuración unificada para el calendario general.
     * business_days = unión de todos; resto = valor común o por defecto.
     *
     * @param  \Illuminate\Support\Collection<int, Calendar>  $calendars
     * @return array{start_time: string, end_time: string, slot_duration: int, business_days: int[], time_format: string, timezone: string}
     */
    private function buildCalendarConfig($calendars): array
    {
        $defaults = [
            'start_time' => '06:00',
            'end_time' => '19:00',
            'slot_duration' => 30,
            'time_format' => '12',
            'timezone' => 'America/Bogota',
            'business_days' => [1, 2, 3, 4, 5, 6],
        ];

        if ($calendars->isEmpty()) {
            return $defaults;
        }

        $businessDays = $calendars->pluck('business_days')->flatten()->unique()->sort()->values()->all();
        $businessDays = array_values(array_map('intval', $businessDays));

        $startTimes = $calendars->pluck('start_time')->map(fn ($t) => is_string($t) ? substr($t, 0, 5) : $t)->unique();
        $endTimes = $calendars->pluck('end_time')->map(fn ($t) => is_string($t) ? substr($t, 0, 5) : $t)->unique();
        $slotDurations = $calendars->pluck('slot_duration')->unique();
        $timeFormats = $calendars->pluck('time_format')->unique();
        $timezones = $calendars->pluck('timezone')->unique();

        return [
            'start_time' => $startTimes->count() === 1 ? $startTimes->first() : $defaults['start_time'],
            'end_time' => $endTimes->count() === 1 ? $endTimes->first() : $defaults['end_time'],
            'slot_duration' => $slotDurations->count() === 1 ? (int) $slotDurations->first() : $defaults['slot_duration'],
            'time_format' => $timeFormats->count() === 1 ? $timeFormats->first() : $defaults['time_format'],
            'timezone' => $timezones->count() === 1 ? $timezones->first() : $defaults['timezone'],
            'business_days' => $businessDays ?: $defaults['business_days'],
        ];
    }
}
