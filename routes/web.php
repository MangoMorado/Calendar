<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    Route::resource('calendars', \App\Http\Controllers\CalendarController::class);
    Route::post('appointments', [\App\Http\Controllers\AppointmentController::class, 'store'])->name('appointments.store');
    Route::match(['put', 'patch'], 'appointments/{appointment}', [\App\Http\Controllers\AppointmentController::class, 'update'])->name('appointments.update');

    Route::middleware(['role:admin,mango'])->group(function () {
        Route::resource('users', \App\Http\Controllers\UserController::class);
    });
});

require __DIR__.'/settings.php';
