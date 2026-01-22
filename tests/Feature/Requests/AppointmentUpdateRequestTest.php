<?php

use App\Http\Requests\AppointmentUpdateRequest;
use App\Models\User;

test('appointment update request authorizes authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $request = AppointmentUpdateRequest::create('/', 'PATCH', []);
    $request->setUserResolver(fn () => $user);

    expect($request->authorize())->toBeTrue();
});

test('appointment update request allows partial updates', function () {
    $request = new AppointmentUpdateRequest;

    // Solo actualizar el título
    $validator = validator(['title' => 'Updated Title'], $request->rules());
    expect($validator->fails())->toBeFalse();

    // Solo actualizar la descripción
    $validator = validator(['description' => 'Updated Description'], $request->rules());
    expect($validator->fails())->toBeFalse();
});

test('appointment update request validates end_time is after start_time when both provided', function () {
    $request = new AppointmentUpdateRequest;

    $startTime = now()->addDay();
    $endTime = $startTime->copy()->subHour();

    $validator = validator([
        'start_time' => $startTime->toDateTimeString(),
        'end_time' => $endTime->toDateTimeString(),
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('end_time'))->toBeTrue();
});
