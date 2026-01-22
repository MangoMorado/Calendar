<?php

use App\Models\Calendar;
use App\Models\User;

test('any user can view any calendar', function () {
    $user = User::factory()->user()->create();
    $calendar = Calendar::factory()->create();

    expect($user->can('viewAny', Calendar::class))->toBeTrue();
    expect($user->can('view', $calendar))->toBeTrue();
});

test('only admin users can create calendars', function () {
    $regularUser = User::factory()->user()->create();
    $admin = User::factory()->admin()->create();
    $mango = User::factory()->mango()->create();

    expect($regularUser->can('create', Calendar::class))->toBeFalse();
    expect($admin->can('create', Calendar::class))->toBeTrue();
    expect($mango->can('create', Calendar::class))->toBeTrue();
});

test('only admin users can update calendars', function () {
    $regularUser = User::factory()->user()->create();
    $admin = User::factory()->admin()->create();
    $mango = User::factory()->mango()->create();
    $calendar = Calendar::factory()->create();

    expect($regularUser->can('update', $calendar))->toBeFalse();
    expect($admin->can('update', $calendar))->toBeTrue();
    expect($mango->can('update', $calendar))->toBeTrue();
});

test('only admin users can delete calendars', function () {
    $regularUser = User::factory()->user()->create();
    $admin = User::factory()->admin()->create();
    $mango = User::factory()->mango()->create();
    $calendar = Calendar::factory()->create();

    expect($regularUser->can('delete', $calendar))->toBeFalse();
    expect($admin->can('delete', $calendar))->toBeTrue();
    expect($mango->can('delete', $calendar))->toBeTrue();
});

test('only admin users can restore calendars', function () {
    $regularUser = User::factory()->user()->create();
    $admin = User::factory()->admin()->create();
    $calendar = Calendar::factory()->create();

    expect($regularUser->can('restore', $calendar))->toBeFalse();
    expect($admin->can('restore', $calendar))->toBeTrue();
});

test('only admin users can force delete calendars', function () {
    $regularUser = User::factory()->user()->create();
    $admin = User::factory()->admin()->create();
    $calendar = Calendar::factory()->create();

    expect($regularUser->can('forceDelete', $calendar))->toBeFalse();
    expect($admin->can('forceDelete', $calendar))->toBeTrue();
});
