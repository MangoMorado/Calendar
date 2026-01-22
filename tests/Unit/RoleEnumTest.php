<?php

use App\Enums\Role;

test('role enum has all expected values', function () {
    $roles = Role::all();

    expect($roles)->toContain('mango');
    expect($roles)->toContain('admin');
    expect($roles)->toContain('user');
    expect($roles)->toHaveCount(3);
});

test('role enum has correct labels', function () {
    expect(Role::Mango->label())->toBe('Mango');
    expect(Role::Admin->label())->toBe('Administrador');
    expect(Role::User->label())->toBe('Usuario');
});

test('role enum isMango method works correctly', function () {
    expect(Role::Mango->isMango())->toBeTrue();
    expect(Role::Admin->isMango())->toBeFalse();
    expect(Role::User->isMango())->toBeFalse();
});

test('role enum isAdmin method works correctly', function () {
    expect(Role::Mango->isAdmin())->toBeTrue();
    expect(Role::Admin->isAdmin())->toBeTrue();
    expect(Role::User->isAdmin())->toBeFalse();
});

test('role enum isUser method works correctly', function () {
    expect(Role::Mango->isUser())->toBeTrue();
    expect(Role::Admin->isUser())->toBeTrue();
    expect(Role::User->isUser())->toBeTrue();
});

test('role enum can be created from string', function () {
    expect(Role::from('mango'))->toBe(Role::Mango);
    expect(Role::from('admin'))->toBe(Role::Admin);
    expect(Role::from('user'))->toBe(Role::User);
});
