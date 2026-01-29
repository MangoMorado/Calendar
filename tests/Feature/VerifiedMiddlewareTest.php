<?php

use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function (): void {
    if (! in_array(Features::emailVerification(), config('fortify.features', []), true)) {
        $this->markTestSkipped('Email verification is temporarily disabled');
    }
});

test('unverified user cannot access dashboard', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));
});
