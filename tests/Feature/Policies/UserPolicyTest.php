<?php

use App\Models\User;

test('admin and mango can viewAny view create users', function () {
    $admin = User::factory()->admin()->create();
    $mango = User::factory()->mango()->create();
    $target = User::factory()->user()->create();

    expect($admin->can('viewAny', User::class))->toBeTrue();
    expect($admin->can('view', $target))->toBeTrue();
    expect($admin->can('create', User::class))->toBeTrue();

    expect($mango->can('viewAny', User::class))->toBeTrue();
    expect($mango->can('view', $target))->toBeTrue();
    expect($mango->can('create', User::class))->toBeTrue();
});

test('user role cannot viewAny view or create users', function () {
    $user = User::factory()->user()->create();
    $target = User::factory()->user()->create();

    expect($user->can('viewAny', User::class))->toBeFalse();
    expect($user->can('view', $target))->toBeFalse();
    expect($user->can('create', User::class))->toBeFalse();
});

test('mango can update any user including self', function () {
    $mango = User::factory()->mango()->create();
    $other = User::factory()->user()->create();

    expect($mango->can('update', $other))->toBeTrue();
    expect($mango->can('update', $mango))->toBeTrue();
});

test('admin can update other users but not self', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->user()->create();

    expect($admin->can('update', $other))->toBeTrue();
    expect($admin->can('update', $admin))->toBeFalse();
});

test('only mango can delete users and mango cannot delete self', function () {
    $mango = User::factory()->mango()->create();
    $admin = User::factory()->admin()->create();
    $target = User::factory()->user()->create();

    expect($mango->can('delete', $target))->toBeTrue();
    expect($mango->can('delete', $mango))->toBeFalse();

    expect($admin->can('delete', $target))->toBeFalse();
    expect($admin->can('delete', $admin))->toBeFalse();
});
