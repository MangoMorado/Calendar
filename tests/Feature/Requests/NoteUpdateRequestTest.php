<?php

use App\Http\Requests\NoteUpdateRequest;
use App\Models\NoteCategory;
use App\Models\User;

test('note update request authorizes authenticated users', function () {
    $user = User::factory()->create();

    $request = NoteUpdateRequest::create('/', 'PUT', []);
    $request->setUserResolver(fn () => $user);

    expect($request->authorize())->toBeTrue();
});

test('note update request denies guests', function () {
    $request = NoteUpdateRequest::create('/', 'PUT', []);
    $request->setUserResolver(fn () => null);

    expect($request->authorize())->toBeFalse();
});

test('note update request validates visibility when present', function () {
    $user = User::factory()->create();
    $request = new NoteUpdateRequest;
    $request->setUserResolver(fn () => $user);

    $validator = validator([
        'visibility' => 'invalid',
    ], $request->rules());

    expect($validator->fails())->toBeTrue();

    $validator = validator([
        'visibility' => 'todos',
    ], $request->rules());
    expect($validator->fails())->toBeFalse();
});

test('note update request allows partial updates', function () {
    $user = User::factory()->create();
    $request = new NoteUpdateRequest;
    $request->setUserResolver(fn () => $user);

    $validator = validator(['title' => 'Updated'], $request->rules());
    expect($validator->fails())->toBeFalse();

    $validator = validator(['content' => '<p>New</p>'], $request->rules());
    expect($validator->fails())->toBeFalse();
});

test('note update request validates note_category_id belongs to user when present', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherCategory = NoteCategory::factory()->create(['user_id' => $otherUser->id]);

    $request = new NoteUpdateRequest;
    $request->setUserResolver(fn () => $user);

    $validator = validator([
        'note_category_id' => $otherCategory->id,
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
});
