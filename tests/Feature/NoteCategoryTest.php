<?php

use App\Models\NoteCategory;
use App\Models\User;

test('guests cannot access note categories index', function () {
    $this->get(route('note-categories.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view their note categories', function () {
    $user = User::factory()->create();
    NoteCategory::factory()->count(3)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('note-categories.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('note-categories/index')
            ->has('noteCategories', 3)
        );
});

test('users see only their own categories on index', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    NoteCategory::factory()->create(['user_id' => $user->id, 'name' => 'Mine']);
    NoteCategory::factory()->create(['user_id' => $otherUser->id, 'name' => 'Other']);

    $this->actingAs($user)
        ->get(route('note-categories.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('noteCategories', 1)
        );
});

test('authenticated users can create categories', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('note-categories.store'), ['name' => 'Work'])
        ->assertRedirect(route('note-categories.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('note_categories', [
        'name' => 'Work',
        'user_id' => $user->id,
    ]);
});

test('users cannot edit categories they do not own', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $category = NoteCategory::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->get(route('note-categories.edit', $category))
        ->assertForbidden();
});

test('users can edit their own categories', function () {
    $user = User::factory()->create();
    $category = NoteCategory::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('note-categories.edit', $category))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('note-categories/edit')
            ->has('noteCategory')
        );
});

test('users can update their own categories', function () {
    $user = User::factory()->create();
    $category = NoteCategory::factory()->create(['user_id' => $user->id, 'name' => 'Old']);

    $this->actingAs($user)
        ->put(route('note-categories.update', $category), ['name' => 'New Name'])
        ->assertRedirect(route('note-categories.index'))
        ->assertSessionHas('success');

    $category->refresh();
    expect($category->name)->toBe('New Name');
});

test('users cannot update categories they do not own', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $category = NoteCategory::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->put(route('note-categories.update', $category), ['name' => 'Hacked'])
        ->assertForbidden();
});

test('users can delete their own categories', function () {
    $user = User::factory()->create();
    $category = NoteCategory::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->delete(route('note-categories.destroy', $category))
        ->assertRedirect(route('note-categories.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('note_categories', ['id' => $category->id]);
});

test('users cannot delete categories they do not own', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $category = NoteCategory::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->delete(route('note-categories.destroy', $category))
        ->assertForbidden();
});

test('user can view own note category and cannot view others', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownCat = NoteCategory::factory()->create(['user_id' => $user->id]);
    $otherCat = NoteCategory::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->get(route('note-categories.show', $ownCat))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('note-categories/show')
            ->has('noteCategory')
        );

    $this->actingAs($user)
        ->get(route('note-categories.show', $otherCat))
        ->assertForbidden();
});

test('authenticated user can access note categories create form', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('note-categories.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('note-categories/create'));
});
