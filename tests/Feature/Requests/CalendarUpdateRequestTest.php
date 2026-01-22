<?php

use App\Http\Requests\CalendarUpdateRequest;
use App\Models\User;

test('calendar update request authorizes admin users', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $request = CalendarUpdateRequest::create('/', 'PUT', []);
    $request->setUserResolver(fn () => $admin);

    expect($request->authorize())->toBeTrue();
});

test('calendar update request denies regular users', function () {
    $user = User::factory()->user()->create();

    $this->actingAs($user);

    $request = CalendarUpdateRequest::create('/', 'PUT', []);
    $request->setUserResolver(fn () => $user);

    expect($request->authorize())->toBeFalse();
});

test('calendar update request validates is_active as boolean', function () {
    $request = new CalendarUpdateRequest;

    $validator = validator(['is_active' => true], $request->rules());
    expect($validator->fails())->toBeFalse();

    $validator = validator(['is_active' => false], $request->rules());
    expect($validator->fails())->toBeFalse();

    $validator = validator(['is_active' => 'not-boolean'], $request->rules());
    expect($validator->fails())->toBeTrue();
});

test('calendar update request allows partial updates', function () {
    $request = new CalendarUpdateRequest;

    // Solo actualizar el nombre
    $validator = validator(['name' => 'Updated Name'], $request->rules());
    expect($validator->fails())->toBeFalse();

    // Solo actualizar el color
    $validator = validator(['color' => '#FF5733'], $request->rules());
    expect($validator->fails())->toBeFalse();
});
