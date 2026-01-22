<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    Route::resource('calendars', \App\Http\Controllers\CalendarController::class);
    Route::post('appointments', [\App\Http\Controllers\AppointmentController::class, 'store'])->name('appointments.store');
    Route::match(['put', 'patch'], 'appointments/{appointment}', [\App\Http\Controllers\AppointmentController::class, 'update'])->name('appointments.update');
});

require __DIR__.'/settings.php';
