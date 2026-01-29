<?php

use App\Models\User;

test('user update request authorizes only admin and mango', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->user()->create();
    $target = User::factory()->user()->create();

    $this->actingAs($user)
        ->put(route('users.update', $target), ['name' => 'Updated'])
        ->assertForbidden();

    $this->actingAs($admin)
        ->put(route('users.update', $target), [
            'name' => 'Updated',
            'email' => $target->email,
            'role' => 'user',
        ])
        ->assertRedirect(route('users.index'));
});

test('user update request validates optional password and unique email ignore', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->user()->create();

    $this->actingAs($admin)
        ->put(route('users.update', $target), [
            'name' => 'Updated Name',
            'email' => $target->email,
            'role' => 'user',
        ])
        ->assertRedirect(route('users.index'));

    expect($target->fresh()->name)->toBe('Updated Name');

    $other = User::factory()->user()->create(['email' => 'other@example.com']);

    $this->actingAs($admin)
        ->put(route('users.update', $target), [
            'name' => 'Updated',
            'email' => 'other@example.com',
            'role' => 'user',
        ])
        ->assertSessionHasErrors('email');
});

test('user update request prepareForValidation sets empty password to null', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->user()->create();

    $this->actingAs($admin)
        ->put(route('users.update', $target), [
            'name' => 'Same',
            'email' => $target->email,
            'password' => '',
            'password_confirmation' => '',
            'role' => 'user',
        ])
        ->assertRedirect(route('users.index'));

    expect($target->fresh()->name)->toBe('Same');
});

test('user update request mango can assign role admin and mango', function () {
    $mango = User::factory()->mango()->create();
    $target = User::factory()->user()->create();

    $this->actingAs($mango)
        ->put(route('users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'role' => 'admin',
        ])
        ->assertRedirect(route('users.index'));

    expect($target->fresh()->role->value)->toBe('admin');

    $target2 = User::factory()->user()->create();

    $this->actingAs($mango)
        ->put(route('users.update', $target2), [
            'name' => $target2->name,
            'email' => $target2->email,
            'role' => 'mango',
        ])
        ->assertRedirect(route('users.index'));

    expect($target2->fresh()->role->value)->toBe('mango');
});

test('user update request admin cannot assign role admin or mango', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->user()->create();

    $this->actingAs($admin)
        ->put(route('users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'role' => 'admin',
        ])
        ->assertSessionHasErrors('role');

    $this->actingAs($admin)
        ->put(route('users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'role' => 'mango',
        ])
        ->assertSessionHasErrors('role');
});
