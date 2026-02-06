<?php

use App\Http\Controllers\NoteCategoryController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\NoteShareController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('n/{token}', [NoteShareController::class, 'show'])->name('notes.public.show');

Route::middleware(['auth'])->group(function () {
    if (! Features::enabled(Features::emailVerification())) {
        Route::post('/email/verification-notification', [\App\Http\Controllers\Auth\EmailVerificationStubController::class, 'store'])
            ->middleware(['throttle:6,1'])
            ->name('verification.send');
    }

    Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    Route::resource('calendars', \App\Http\Controllers\CalendarController::class);
    Route::post('appointments', [\App\Http\Controllers\AppointmentController::class, 'store'])->name('appointments.store');
    Route::match(['put', 'patch'], 'appointments/{appointment}', [\App\Http\Controllers\AppointmentController::class, 'update'])->name('appointments.update');

    Route::post('notes/{note}/share', [NoteController::class, 'share'])->name('notes.share');
    Route::resource('notes', NoteController::class);
    Route::resource('note-categories', NoteCategoryController::class);

    Route::middleware(['role:admin,mango'])->group(function () {
        Route::resource('users', \App\Http\Controllers\UserController::class);
        Route::get('analytics', [\App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics');
    });

    Route::middleware(['role:mango'])->group(function () {
        Route::get('health', [\App\Http\Controllers\HealthController::class, 'index'])->name('health');
    });
});

require __DIR__.'/settings.php';
