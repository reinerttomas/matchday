<?php

declare(strict_types=1);

use App\Http\Controllers\AdminSelectionController;
use App\Http\Controllers\Auth\GoogleCallbackController;
use App\Http\Controllers\Auth\GoogleRedirectController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\FixtureController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PublicTeamPageController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::get('calendar/{team:slug}.ics', CalendarController::class)->name('calendar');

Route::get('t/{team:slug}', PublicTeamPageController::class)->name('public-team-page');

Route::middleware('guest')->group(function () {
    Route::get('auth/google/redirect', GoogleRedirectController::class)->name('auth.google.redirect');
    Route::get('auth/google/callback', GoogleCallbackController::class)->name('auth.google.callback');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', '/fixtures')->name('dashboard');

    Route::post('admin-selection', AdminSelectionController::class)->name('admin-selection.update');

    Route::get('fixtures', [FixtureController::class, 'index'])->name('fixtures.index');
    Route::get('imports', [ImportController::class, 'index'])->name('imports.index');
});

require __DIR__.'/settings.php';
