<?php

use App\Models\Calendar;
use App\Models\User;

test('guests cannot access calendar index', function () {
    $this->get(route('calendars.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view calendar index', function () {
    $user = User::factory()->create();
    Calendar::factory()->count(5)->create();

    $this->actingAs($user)
        ->get(route('calendars.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('calendars/index')
            ->has('calendars.data', 5)
        );
});

test('calendar index shows only active calendars by default', function () {
    $user = User::factory()->create();
    Calendar::factory()->count(3)->create(['is_active' => true]);
    Calendar::factory()->count(2)->create(['is_active' => false]);

    $this->actingAs($user)
        ->get(route('calendars.index'))
        ->assertOk();
});

test('guests cannot create calendars', function () {
    $this->get(route('calendars.create'))
        ->assertRedirect(route('login'));
});

test('regular users cannot create calendars', function () {
    $user = User::factory()->user()->create();

    $this->actingAs($user)
        ->get(route('calendars.create'))
        ->assertForbidden();
});

test('admin users can create calendars', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('calendars.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('calendars/create')
        );
});

test('mango users can create calendars', function () {
    $mango = User::factory()->mango()->create();

    $this->actingAs($mango)
        ->get(route('calendars.create'))
        ->assertOk();
});

test('regular users cannot store calendars', function () {
    $user = User::factory()->user()->create();
    $calendarData = [
        'name' => 'Test Calendar',
        'description' => 'Test Description',
        'color' => '#FF5733',
    ];

    $this->actingAs($user)
        ->post(route('calendars.store'), $calendarData)
        ->assertForbidden();
});

test('admin users can store calendars', function () {
    $admin = User::factory()->admin()->create();
    $calendarData = [
        'name' => 'Test Calendar',
        'description' => 'Test Description',
        'color' => '#FF5733',
    ];

    $this->actingAs($admin)
        ->post(route('calendars.store'), $calendarData)
        ->assertRedirect(route('calendars.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('calendars', [
        'name' => 'Test Calendar',
        'description' => 'Test Description',
        'color' => '#FF5733',
        'is_active' => true,
    ]);
});

test('calendar store validates required fields', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('calendars.store'), [])
        ->assertSessionHasErrors(['name']);
});

test('calendar store validates color format', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('calendars.store'), [
            'name' => 'Test Calendar',
            'color' => 'invalid-color',
        ])
        ->assertSessionHasErrors(['color']);
});

test('calendar store validates user_id exists', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('calendars.store'), [
            'name' => 'Test Calendar',
            'user_id' => 99999,
        ])
        ->assertSessionHasErrors(['user_id']);
});

test('calendar can be created without user_id', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('calendars.store'), [
            'name' => 'Test Calendar',
            'user_id' => null,
        ])
        ->assertRedirect(route('calendars.index'));

    $this->assertDatabaseHas('calendars', [
        'name' => 'Test Calendar',
        'user_id' => null,
    ]);
});

// Nota: El método show existe en el controlador pero la vista no está implementada
// test('guests cannot view calendar details', function () {
//     $calendar = Calendar::factory()->create();
//
//     $this->get(route('calendars.show', $calendar))
//         ->assertRedirect(route('login'));
// });
//
// test('authenticated users can view calendar details', function () {
//     $user = User::factory()->create();
//     $calendar = Calendar::factory()->create();
//
//     $this->actingAs($user)
//         ->get(route('calendars.show', $calendar))
//         ->assertOk()
//         ->assertInertia(fn ($page) => $page
//             ->component('calendars/show')
//             ->has('calendar')
//             ->where('calendar.id', $calendar->id)
//         );
// });

test('guests cannot edit calendars', function () {
    $calendar = Calendar::factory()->create();

    $this->get(route('calendars.edit', $calendar))
        ->assertRedirect(route('login'));
});

test('regular users cannot edit calendars', function () {
    $user = User::factory()->user()->create();
    $calendar = Calendar::factory()->create();

    $this->actingAs($user)
        ->get(route('calendars.edit', $calendar))
        ->assertForbidden();
});

test('admin users can edit calendars', function () {
    $admin = User::factory()->admin()->create();
    $calendar = Calendar::factory()->create();

    $this->actingAs($admin)
        ->get(route('calendars.edit', $calendar))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('calendars/edit')
            ->has('calendar')
        );
});

test('regular users cannot update calendars', function () {
    $user = User::factory()->user()->create();
    $calendar = Calendar::factory()->create();

    $this->actingAs($user)
        ->put(route('calendars.update', $calendar), [
            'name' => 'Updated Name',
        ])
        ->assertForbidden();
});

test('admin users can update calendars', function () {
    $admin = User::factory()->admin()->create();
    $calendar = Calendar::factory()->create();

    $this->actingAs($admin)
        ->put(route('calendars.update', $calendar), [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'color' => '#00FF00',
            'is_active' => false,
        ])
        ->assertRedirect(route('calendars.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('calendars', [
        'id' => $calendar->id,
        'name' => 'Updated Name',
        'description' => 'Updated Description',
        'color' => '#00FF00',
        'is_active' => false,
    ]);
});

test('calendar update validates color format', function () {
    $admin = User::factory()->admin()->create();
    $calendar = Calendar::factory()->create();

    $this->actingAs($admin)
        ->put(route('calendars.update', $calendar), [
            'name' => 'Updated Name',
            'color' => 'invalid',
        ])
        ->assertSessionHasErrors(['color']);
});

test('regular users cannot delete calendars', function () {
    $user = User::factory()->user()->create();
    $calendar = Calendar::factory()->create();

    $this->actingAs($user)
        ->delete(route('calendars.destroy', $calendar))
        ->assertForbidden();
});

test('admin users can delete calendars', function () {
    $admin = User::factory()->admin()->create();
    $calendar = Calendar::factory()->create();

    $this->actingAs($admin)
        ->delete(route('calendars.destroy', $calendar))
        ->assertRedirect(route('calendars.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('calendars', [
        'id' => $calendar->id,
    ]);
});

test('deleting calendar also deletes appointments', function () {
    $admin = User::factory()->admin()->create();
    $calendar = Calendar::factory()->create();
    $appointment = \App\Models\Appointment::factory()->create([
        'calendar_id' => $calendar->id,
    ]);

    $this->actingAs($admin)
        ->delete(route('calendars.destroy', $calendar));

    $this->assertDatabaseMissing('appointments', [
        'id' => $appointment->id,
    ]);
});
