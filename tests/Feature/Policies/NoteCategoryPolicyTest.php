<?php

use App\Models\NoteCategory;
use App\Models\User;

test('user can view update delete own note category', function () {
    $user = User::factory()->create();
    $category = NoteCategory::factory()->create(['user_id' => $user->id]);

    expect($user->can('view', $category))->toBeTrue();
    expect($user->can('update', $category))->toBeTrue();
    expect($user->can('delete', $category))->toBeTrue();
});

test('user cannot view update or delete others note category', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherCategory = NoteCategory::factory()->create(['user_id' => $otherUser->id]);

    expect($user->can('view', $otherCategory))->toBeFalse();
    expect($user->can('update', $otherCategory))->toBeFalse();
    expect($user->can('delete', $otherCategory))->toBeFalse();
});
