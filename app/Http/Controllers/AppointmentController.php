<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppointmentStoreRequest;
use App\Http\Requests\AppointmentUpdateRequest;
use App\Models\Appointment;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    /**
     * Display the specified appointment.
     */
    public function show(Appointment $appointment): Response
    {
        $appointment->load(['calendar', 'user']);

        return Inertia::render('appointments/show', [
            'appointment' => $appointment,
        ]);
    }

    /**
     * Store a newly created appointment in storage.
     */
    public function store(AppointmentStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Convertir all_day de string a boolean si viene como '1' o '0'
        if (isset($data['all_day'])) {
            if (is_string($data['all_day'])) {
                $data['all_day'] = $data['all_day'] === '1';
            }
        } else {
            $data['all_day'] = false;
        }

        // Si es todo el día, ajustar las horas
        if ($data['all_day']) {
            $startTime = \Carbon\Carbon::parse($data['start_time'])->startOfDay();
            $endTime = \Carbon\Carbon::parse($data['end_time'])->endOfDay();
            $data['start_time'] = $startTime;
            $data['end_time'] = $endTime;
        }

        Appointment::create($data);

        return redirect()->back()->with('success', 'Cita creada exitosamente.');
    }

    /**
     * Update the specified appointment in storage.
     */
    public function update(
        AppointmentUpdateRequest $request,
        Appointment $appointment
    ): RedirectResponse {
        $data = $request->validated();

        // Convertir all_day de string a boolean si viene como '1' o '0'
        if (isset($data['all_day'])) {
            if (is_string($data['all_day'])) {
                $data['all_day'] = $data['all_day'] === '1';
            }
        }

        // Si es todo el día, ajustar las horas
        if (isset($data['all_day']) && $data['all_day']) {
            if (isset($data['start_time'])) {
                $startTime = \Carbon\Carbon::parse($data['start_time'])->startOfDay();
                $data['start_time'] = $startTime;
            }
            if (isset($data['end_time'])) {
                $endTime = \Carbon\Carbon::parse($data['end_time'])->endOfDay();
                $data['end_time'] = $endTime;
            }
        }

        $appointment->update($data);

        return redirect()->back()->with('success', 'Cita actualizada exitosamente.');
    }
}
