<?php

use App\Models\User;

test('unauthenticated user cannot access health page', function () {
    $this->get(route('health'))
        ->assertRedirect(route('login'));
});

test('mango user can access health page', function () {
    $user = User::factory()->mango()->create();

    $response = $this->actingAs($user)->get(route('health'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('health')
            ->has('metrics')
            ->where('metrics.environment', config('app.env'))
            ->where('metrics.php_version', PHP_VERSION)
            ->has('metrics.counts')
            ->where('metrics.counts.users', 1)
        );
});

test('admin user cannot access health page', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(route('health'))
        ->assertForbidden();
});

test('regular user cannot access health page', function () {
    $user = User::factory()->user()->create();

    $this->actingAs($user)
        ->get(route('health'))
        ->assertForbidden();
});
