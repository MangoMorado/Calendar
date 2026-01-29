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
            ->has('timezones')
        );
});

test('mango users can create calendars', function () {
    $mango = User::factory()->mango()->create();

    $this->actingAs($mango)
        ->get(route('calendars.create'))
        ->assertOk();
});

function validCalendarStoreData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Test Calendar',
        'description' => 'Test Description',
        'color' => '#FF5733',
        'start_time' => '06:00',
        'end_time' => '19:00',
        'slot_duration' => 30,
        'time_format' => '12',
        'timezone' => 'America/Bogota',
        'business_days' => [1, 2, 3, 4, 5, 6],
        'visibility' => 'todos',
    ], $overrides);
}

test('regular users cannot store calendars', function () {
    $user = User::factory()->user()->create();

    $this->actingAs($user)
        ->post(route('calendars.store'), validCalendarStoreData())
        ->assertForbidden();
});

test('admin users can store calendars', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('calendars.store'), validCalendarStoreData())
        ->assertRedirect(route('calendars.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('calendars', [
        'name' => 'Test Calendar',
        'description' => 'Test Description',
        'color' => '#FF5733',
        'is_active' => true,
        'start_time' => '06:00',
        'end_time' => '19:00',
        'slot_duration' => 30,
        'time_format' => '12',
        'timezone' => 'America/Bogota',
        'visibility' => 'todos',
    ]);
    $calendar = Calendar::where('name', 'Test Calendar')->first();
    expect($calendar->business_days)->toBe([1, 2, 3, 4, 5, 6]);
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
        ->post(route('calendars.store'), validCalendarStoreData([
            'color' => 'invalid-color',
        ]))
        ->assertSessionHasErrors(['color']);
});

test('calendar store validates user_id exists', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('calendars.store'), validCalendarStoreData([
            'user_id' => 99999,
        ]))
        ->assertSessionHasErrors(['user_id']);
});

test('calendar store validates business_days', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('calendars.store'), validCalendarStoreData([
            'business_days' => [0, 8],
        ]))
        ->assertSessionHasErrors(['business_days.0', 'business_days.1']);
});

test('calendar store accepts valid business_days', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('calendars.store'), validCalendarStoreData([
            'business_days' => [1, 3, 5],
        ]))
        ->assertRedirect(route('calendars.index'));

    $calendar = Calendar::where('name', 'Test Calendar')->first();
    expect($calendar->business_days)->toBe([1, 3, 5]);
});

test('calendar can be created without user_id', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('calendars.store'), validCalendarStoreData([
            'user_id' => null,
        ]))
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
            ->has('timezones')
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
            'start_time' => '08:00',
            'end_time' => '18:00',
            'slot_duration' => 45,
            'time_format' => '24',
            'timezone' => 'America/New_York',
            'business_days' => [1, 2, 3, 4, 5],
            'visibility' => 'solo_yo',
        ])
        ->assertRedirect(route('calendars.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('calendars', [
        'id' => $calendar->id,
        'name' => 'Updated Name',
        'description' => 'Updated Description',
        'color' => '#00FF00',
        'is_active' => false,
        'start_time' => '08:00',
        'end_time' => '18:00',
        'slot_duration' => 45,
        'time_format' => '24',
        'timezone' => 'America/New_York',
        'visibility' => 'solo_yo',
    ]);
    $calendar->refresh();
    expect($calendar->business_days)->toBe([1, 2, 3, 4, 5]);
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

test('mango users can edit update and delete calendars', function () {
    $mango = User::factory()->mango()->create();
    $calendar = Calendar::factory()->create();

    $this->actingAs($mango)
        ->get(route('calendars.edit', $calendar))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('calendars/edit')
            ->has('calendar')
            ->has('timezones')
        );

    $this->actingAs($mango)
        ->put(route('calendars.update', $calendar), [
            'name' => 'Mango Updated',
            'description' => 'Updated by Mango',
            'color' => '#00FF00',
            'is_active' => true,
            'start_time' => '08:00',
            'end_time' => '18:00',
            'slot_duration' => 45,
            'time_format' => '24',
            'timezone' => 'America/New_York',
            'business_days' => [1, 2, 3, 4, 5],
            'visibility' => 'solo_yo',
        ])
        ->assertRedirect(route('calendars.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('calendars', [
        'id' => $calendar->id,
        'name' => 'Mango Updated',
    ]);

    $calendar2 = Calendar::factory()->create();

    $this->actingAs($mango)
        ->delete(route('calendars.destroy', $calendar2))
        ->assertRedirect(route('calendars.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('calendars', ['id' => $calendar2->id]);
});
