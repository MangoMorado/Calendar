<?php

use App\Models\Note;

test('public note page returns 200 for valid token', function () {
    $note = Note::factory()->withShareToken()->create([
        'title' => 'Shared Note',
        'content' => '<p>Hello world</p>',
    ]);

    $this->get(route('notes.public.show', ['token' => $note->share_token]))
        ->assertOk()
        ->assertSee('Shared Note')
        ->assertSee('Hello world');
});

test('public note page returns 404 for invalid token', function () {
    $this->get(route('notes.public.show', ['token' => 'invalid-token-123']))
        ->assertNotFound();
});

test('public note page returns 404 for empty token', function () {
    $this->get('/n/')
        ->assertNotFound();
});

test('public note page does not require auth', function () {
    $note = Note::factory()->withShareToken()->create();

    $this->get(route('notes.public.show', ['token' => $note->share_token]))
        ->assertOk();
});
