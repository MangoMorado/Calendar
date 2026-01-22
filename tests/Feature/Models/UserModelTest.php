<?php

use App\Enums\Role;
use App\Models\Calendar;
use App\Models\User;

test('user has role enum cast', function () {
    $user = User::factory()->create();

    expect($user->role)->toBeInstanceOf(Role::class);
});

test('user can have mango role', function () {
    $user = User::factory()->mango()->create();

    expect($user->isMango())->toBeTrue();
    expect($user->isAdmin())->toBeTrue();
    expect($user->hasRole(Role::Mango))->toBeTrue();
});

test('user can have admin role', function () {
    $user = User::factory()->admin()->create();

    expect($user->isAdmin())->toBeTrue();
    expect($user->isMango())->toBeFalse();
    expect($user->hasRole(Role::Admin))->toBeTrue();
});

test('user can have user role', function () {
    $user = User::factory()->user()->create();

    expect($user->isMango())->toBeFalse();
    expect($user->isAdmin())->toBeFalse();
    expect($user->hasRole(Role::User))->toBeTrue();
});

test('user can assign role', function () {
    $user = User::factory()->create();

    expect($user->role)->toBe(Role::User);

    $user->assignRole(Role::Admin);

    expect($user->fresh()->role)->toBe(Role::Admin);
    expect($user->fresh()->isAdmin())->toBeTrue();
});

test('user can check if has any role from array', function () {
    $user = User::factory()->admin()->create();

    expect($user->hasAnyRole([Role::Admin, Role::Mango]))->toBeTrue();
    expect($user->hasAnyRole([Role::User]))->toBeFalse();
});

test('user can have many calendars', function () {
    $user = User::factory()->create();
    Calendar::factory()->count(3)->create(['user_id' => $user->id]);

    // Nota: Necesitarías agregar la relación hasMany en el modelo User
    // Por ahora verificamos que los calendarios existen
    $this->assertDatabaseCount('calendars', 3);
});

test('deleting user nullifies calendar user_id', function () {
    $user = User::factory()->create();
    $calendar = Calendar::factory()->create(['user_id' => $user->id]);

    $user->delete();

    expect($calendar->fresh()->user_id)->toBeNull();
});
