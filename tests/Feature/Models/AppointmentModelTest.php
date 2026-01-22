<?php

use App\Models\Appointment;
use App\Models\Calendar;
use App\Models\User;

test('appointment belongs to a calendar', function () {
    $calendar = Calendar::factory()->create();
    $appointment = Appointment::factory()->create(['calendar_id' => $calendar->id]);

    expect($appointment->calendar)->toBeInstanceOf(Calendar::class);
    expect($appointment->calendar->id)->toBe($calendar->id);
});

test('appointment belongs to a user', function () {
    $user = User::factory()->create();
    $appointment = Appointment::factory()->create(['user_id' => $user->id]);

    expect($appointment->user)->toBeInstanceOf(User::class);
    expect($appointment->user->id)->toBe($user->id);
});

test('appointment can have no assigned user', function () {
    $appointment = Appointment::factory()->withoutUser()->create();

    expect($appointment->user)->toBeNull();
});

test('appointment has datetime casts', function () {
    $appointment = Appointment::factory()->create();

    // La aplicación usa CarbonImmutable según AppServiceProvider
    expect($appointment->start_time)->toBeInstanceOf(\Carbon\CarbonImmutable::class);
    expect($appointment->end_time)->toBeInstanceOf(\Carbon\CarbonImmutable::class);
});

test('appointment has all_day cast to boolean', function () {
    $appointment = Appointment::factory()->create(['all_day' => true]);

    expect($appointment->all_day)->toBeBool();
    expect($appointment->all_day)->toBeTrue();
});

test('appointment can be created as all day', function () {
    $appointment = Appointment::factory()->allDay()->create();

    expect($appointment->all_day)->toBeTrue();
    expect($appointment->start_time->format('H:i:s'))->toBe('00:00:00');
    expect($appointment->end_time->format('H:i:s'))->toBe('23:59:59');
});

test('appointment can be created in the past', function () {
    $appointment = Appointment::factory()->past()->create();

    expect($appointment->start_time->isPast())->toBeTrue();
});

test('appointment can be created in the future', function () {
    $appointment = Appointment::factory()->future()->create();

    expect($appointment->start_time->isFuture())->toBeTrue();
});

test('appointment end_time is after start_time', function () {
    $appointment = Appointment::factory()->create();

    expect($appointment->end_time->greaterThan($appointment->start_time))->toBeTrue();
});

test('deleting user does not delete appointments', function () {
    $user = User::factory()->create();
    $appointment = Appointment::factory()->create(['user_id' => $user->id]);

    $user->delete();

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
    ]);
    expect($appointment->fresh()->user_id)->toBeNull();
});
