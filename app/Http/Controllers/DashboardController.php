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

        // Obtener todos los calendarios activos
        $calendars = Calendar::where('is_active', true)
            ->with('user')
            ->orderBy('name')
            ->get();

        // Obtener citas de calendarios activos
        $appointmentsQuery = Appointment::with(['calendar', 'user'])
            ->whereHas('calendar', function ($query) {
                $query->where('is_active', true);
            });

        // Filtrar por calendario específico si se selecciona uno
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

        // Obtener próximas citas (próximas 10 citas)
        $upcomingAppointments = Appointment::with(['calendar', 'user'])
            ->whereHas('calendar', function ($query) {
                $query->where('is_active', true);
            })
            ->where('start_time', '>=', now())
            ->orderBy('start_time')
            ->limit(10)
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'title' => $appointment->title,
                    'start_time' => $appointment->start_time->format('Y-m-d H:i:s'),
                    'start_time_formatted' => $appointment->start_time->format('d/m/Y H:i'),
                    'description' => $appointment->description,
                    'color' => $appointment->color ?? $appointment->calendar->color ?? '#5D69F7',
                    'calendar_name' => $appointment->calendar->name,
                    'user_name' => $appointment->user?->name ?? 'Sin asignar',
                ];
            });

        // Obtener usuarios para el select de asignación
        $users = User::orderBy('name')->get(['id', 'name']);

        return Inertia::render('dashboard', [
            'events' => $events,
            'calendars' => $calendars,
            'selectedCalendarId' => $selectedCalendarId,
            'upcomingAppointments' => $upcomingAppointments,
            'users' => $users,
        ]);
    }
}
