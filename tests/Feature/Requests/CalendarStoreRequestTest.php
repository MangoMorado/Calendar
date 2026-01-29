<?php

use App\Http\Requests\CalendarStoreRequest;
use App\Models\User;

test('calendar store request authorizes admin users', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $request = CalendarStoreRequest::create('/', 'POST', []);
    $request->setUserResolver(fn () => $admin);

    expect($request->authorize())->toBeTrue();
});

test('calendar store request denies regular users', function () {
    $user = User::factory()->user()->create();

    $this->actingAs($user);

    $request = CalendarStoreRequest::create('/', 'POST', []);
    $request->setUserResolver(fn () => $user);

    expect($request->authorize())->toBeFalse();
});

test('calendar store request denies guests', function () {
    $request = CalendarStoreRequest::create('/', 'POST', []);
    $request->setUserResolver(fn () => null);

    expect($request->authorize())->toBeFalse();
});

test('calendar store request validates name is required', function () {
    $admin = User::factory()->admin()->create();
    $request = new CalendarStoreRequest;

    $validator = validator([], $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('name'))->toBeTrue();
});

test('calendar store request validates name is string', function () {
    $request = new CalendarStoreRequest;

    $validator = validator(['name' => 123], $request->rules());

    expect($validator->fails())->toBeTrue();
});

test('calendar store request validates name max length', function () {
    $request = new CalendarStoreRequest;

    $validator = validator(['name' => str_repeat('a', 256)], $request->rules());

    expect($validator->fails())->toBeTrue();
});

test('calendar store request validates color format', function () {
    $request = new CalendarStoreRequest;

    $validColors = ['#FF5733', '#00FF00', '#000000', '#FFFFFF'];
    $invalidColors = ['FF5733', '#GGGGGG', 'red', '#FF', '#FFFFF'];

    foreach ($validColors as $color) {
        $validator = validator(['name' => 'Test', 'color' => $color], $request->rules());
        expect($validator->errors()->has('color'))->toBeFalse();
    }

    foreach ($invalidColors as $color) {
        $validator = validator(['name' => 'Test', 'color' => $color], $request->rules());
        expect($validator->errors()->has('color'))->toBeTrue();
    }
});

test('calendar store request validates user_id exists', function () {
    $request = new CalendarStoreRequest;

    $validator = validator([
        'name' => 'Test',
        'user_id' => 99999,
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('user_id'))->toBeTrue();
});

test('calendar store request allows null user_id', function () {
    $request = new CalendarStoreRequest;

    $validator = validator([
        'name' => 'Test',
        'user_id' => null,
        'start_time' => '06:00',
        'end_time' => '19:00',
        'slot_duration' => 30,
        'time_format' => '12',
        'timezone' => 'America/Bogota',
        'business_days' => [1, 2, 3, 4, 5, 6],
        'visibility' => 'todos',
    ], $request->rules());

    expect($validator->fails())->toBeFalse();
});

test('calendar store request validates description max length', function () {
    $request = new CalendarStoreRequest;

    $validator = validator([
        'name' => 'Test',
        'description' => str_repeat('a', 1001),
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
});
