<?php

use App\Http\Controllers\Auth\GitHubController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['guest', 'throttle:10,1'])
    ->prefix('auth/github')
    ->name('auth.github.')
    ->controller(GitHubController::class)
    ->group(function () {
        Route::get('redirect', 'redirect')->name('redirect');
        Route::get('callback', 'callback')->name('callback');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
