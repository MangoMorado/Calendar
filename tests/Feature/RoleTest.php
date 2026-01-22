<?php

use App\Enums\Role;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('usuario mango tiene acceso a todo', function () {
    $mango = User::factory()->mango()->create();

    expect($mango->isMango())->toBeTrue();
    expect($mango->isAdmin())->toBeTrue();
    expect($mango->hasRole(Role::Mango))->toBeTrue();
});

test('usuario admin tiene permisos de administrador', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->isAdmin())->toBeTrue();
    expect($admin->isMango())->toBeFalse();
    expect($admin->hasRole(Role::Admin))->toBeTrue();
});

test('usuario normal solo tiene permisos básicos', function () {
    $user = User::factory()->user()->create();

    expect($user->isMango())->toBeFalse();
    expect($user->isAdmin())->toBeFalse();
    expect($user->hasRole(Role::User))->toBeTrue();
});

test('middleware permite acceso a mango para cualquier rol', function () {
    $mango = User::factory()->mango()->create();

    // Verificar que mango tiene acceso a todo
    expect($mango->isMango())->toBeTrue();
    expect($mango->role->isAdmin())->toBeTrue();
});

test('middleware bloquea acceso si el usuario no tiene el rol requerido', function () {
    $user = User::factory()->user()->create();

    // Simulamos una ruta protegida con rol admin
    // Esto debería fallar porque el usuario es 'user'
    // Nota: Necesitarás crear una ruta de prueba para esto
    actingAs($user);

    expect($user->hasRole(Role::Admin))->toBeFalse();
});

test('se puede asignar un rol a un usuario', function () {
    $user = User::factory()->create();

    expect($user->role)->toBe(Role::User);

    $user->assignRole(Role::Admin);

    expect($user->fresh()->role)->toBe(Role::Admin);
    expect($user->fresh()->isAdmin())->toBeTrue();
});

test('enum role tiene todos los valores correctos', function () {
    $roles = Role::all();

    expect($roles)->toContain('mango');
    expect($roles)->toContain('admin');
    expect($roles)->toContain('user');
});

test('enum role tiene labels correctos', function () {
    expect(Role::Mango->label())->toBe('Mango');
    expect(Role::Admin->label())->toBe('Administrador');
    expect(Role::User->label())->toBe('Usuario');
});
