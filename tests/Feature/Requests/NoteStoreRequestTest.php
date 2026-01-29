<?php

use App\Http\Requests\NoteStoreRequest;
use App\Models\NoteCategory;
use App\Models\User;

test('note store request authorizes authenticated users', function () {
    $user = User::factory()->create();

    $request = NoteStoreRequest::create('/', 'POST', []);
    $request->setUserResolver(fn () => $user);

    expect($request->authorize())->toBeTrue();
});

test('note store request denies guests', function () {
    $request = NoteStoreRequest::create('/', 'POST', []);
    $request->setUserResolver(fn () => null);

    expect($request->authorize())->toBeFalse();
});

test('note store request validates title is required', function () {
    $user = User::factory()->create();
    $request = new NoteStoreRequest;
    $request->setUserResolver(fn () => $user);

    $validator = validator([], $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('title'))->toBeTrue();
});

test('note store request validates title max length', function () {
    $user = User::factory()->create();
    $request = new NoteStoreRequest;
    $request->setUserResolver(fn () => $user);

    $validator = validator([
        'title' => str_repeat('a', 256),
        'visibility' => 'todos',
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
});

test('note store request validates visibility is required', function () {
    $user = User::factory()->create();
    $request = new NoteStoreRequest;
    $request->setUserResolver(fn () => $user);

    $validator = validator(['title' => 'Test'], $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('visibility'))->toBeTrue();
});

test('note store request validates visibility in todos solo_yo', function () {
    $user = User::factory()->create();
    $request = new NoteStoreRequest;
    $request->setUserResolver(fn () => $user);

    $validator = validator([
        'title' => 'Test',
        'visibility' => 'invalid',
    ], $request->rules());

    expect($validator->fails())->toBeTrue();

    $validator = validator([
        'title' => 'Test',
        'visibility' => 'todos',
    ], $request->rules());
    expect($validator->fails())->toBeFalse();

    $validator = validator([
        'title' => 'Test',
        'visibility' => 'solo_yo',
    ], $request->rules());
    expect($validator->fails())->toBeFalse();
});

test('note store request validates note_category_id belongs to user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownCategory = NoteCategory::factory()->create(['user_id' => $user->id]);
    $otherCategory = NoteCategory::factory()->create(['user_id' => $otherUser->id]);

    $request = new NoteStoreRequest;
    $request->setUserResolver(fn () => $user);

    $validator = validator([
        'title' => 'Test',
        'visibility' => 'todos',
        'note_category_id' => $otherCategory->id,
    ], $request->rules());

    expect($validator->fails())->toBeTrue();

    $validator = validator([
        'title' => 'Test',
        'visibility' => 'todos',
        'note_category_id' => $ownCategory->id,
    ], $request->rules());
    expect($validator->fails())->toBeFalse();
});

test('note store request allows null content and null note_category_id', function () {
    $user = User::factory()->create();
    $request = new NoteStoreRequest;
    $request->setUserResolver(fn () => $user);

    $validator = validator([
        'title' => 'Test',
        'visibility' => 'todos',
        'content' => null,
        'note_category_id' => null,
    ], $request->rules());

    expect($validator->fails())->toBeFalse();
});
