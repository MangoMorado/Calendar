<?php

use App\Enums\Role;
use App\Http\Middleware\EnsureUserHasRole;
use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Limpiar rutas antes de cada test
    Route::getRoutes()->refreshNameLookups();
    Route::getRoutes()->refreshActionLookups();

    // Crear rutas de prueba para el middleware
    if (! Route::has('test.admin')) {
        Route::middleware(['auth', 'role:admin'])->get('/test-admin-route', function () {
            return response()->json(['message' => 'Access granted']);
        })->name('test.admin');
    }

    if (! Route::has('test.mango')) {
        Route::middleware(['auth', 'role:mango'])->get('/test-mango-route', function () {
            return response()->json(['message' => 'Access granted']);
        })->name('test.mango');
    }

    if (! Route::has('test.user')) {
        Route::middleware(['auth', 'role:user'])->get('/test-user-route', function () {
            return response()->json(['message' => 'Access granted']);
        })->name('test.user');
    }
});

test('middleware denies access to unauthenticated users', function () {
    // El middleware auth redirige a login (302) antes de que EnsureUserHasRole se ejecute
    $this->get('/test-admin-route')
        ->assertRedirect(route('login'));
});

test('middleware allows access to users with correct role', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/test-admin-route')
        ->assertOk()
        ->assertJson(['message' => 'Access granted']);
});

test('middleware denies access to users without required role', function () {
    $user = User::factory()->user()->create();

    $this->actingAs($user)
        ->get('/test-admin-route')
        ->assertForbidden();
});

test('middleware allows mango to access any route', function () {
    $mango = User::factory()->mango()->create();

    $this->actingAs($mango)
        ->get('/test-admin-route')
        ->assertOk();

    $this->actingAs($mango)
        ->get('/test-user-route')
        ->assertOk();
});

test('middleware handles multiple roles', function () {
    Route::middleware(['auth', 'role:admin,mango'])->get('/test-multi-route', function () {
        return response()->json(['message' => 'Access granted']);
    });

    $admin = User::factory()->admin()->create();
    $mango = User::factory()->mango()->create();
    $user = User::factory()->user()->create();

    $this->actingAs($admin)
        ->get('/test-multi-route')
        ->assertOk();

    $this->actingAs($mango)
        ->get('/test-multi-route')
        ->assertOk();

    $this->actingAs($user)
        ->get('/test-multi-route')
        ->assertForbidden();
});

// Nota: No se puede probar usuario sin rol porque la columna es NOT NULL
// test('middleware returns 403 for users without role', function () {
//     $user = User::factory()->create();
//     // Simular usuario sin rol (aunque esto no debería pasar normalmente)
//     $user->role = null;
//     $user->save();
//
//     $this->actingAs($user)
//         ->get('/test-admin-route')
//         ->assertForbidden();
// });
