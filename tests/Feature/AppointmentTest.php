<?php

use App\Models\Appointment;
use App\Models\Calendar;
use App\Models\User;

test('guests cannot create appointments', function () {
    $calendar = Calendar::factory()->create();
    $user = User::factory()->create();

    $this->post(route('appointments.store'), [
        'title' => 'Test Appointment',
        'calendar_id' => $calendar->id,
        'user_id' => $user->id,
        'start_time' => now()->addDay()->toDateTimeString(),
        'end_time' => now()->addDay()->addHour()->toDateTimeString(),
    ])
        ->assertRedirect(route('login'));
});

test('authenticated users can create appointments', function () {
    $user = User::factory()->create();
    $calendar = Calendar::factory()->create();
    $assignedUser = User::factory()->create();

    $startTime = now()->addDay();
    $endTime = $startTime->copy()->addHour();

    $this->actingAs($user)
        ->post(route('appointments.store'), [
            'title' => 'Test Appointment',
            'description' => 'Test Description',
            'calendar_id' => $calendar->id,
            'user_id' => $assignedUser->id,
            'start_time' => $startTime->toDateTimeString(),
            'end_time' => $endTime->toDateTimeString(),
            'all_day' => false,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('appointments', [
        'title' => 'Test Appointment',
        'description' => 'Test Description',
        'calendar_id' => $calendar->id,
        'user_id' => $assignedUser->id,
        'all_day' => false,
    ]);
});

test('appointment store validates required fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('appointments.store'), [])
        ->assertSessionHasErrors(['title', 'calendar_id', 'user_id', 'start_time', 'end_time']);
});

test('appointment store validates calendar exists', function () {
    $user = User::factory()->create();
    $assignedUser = User::factory()->create();

    $this->actingAs($user)
        ->post(route('appointments.store'), [
            'title' => 'Test Appointment',
            'calendar_id' => 99999,
            'user_id' => $assignedUser->id,
            'start_time' => now()->addDay()->toDateTimeString(),
            'end_time' => now()->addDay()->addHour()->toDateTimeString(),
        ])
        ->assertSessionHasErrors(['calendar_id']);
});

test('appointment store validates user exists', function () {
    $user = User::factory()->create();
    $calendar = Calendar::factory()->create();

    $this->actingAs($user)
        ->post(route('appointments.store'), [
            'title' => 'Test Appointment',
            'calendar_id' => $calendar->id,
            'user_id' => 99999,
            'start_time' => now()->addDay()->toDateTimeString(),
            'end_time' => now()->addDay()->addHour()->toDateTimeString(),
        ])
        ->assertSessionHasErrors(['user_id']);
});

test('appointment store validates end_time is after start_time', function () {
    $user = User::factory()->create();
    $calendar = Calendar::factory()->create();
    $assignedUser = User::factory()->create();

    $startTime = now()->addDay();
    $endTime = $startTime->copy()->subHour();

    $this->actingAs($user)
        ->post(route('appointments.store'), [
            'title' => 'Test Appointment',
            'calendar_id' => $calendar->id,
            'user_id' => $assignedUser->id,
            'start_time' => $startTime->toDateTimeString(),
            'end_time' => $endTime->toDateTimeString(),
        ])
        ->assertSessionHasErrors(['end_time']);
});

test('appointment store validates color format', function () {
    $user = User::factory()->create();
    $calendar = Calendar::factory()->create();
    $assignedUser = User::factory()->create();

    $startTime = now()->addDay();
    $endTime = $startTime->copy()->addHour();

    $this->actingAs($user)
        ->post(route('appointments.store'), [
            'title' => 'Test Appointment',
            'calendar_id' => $calendar->id,
            'user_id' => $assignedUser->id,
            'start_time' => $startTime->toDateTimeString(),
            'end_time' => $endTime->toDateTimeString(),
            'color' => 'invalid-color',
        ])
        ->assertSessionHasErrors(['color']);
});

test('appointment can be created as all day', function () {
    $user = User::factory()->create();
    $calendar = Calendar::factory()->create();
    $assignedUser = User::factory()->create();

    $startTime = now()->addDay()->startOfDay();
    $endTime = $startTime->copy()->endOfDay();

    $this->actingAs($user)
        ->post(route('appointments.store'), [
            'title' => 'All Day Appointment',
            'calendar_id' => $calendar->id,
            'user_id' => $assignedUser->id,
            'start_time' => $startTime->toDateTimeString(),
            'end_time' => $endTime->toDateTimeString(),
            'all_day' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $appointment = Appointment::where('title', 'All Day Appointment')->first();
    expect($appointment->all_day)->toBeTrue();
    expect($appointment->start_time->format('H:i:s'))->toBe('00:00:00');
    expect($appointment->end_time->format('H:i:s'))->toBe('23:59:59');
});

test('guests cannot update appointments', function () {
    $appointment = Appointment::factory()->create();

    $this->patch(route('appointments.update', $appointment), [
        'title' => 'Updated Title',
    ])
        ->assertRedirect(route('login'));
});

test('authenticated users can update appointments', function () {
    $user = User::factory()->create();
    $appointment = Appointment::factory()->create();
    $newCalendar = Calendar::factory()->create();
    $newUser = User::factory()->create();

    $startTime = now()->addDays(2);
    $endTime = $startTime->copy()->addHours(2);

    $this->actingAs($user)
        ->patch(route('appointments.update', $appointment), [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'calendar_id' => $newCalendar->id,
            'user_id' => $newUser->id,
            'start_time' => $startTime->toDateTimeString(),
            'end_time' => $endTime->toDateTimeString(),
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'title' => 'Updated Title',
        'description' => 'Updated Description',
        'calendar_id' => $newCalendar->id,
        'user_id' => $newUser->id,
    ]);
});

test('appointment update validates end_time is after start_time', function () {
    $user = User::factory()->create();
    $appointment = Appointment::factory()->create();

    $startTime = now()->addDay();
    $endTime = $startTime->copy()->subHour();

    $this->actingAs($user)
        ->patch(route('appointments.update', $appointment), [
            'start_time' => $startTime->toDateTimeString(),
            'end_time' => $endTime->toDateTimeString(),
        ])
        ->assertSessionHasErrors(['end_time']);
});

test('appointment can be updated to all day', function () {
    $user = User::factory()->create();
    $appointment = Appointment::factory()->create(['all_day' => false]);

    $startTime = now()->addDay()->startOfDay();
    $endTime = $startTime->copy()->endOfDay();

    $this->actingAs($user)
        ->patch(route('appointments.update', $appointment), [
            'start_time' => $startTime->toDateTimeString(),
            'end_time' => $endTime->toDateTimeString(),
            'all_day' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $appointment->refresh();
    expect($appointment->all_day)->toBeTrue();
});

test('appointment can be updated via drag and drop', function () {
    $user = User::factory()->create();
    $appointment = Appointment::factory()->create();

    $newStartTime = now()->addDays(3);
    $newEndTime = $newStartTime->copy()->addHour();

    $this->actingAs($user)
        ->patch(route('appointments.update', $appointment), [
            'start_time' => $newStartTime->toIso8601String(),
            'end_time' => $newEndTime->toIso8601String(),
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $appointment->refresh();
    expect($appointment->start_time->format('Y-m-d H:i:s'))
        ->toBe($newStartTime->format('Y-m-d H:i:s'));
});
