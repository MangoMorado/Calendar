<?php

use App\Models\Appointment;
use App\Models\Calendar;
use App\Models\User;

test('calendar belongs to a user', function () {
    $user = User::factory()->create();
    $calendar = Calendar::factory()->create(['user_id' => $user->id]);

    expect($calendar->user)->toBeInstanceOf(User::class);
    expect($calendar->user->id)->toBe($user->id);
});

test('calendar can have no user', function () {
    $calendar = Calendar::factory()->withoutOwner()->create();

    expect($calendar->user)->toBeNull();
});

test('calendar has many appointments', function () {
    $calendar = Calendar::factory()->create();
    Appointment::factory()->count(5)->create(['calendar_id' => $calendar->id]);

    expect($calendar->appointments)->toHaveCount(5);
    expect($calendar->appointments->first())->toBeInstanceOf(Appointment::class);
});

test('calendar has is_active cast to boolean', function () {
    $calendar = Calendar::factory()->create(['is_active' => true]);

    expect($calendar->is_active)->toBeBool();
    expect($calendar->is_active)->toBeTrue();
});

test('calendar default color is set', function () {
    $calendar = Calendar::factory()->create();

    // El color por defecto se establece en la migración (#5D69F7)
    expect($calendar->color)->not->toBeNull();
    expect($calendar->color)->toBeString();
});

test('deleting calendar cascades to appointments', function () {
    $calendar = Calendar::factory()->create();
    $appointment = Appointment::factory()->create(['calendar_id' => $calendar->id]);

    $calendar->delete();

    $this->assertDatabaseMissing('appointments', [
        'id' => $appointment->id,
    ]);
});

test('calendar can be created with factory', function () {
    $calendar = Calendar::factory()->create();

    expect($calendar)->toBeInstanceOf(Calendar::class);
    expect($calendar->name)->not->toBeEmpty();
    expect($calendar->is_active)->toBeTrue();
});

test('calendar can be created as inactive', function () {
    $calendar = Calendar::factory()->inactive()->create();

    expect($calendar->is_active)->toBeFalse();
});
