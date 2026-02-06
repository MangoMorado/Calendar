<?php

use App\Models\Appointment;
use App\Models\Calendar;
use App\Models\User;

test('unauthenticated user cannot access analytics page', function () {
    $this->get(route('analytics'))
        ->assertRedirect(route('login'));
});

test('admin user can access analytics page', function () {
    $user = User::factory()->admin()->create();

    $response = $this->actingAs($user)->get(route('analytics'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('analytics')
            ->has('totalAppointments')
            ->has('appointmentsByMonth')
            ->has('balanceByCalendar')
            ->has('balanceByUser')
            ->has('peakHours')
            ->has('distributionByWeekday')
        );
});

test('mango user can access analytics page', function () {
    $user = User::factory()->mango()->create();

    $this->actingAs($user)
        ->get(route('analytics'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('analytics')
            ->has('filters')
        );
});

test('regular user cannot access analytics page', function () {
    $user = User::factory()->user()->create();

    $this->actingAs($user)
        ->get(route('analytics'))
        ->assertForbidden();
});

test('analytics returns expected metrics with appointments', function () {
    $user = User::factory()->admin()->create();
    $calendar = Calendar::factory()->create(['is_active' => true]);
    Appointment::factory()->count(3)->create([
        'calendar_id' => $calendar->id,
        'user_id' => $user->id,
        'start_time' => '2025-06-15 10:00:00',
        'end_time' => '2025-06-15 11:00:00',
    ]);

    $response = $this->actingAs($user)->get(route('analytics', [
        'start_date' => '2025-06-01',
        'end_date' => '2025-06-30',
    ]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('totalAppointments', 3)
            ->has('appointmentsByMonth')
            ->has('balanceByCalendar', 1)
            ->where('balanceByCalendar.0.calendar_name', $calendar->name)
        );
});

test('analytics applies date filters correctly', function () {
    $user = User::factory()->admin()->create();
    $calendar = Calendar::factory()->create(['is_active' => true]);
    $inRange = Appointment::factory()->create([
        'calendar_id' => $calendar->id,
        'start_time' => '2025-06-15 10:00:00',
        'end_time' => '2025-06-15 11:00:00',
    ]);
    $outOfRange = Appointment::factory()->create([
        'calendar_id' => $calendar->id,
        'start_time' => '2024-01-01 10:00:00',
        'end_time' => '2024-01-01 11:00:00',
    ]);

    $response = $this->actingAs($user)->get(route('analytics', [
        'start_date' => '2025-06-01',
        'end_date' => '2025-06-30',
    ]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('totalAppointments', 1)
            ->where('filters.start_date', '2025-06-01')
            ->where('filters.end_date', '2025-06-30')
        );
});
