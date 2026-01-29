<?php

use App\Models\Note;
use App\Models\User;

test('any authenticated user can view any notes list', function () {
    $user = User::factory()->create();

    expect($user->can('viewAny', Note::class))->toBeTrue();
});

test('any authenticated user can create notes', function () {
    $user = User::factory()->user()->create();

    expect($user->can('create', Note::class))->toBeTrue();
});

test('user can view only their own notes', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownNote = Note::factory()->create(['user_id' => $user->id]);
    $otherNote = Note::factory()->create(['user_id' => $otherUser->id]);

    expect($user->can('view', $ownNote))->toBeTrue();
    expect($user->can('view', $otherNote))->toBeFalse();
});

test('user can update only their own notes', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownNote = Note::factory()->create(['user_id' => $user->id]);
    $otherNote = Note::factory()->create(['user_id' => $otherUser->id]);

    expect($user->can('update', $ownNote))->toBeTrue();
    expect($user->can('update', $otherNote))->toBeFalse();
});

test('user can delete only their own notes', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownNote = Note::factory()->create(['user_id' => $user->id]);
    $otherNote = Note::factory()->create(['user_id' => $otherUser->id]);

    expect($user->can('delete', $ownNote))->toBeTrue();
    expect($user->can('delete', $otherNote))->toBeFalse();
});
