<?php

use App\Http\Requests\AppointmentStoreRequest;
use App\Models\Calendar;
use App\Models\User;

test('appointment store request authorizes authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $request = AppointmentStoreRequest::create('/', 'POST', []);
    $request->setUserResolver(fn () => $user);

    expect($request->authorize())->toBeTrue();
});

// Nota: AppointmentStoreRequest siempre retorna true en authorize()
// La autenticación se maneja a nivel de middleware
test('appointment store request allows all authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $request = AppointmentStoreRequest::create('/', 'POST', []);
    $request->setUserResolver(fn () => $user);

    expect($request->authorize())->toBeTrue();
});

test('appointment store request validates required fields', function () {
    $request = new AppointmentStoreRequest;

    $validator = validator([], $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('title'))->toBeTrue();
    expect($validator->errors()->has('calendar_id'))->toBeTrue();
    expect($validator->errors()->has('user_id'))->toBeTrue();
    expect($validator->errors()->has('start_time'))->toBeTrue();
    expect($validator->errors()->has('end_time'))->toBeTrue();
});

test('appointment store request validates end_time is after start_time', function () {
    $request = new AppointmentStoreRequest;
    $calendar = Calendar::factory()->create();
    $user = User::factory()->create();

    $startTime = now()->addDay();
    $endTime = $startTime->copy()->subHour();

    $validator = validator([
        'title' => 'Test Appointment',
        'calendar_id' => $calendar->id,
        'user_id' => $user->id,
        'start_time' => $startTime->toDateTimeString(),
        'end_time' => $endTime->toDateTimeString(),
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('end_time'))->toBeTrue();
});

test('appointment store request validates calendar_id exists', function () {
    $request = new AppointmentStoreRequest;
    $user = User::factory()->create();

    $validator = validator([
        'title' => 'Test Appointment',
        'calendar_id' => 99999,
        'user_id' => $user->id,
        'start_time' => now()->addDay()->toDateTimeString(),
        'end_time' => now()->addDay()->addHour()->toDateTimeString(),
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('calendar_id'))->toBeTrue();
});

test('appointment store request validates user_id exists', function () {
    $request = new AppointmentStoreRequest;
    $calendar = Calendar::factory()->create();

    $validator = validator([
        'title' => 'Test Appointment',
        'calendar_id' => $calendar->id,
        'user_id' => 99999,
        'start_time' => now()->addDay()->toDateTimeString(),
        'end_time' => now()->addDay()->addHour()->toDateTimeString(),
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('user_id'))->toBeTrue();
});

test('appointment store request validates all_day as boolean', function () {
    $request = new AppointmentStoreRequest;
    $calendar = Calendar::factory()->create();
    $user = User::factory()->create();

    $validator = validator([
        'title' => 'Test Appointment',
        'calendar_id' => $calendar->id,
        'user_id' => $user->id,
        'start_time' => now()->addDay()->toDateTimeString(),
        'end_time' => now()->addDay()->addHour()->toDateTimeString(),
        'all_day' => true,
    ], $request->rules());

    expect($validator->fails())->toBeFalse();
});

test('appointment store request validates color format', function () {
    $request = new AppointmentStoreRequest;
    $calendar = Calendar::factory()->create();
    $user = User::factory()->create();

    $validator = validator([
        'title' => 'Test Appointment',
        'calendar_id' => $calendar->id,
        'user_id' => $user->id,
        'start_time' => now()->addDay()->toDateTimeString(),
        'end_time' => now()->addDay()->addHour()->toDateTimeString(),
        'color' => 'invalid-color',
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('color'))->toBeTrue();
});
