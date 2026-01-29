<?php

use App\Models\Appointment;
use App\Models\Calendar;
use App\Models\User;

test('guests are redirected to login when accessing dashboard', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view dashboard', function () {
    $user = User::factory()->create();
    Calendar::factory()->count(3)->create(['is_active' => true]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('calendars')
            ->has('events')
            ->has('upcomingAppointments')
            ->has('users')
            ->has('calendarConfig')
            ->has('canCreateCalendar')
        );
});

test('dashboard shows only active calendars', function () {
    $user = User::factory()->create();
    $activeCalendar = Calendar::factory()->create(['is_active' => true]);
    $inactiveCalendar = Calendar::factory()->create(['is_active' => false]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('calendars', 1)
            ->where('calendars.0.id', $activeCalendar->id)
        );
});

test('dashboard shows appointments from active calendars only', function () {
    $user = User::factory()->create();
    $activeCalendar = Calendar::factory()->create(['is_active' => true]);
    $inactiveCalendar = Calendar::factory()->create(['is_active' => false]);

    $activeAppointment = Appointment::factory()->create([
        'calendar_id' => $activeCalendar->id,
    ]);
    $inactiveAppointment = Appointment::factory()->create([
        'calendar_id' => $inactiveCalendar->id,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('events', 1)
            ->where('events.0.id', (string) $activeAppointment->id)
        );
});

test('dashboard can filter appointments by calendar', function () {
    $user = User::factory()->create();
    $calendar1 = Calendar::factory()->create(['is_active' => true]);
    $calendar2 = Calendar::factory()->create(['is_active' => true]);

    $appointment1 = Appointment::factory()->create([
        'calendar_id' => $calendar1->id,
    ]);
    $appointment2 = Appointment::factory()->create([
        'calendar_id' => $calendar2->id,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard', ['calendar_id' => $calendar1->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('events', 1)
            ->where('events.0.id', (string) $appointment1->id)
            ->where('selectedCalendarId', (string) $calendar1->id)
        );
});

test('dashboard shows upcoming appointments', function () {
    $user = User::factory()->create();
    $calendar = Calendar::factory()->create(['is_active' => true]);

    $pastAppointment = Appointment::factory()->past()->create([
        'calendar_id' => $calendar->id,
    ]);
    $futureAppointment1 = Appointment::factory()->future()->create([
        'calendar_id' => $calendar->id,
    ]);
    $futureAppointment2 = Appointment::factory()->future()->create([
        'calendar_id' => $calendar->id,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('upcomingAppointments', 2)
            ->where('upcomingAppointments.0.id', function ($id) use ($futureAppointment1, $futureAppointment2) {
                return in_array($id, [$futureAppointment1->id, $futureAppointment2->id]);
            })
        );
});

test('dashboard limits upcoming appointments to 10', function () {
    $user = User::factory()->create();
    $calendar = Calendar::factory()->create(['is_active' => true]);

    Appointment::factory()->count(15)->future()->create([
        'calendar_id' => $calendar->id,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('upcomingAppointments', 10)
        );
});

test('dashboard formats events correctly for fullcalendar', function () {
    $user = User::factory()->create();
    $calendar = Calendar::factory()->create([
        'is_active' => true,
        'color' => '#FF5733',
    ]);
    $assignedUser = User::factory()->create();

    $appointment = Appointment::factory()->create([
        'calendar_id' => $calendar->id,
        'user_id' => $assignedUser->id,
        'title' => 'Test Appointment',
        'color' => '#00FF00',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('events', 1)
            ->where('events.0.title', 'Test Appointment')
            ->where('events.0.backgroundColor', '#00FF00')
            ->where('events.0.borderColor', '#00FF00')
            ->has('events.0.extendedProps')
            ->where('events.0.extendedProps.calendarId', $calendar->id)
            ->where('events.0.extendedProps.userId', $assignedUser->id)
        );
});

test('dashboard uses calendar color when appointment has no color', function () {
    $user = User::factory()->create();
    $calendar = Calendar::factory()->create([
        'is_active' => true,
        'color' => '#FF5733',
    ]);

    $appointment = Appointment::factory()->create([
        'calendar_id' => $calendar->id,
        'color' => null,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('events.0.backgroundColor', '#FF5733')
            ->where('events.0.borderColor', '#FF5733')
        );
});

test('dashboard shows all users for assignment', function () {
    $user = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('users', 3)
        );
});

test('dashboard shows calendars with visibility todos to any user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $calendar = Calendar::factory()->create([
        'is_active' => true,
        'visibility' => 'todos',
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('calendars', 1)
            ->where('calendars.0.id', $calendar->id)
        );
});

test('dashboard shows solo_yo calendar only to its owner', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $calendar = Calendar::factory()->create([
        'is_active' => true,
        'visibility' => 'solo_yo',
        'user_id' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('calendars', 1)
            ->where('calendars.0.id', $calendar->id)
        );

    $this->actingAs($otherUser)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('calendars', 0)
        );
});

test('dashboard shows appointments only from visible calendars', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $visibleCalendar = Calendar::factory()->create([
        'is_active' => true,
        'visibility' => 'todos',
    ]);
    $soloYoCalendar = Calendar::factory()->create([
        'is_active' => true,
        'visibility' => 'solo_yo',
        'user_id' => $owner->id,
    ]);

    $visibleAppointment = Appointment::factory()->create(['calendar_id' => $visibleCalendar->id]);
    $soloYoAppointment = Appointment::factory()->create(['calendar_id' => $soloYoCalendar->id]);

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('events', 2)
        );

    $this->actingAs($otherUser)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('events', 1)
            ->where('events.0.id', (string) $visibleAppointment->id)
        );
});

test('dashboard returns default calendarConfig when no calendars visible', function () {
    $user = User::factory()->create();
    Calendar::factory()->create([
        'is_active' => true,
        'visibility' => 'solo_yo',
        'user_id' => User::factory()->create()->id,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('calendars', 0)
            ->where('calendarConfig.start_time', '06:00')
            ->where('calendarConfig.end_time', '19:00')
            ->where('calendarConfig.slot_duration', 30)
            ->where('calendarConfig.business_days', [1, 2, 3, 4, 5, 6])
        );
});
