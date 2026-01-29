<?php

use App\Models\Note;
use App\Models\User;

test('guests cannot access notes index', function () {
    $this->get(route('notes.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view notes index', function () {
    $user = User::factory()->create();
    Note::factory()->count(3)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('notes.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('notes/index')
            ->has('notes')
            ->has('noteCategories')
        );
});

test('notes index shows only own notes and visibility todos', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Note::factory()->create(['user_id' => $user->id, 'visibility' => 'solo_yo']);
    Note::factory()->create(['user_id' => $user->id, 'visibility' => 'todos']);
    Note::factory()->create(['user_id' => $otherUser->id, 'visibility' => 'todos']);
    Note::factory()->create(['user_id' => $otherUser->id, 'visibility' => 'solo_yo']);

    $this->actingAs($user)
        ->get(route('notes.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('notes.data', 3)
        );
});

test('guests cannot create notes', function () {
    $this->get(route('notes.create'))
        ->assertRedirect(route('login'));
});

test('authenticated users can create notes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('notes.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('notes/create')
            ->has('noteCategories')
        );
});

function validNoteStoreData(array $overrides = []): array
{
    return array_merge([
        'title' => 'Test Note',
        'content' => '<p>Test content</p>',
        'visibility' => 'todos',
    ], $overrides);
}

test('authenticated users can store notes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('notes.store'), validNoteStoreData())
        ->assertRedirect(route('notes.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('notes', [
        'title' => 'Test Note',
        'content' => '<p>Test content</p>',
        'visibility' => 'todos',
        'user_id' => $user->id,
    ]);
});

test('note store assigns current user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('notes.store'), validNoteStoreData());

    $note = Note::where('title', 'Test Note')->first();
    expect($note->user_id)->toBe($user->id);
});

test('users cannot edit notes they do not own', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->get(route('notes.edit', $note))
        ->assertForbidden();
});

test('users can edit their own notes', function () {
    $user = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('notes.edit', $note))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('notes/edit')
            ->has('note')
            ->has('noteCategories')
        );
});

test('users cannot update notes they do not own', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->put(route('notes.update', $note), validNoteStoreData(['title' => 'Hacked']))
        ->assertForbidden();
});

test('users can update their own notes', function () {
    $user = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->put(route('notes.update', $note), [
            'title' => 'Updated Title',
            'content' => '<p>Updated content</p>',
            'visibility' => 'solo_yo',
        ])
        ->assertRedirect(route('notes.index'))
        ->assertSessionHas('success');

    $note->refresh();
    expect($note->title)->toBe('Updated Title');
    expect($note->visibility)->toBe('solo_yo');
});

test('users cannot delete notes they do not own', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->delete(route('notes.destroy', $note))
        ->assertForbidden();
});

test('users can delete their own notes', function () {
    $user = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->delete(route('notes.destroy', $note))
        ->assertRedirect(route('notes.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('notes', ['id' => $note->id]);
});

test('share generates token and returns public url', function () {
    $user = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $user->id, 'share_token' => null]);

    $response = $this->actingAs($user)
        ->postJson(route('notes.share', $note))
        ->assertOk()
        ->assertJsonStructure(['share_token', 'public_url']);

    $note->refresh();
    expect($note->share_token)->not->toBeNull();
    expect($response->json('public_url'))->toContain($note->share_token);
});

test('share returns existing token if already set', function () {
    $user = User::factory()->create();
    $note = Note::factory()->withShareToken()->create(['user_id' => $user->id]);
    $originalToken = $note->share_token;

    $response = $this->actingAs($user)
        ->postJson(route('notes.share', $note))
        ->assertOk();

    $note->refresh();
    expect($note->share_token)->toBe($originalToken);
    expect($response->json('share_token'))->toBe($originalToken);
});

test('users cannot share notes they do not own', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $note = Note::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->postJson(route('notes.share', $note))
        ->assertForbidden();
});

test('user can view own note and cannot view others', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownNote = Note::factory()->create(['user_id' => $user->id]);
    $otherNote = Note::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->get(route('notes.show', $ownNote))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('notes/show')
            ->has('note')
        );

    $this->actingAs($user)
        ->get(route('notes.show', $otherNote))
        ->assertForbidden();
});

test('note index filters by search and category_id', function () {
    $user = User::factory()->create();
    $cat = \App\Models\NoteCategory::factory()->create(['user_id' => $user->id]);
    Note::factory()->create(['user_id' => $user->id, 'title' => 'foo bar', 'content' => 'baz']);
    Note::factory()->create(['user_id' => $user->id, 'title' => 'other', 'content' => 'qux']);
    Note::factory()->create(['user_id' => $user->id, 'title' => 'has foo', 'content' => 'x', 'note_category_id' => $cat->id]);

    $this->actingAs($user)
        ->get(route('notes.index', ['search' => 'foo']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('notes.data', 2)
            ->where('filters.search', 'foo')
        );

    $this->actingAs($user)
        ->get(route('notes.index', ['category_id' => $cat->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('notes.data', 1)
            ->where('filters.category_id', (string) $cat->id)
        );
});

test('note store with note_category_id persists category', function () {
    $user = User::factory()->create();
    $cat = \App\Models\NoteCategory::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('notes.store'), validNoteStoreData(['note_category_id' => $cat->id]))
        ->assertRedirect(route('notes.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('notes', [
        'title' => 'Test Note',
        'note_category_id' => $cat->id,
        'user_id' => $user->id,
    ]);
});
