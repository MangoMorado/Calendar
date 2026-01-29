<?php

use App\Http\Requests\UserStoreRequest;
use App\Models\User;

test('user store request denies guests', function () {
    $request = UserStoreRequest::create(route('users.store'), 'POST', []);
    $request->setUserResolver(fn () => null);

    expect($request->authorize())->toBeFalse();
});

test('user store request authorizes admin and mango', function () {
    $admin = User::factory()->admin()->create();
    $mango = User::factory()->mango()->create();

    $req = UserStoreRequest::create(route('users.store'), 'POST', []);
    $req->setUserResolver(fn () => $admin);
    expect($req->authorize())->toBeTrue();

    $req->setUserResolver(fn () => $mango);
    expect($req->authorize())->toBeTrue();
});

test('user store request validates name email password role required', function () {
    $admin = User::factory()->admin()->create();
    $request = new UserStoreRequest;
    $request->setUserResolver(fn () => $admin);

    $v = validator([], $request->rules());
    expect($v->fails())->toBeTrue();
    expect($v->errors()->has(['name', 'email', 'password', 'role']))->toBeTrue();
});

test('user store request validates password min 8 and confirmed', function () {
    $admin = User::factory()->admin()->create();
    $request = new UserStoreRequest;
    $request->setUserResolver(fn () => $admin);

    $v = validator([
        'name' => 'Test',
        'email' => 'test@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
        'role' => 'user',
    ], $request->rules());
    expect($v->fails())->toBeTrue();

    $v = validator([
        'name' => 'Test',
        'email' => 'test@example.com',
        'password' => 'password1',
        'role' => 'user',
    ], $request->rules());
    expect($v->fails())->toBeTrue();
});

test('user store request validates role admin only for mango', function () {
    $admin = User::factory()->admin()->create();
    $mango = User::factory()->mango()->create();

    $payload = [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $this->actingAs($admin)
        ->post(route('users.store'), [...$payload, 'role' => 'admin'])
        ->assertSessionHasErrors('role');

    $this->actingAs($mango)
        ->post(route('users.store'), [...$payload, 'role' => 'admin'])
        ->assertRedirect(route('users.index'));
});

test('user store request validates email unique', function () {
    $admin = User::factory()->admin()->create();
    $existing = User::factory()->create(['email' => 'existing@example.com']);

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Test',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ])
        ->assertSessionHasErrors('email');
});

test('user store request allows optional phone and valid color', function () {
    $admin = User::factory()->admin()->create();
    $request = new UserStoreRequest;
    $request->setUserResolver(fn () => $admin);

    $v = validator([
        'name' => 'Test',
        'email' => 'unique-email-123@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'user',
        'phone' => null,
        'color' => '#FF5733',
    ], $request->rules());
    expect($v->fails())->toBeFalse();

    $v = validator([
        'name' => 'Test',
        'email' => 'unique-email-456@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'user',
        'color' => 'invalid',
    ], $request->rules());
    expect($v->fails())->toBeTrue();
});
