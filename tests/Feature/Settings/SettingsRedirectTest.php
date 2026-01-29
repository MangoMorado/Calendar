<?php

use App\Models\User;

test('GET /settings redirects to /settings/profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings')
        ->assertRedirect(route('profile.edit'));
});

test('GET /settings/appearance returns 200 and Inertia', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('appearance.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('settings/appearance'));
});
