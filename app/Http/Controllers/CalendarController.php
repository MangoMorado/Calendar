<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalendarStoreRequest;
use App\Http\Requests\CalendarUpdateRequest;
use App\Models\Calendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $calendars = Calendar::with('user')
            ->latest()
            ->paginate(15);

        return Inertia::render('calendars/index', [
            'calendars' => $calendars,
            'canCreate' => $request->user()?->can('create', Calendar::class) ?? false,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Calendar::class);

        return Inertia::render('calendars/create', [
            'timezones' => $this->timezonesList(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CalendarStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $data['is_active'] ?? true;

        $calendar = Calendar::create($data);

        return redirect()
            ->route('calendars.index')
            ->with('success', 'Calendario creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Calendar $calendar): Response
    {
        $calendar->load('user');

        return Inertia::render('calendars/show', [
            'calendar' => $calendar,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Calendar $calendar): Response
    {
        $this->authorize('update', $calendar);

        return Inertia::render('calendars/edit', [
            'calendar' => $calendar,
            'timezones' => $this->timezonesList(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CalendarUpdateRequest $request, Calendar $calendar): RedirectResponse
    {
        $data = $request->validated();

        // Convertir '1'/'0' a boolean si viene como string
        if (isset($data['is_active']) && is_string($data['is_active'])) {
            $data['is_active'] = $data['is_active'] === '1';
        }

        $calendar->update($data);

        return redirect()
            ->route('calendars.index')
            ->with('success', 'Calendario actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Calendar $calendar): RedirectResponse
    {
        $this->authorize('delete', $calendar);

        $calendar->delete();

        return redirect()
            ->route('calendars.index')
            ->with('success', 'Calendario eliminado exitosamente.');
    }

    /**
     * Lista de zonas horarias para selects (América y UTC).
     *
     * @return array<int, string>
     */
    private function timezonesList(): array
    {
        $identifiers = \DateTimeZone::listIdentifiers(\DateTimeZone::AMERICA);
        $identifiers[] = 'UTC';
        sort($identifiers);

        return array_combine($identifiers, $identifiers);
    }
}
