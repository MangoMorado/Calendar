<?php

use App\Http\Requests\NoteCategoryStoreRequest;
use App\Models\NoteCategory;
use App\Models\User;

test('note category store request validates name required and max 255', function () {
    $user = User::factory()->create();
    $request = new NoteCategoryStoreRequest;
    $request->setUserResolver(fn () => $user);

    $v = validator([], $request->rules());
    expect($v->fails())->toBeTrue();
    expect($v->errors()->has('name'))->toBeTrue();

    $v = validator(['name' => str_repeat('a', 256)], $request->rules());
    expect($v->fails())->toBeTrue();
});

test('note category store request accepts valid name', function () {
    $user = User::factory()->create();
    $request = new NoteCategoryStoreRequest;
    $request->setUserResolver(fn () => $user);

    $v = validator(['name' => 'Work'], $request->rules());
    expect($v->fails())->toBeFalse();
});

test('note category update request validates name sometimes required max 255', function () {
    $user = User::factory()->create();
    $category = NoteCategory::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->put(route('note-categories.update', $category), ['name' => ''])
        ->assertSessionHasErrors('name');

    $this->actingAs($user)
        ->put(route('note-categories.update', $category), ['name' => str_repeat('a', 256)])
        ->assertSessionHasErrors('name');
});

test('note category update request authorizes only owner', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherCategory = NoteCategory::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->put(route('note-categories.update', $otherCategory), ['name' => 'Hacked'])
        ->assertForbidden();
});
