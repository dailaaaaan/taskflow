<?php

use App\Http\Controllers\Auth\GitHubController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Teams\InvitationAcceptanceController;
use App\Http\Controllers\Teams\InvitationController;
use App\Http\Controllers\Teams\LeaveTeamController;
use App\Http\Controllers\Teams\MemberController;
use App\Http\Controllers\Teams\TeamController;
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
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Teams. Authorization is declared per route with ->can(), so it runs
    // before validation: unauthorized users get 403, not validation errors.
    Route::post('teams', [TeamController::class, 'store'])->name('teams.store');
    Route::get('teams/{team}', [TeamController::class, 'show'])->name('teams.show')->can('view', 'team');
    Route::get('teams/{team}/settings', [TeamController::class, 'edit'])->name('teams.edit')->can('view', 'team');
    Route::patch('teams/{team}', [TeamController::class, 'update'])->name('teams.update')->can('update', 'team');
    Route::delete('teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy')->can('delete', 'team');
    Route::delete('teams/{team}/membership', LeaveTeamController::class)->name('teams.leave')->can('leave', 'team');

    // Child resources are scoped: a membership or invitation of another team is a 404.
    Route::scopeBindings()->group(function () {
        Route::patch('teams/{team}/members/{membership}', [MemberController::class, 'update'])
            ->name('teams.members.update')->can('manageMembers', 'team');
        Route::delete('teams/{team}/members/{membership}', [MemberController::class, 'destroy'])
            ->name('teams.members.destroy')->can('manageMembers', 'team');

        Route::post('teams/{team}/invitations', [InvitationController::class, 'store'])
            ->name('teams.invitations.store')->can('invite', 'team');
        Route::delete('teams/{team}/invitations/{invitation}', [InvitationController::class, 'destroy'])
            ->name('teams.invitations.destroy')->can('invite', 'team');
    });

    // Invite links: path-only signatures that expire with the invitation.
    Route::middleware('signed:relative')->group(function () {
        Route::get('invitations/{invitation}', [InvitationAcceptanceController::class, 'show'])->name('invitations.show');
        Route::post('invitations/{invitation}/accept', [InvitationAcceptanceController::class, 'store'])->name('invitations.accept');
    });
});

require __DIR__.'/settings.php';
