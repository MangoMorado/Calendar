<?php

use App\Enums\Role;
use App\Models\User;

test('guests cannot access users index', function () {
    $this->get(route('users.index'))
        ->assertRedirect(route('login'));
});

test('user role cannot access users index and admin can', function () {
    $user = User::factory()->user()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(route('users.index'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('users/index')
            ->has('users')
            ->has('canCreate')
        );
});

test('admin can store user and mango can store user with role admin', function () {
    $admin = User::factory()->admin()->create();
    $mango = User::factory()->mango()->create();

    $adminPayload = [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'user',
    ];

    $this->actingAs($admin)
        ->post(route('users.store'), $adminPayload)
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
        'role' => Role::User,
    ]);

    $mangoPayload = [
        'name' => 'New Admin',
        'email' => 'newadmin@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'admin',
    ];

    $this->actingAs($mango)
        ->post(route('users.store'), $mangoPayload)
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('users', [
        'email' => 'newadmin@example.com',
        'role' => Role::Admin,
    ]);
});

test('admin can edit and update other user but cannot edit self', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->user()->create();

    $this->actingAs($admin)
        ->get(route('users.edit', $other))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('users/edit')
            ->has('user')
            ->has('availableRoles')
        );

    $this->actingAs($admin)
        ->put(route('users.update', $other), [
            'name' => 'Updated Name',
            'email' => $other->email,
            'role' => 'user',
        ])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('success');

    expect($other->fresh()->name)->toBe('Updated Name');

    $this->actingAs($admin)
        ->get(route('users.edit', $admin))
        ->assertForbidden();

    $this->actingAs($admin)
        ->put(route('users.update', $admin), [
            'name' => 'Hacked',
            'email' => $admin->email,
            'role' => 'user',
        ])
        ->assertForbidden();
});

test('mango can delete other user but not self and admin cannot delete', function () {
    $mango = User::factory()->mango()->create();
    $other = User::factory()->user()->create();
    $admin = User::factory()->admin()->create();
    $target = User::factory()->user()->create();

    $this->actingAs($mango)
        ->delete(route('users.destroy', $other))
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('users', ['id' => $other->id]);

    $this->actingAs($mango)
        ->delete(route('users.destroy', $mango))
        ->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $mango->id]);

    $this->actingAs($admin)
        ->delete(route('users.destroy', $target))
        ->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $target->id]);
});
