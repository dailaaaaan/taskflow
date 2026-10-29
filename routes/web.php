<?php

use App\Http\Controllers\Auth\GitHubController;
use App\Http\Controllers\Boards\BoardController;
use App\Http\Controllers\Boards\CardController;
use App\Http\Controllers\Boards\ListController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Teams\InvitationAcceptanceController;
use App\Http\Controllers\Teams\InvitationController;
use App\Http\Controllers\Teams\LeaveTeamController;
use App\Http\Controllers\Teams\MemberController;
use App\Http\Controllers\Teams\TeamController;
use App\Models\Board;
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

    // Boards. Every member creates and works on boards; only admins edit or delete them.
    Route::post('teams/{team}/boards', [BoardController::class, 'store'])->name('teams.boards.store')->can('create', [Board::class, 'team']);
    Route::get('boards/{board}', [BoardController::class, 'show'])->name('boards.show')->can('view', 'board');
    Route::patch('boards/{board}', [BoardController::class, 'update'])->name('boards.update')->can('update', 'board');
    Route::delete('boards/{board}', [BoardController::class, 'destroy'])->name('boards.destroy')->can('delete', 'board');

    // Child resources are scoped: a membership, invitation, list or card that
    // belongs to another team or board is a 404.
    Route::scopeBindings()->group(function () {
        Route::patch('teams/{team}/members/{membership}', [MemberController::class, 'update'])
            ->name('teams.members.update')->can('manageMembers', 'team');
        Route::delete('teams/{team}/members/{membership}', [MemberController::class, 'destroy'])
            ->name('teams.members.destroy')->can('manageMembers', 'team');

        Route::post('teams/{team}/invitations', [InvitationController::class, 'store'])
            ->name('teams.invitations.store')->can('invite', 'team');
        Route::delete('teams/{team}/invitations/{invitation}', [InvitationController::class, 'destroy'])
            ->name('teams.invitations.destroy')->can('invite', 'team');

        Route::post('boards/{board}/lists', [ListController::class, 'store'])
            ->name('boards.lists.store')->can('collaborate', 'board');
        Route::patch('boards/{board}/lists/{list}', [ListController::class, 'update'])
            ->name('boards.lists.update')->can('collaborate', 'board');
        Route::delete('boards/{board}/lists/{list}', [ListController::class, 'destroy'])
            ->name('boards.lists.destroy')->can('collaborate', 'board');

        Route::post('boards/{board}/lists/{list}/cards', [CardController::class, 'store'])
            ->name('boards.lists.cards.store')->can('collaborate', 'board');
        Route::patch('boards/{board}/cards/{card}', [CardController::class, 'update'])
            ->name('boards.cards.update')->can('collaborate', 'board');
        Route::delete('boards/{board}/cards/{card}', [CardController::class, 'destroy'])
            ->name('boards.cards.destroy')->can('collaborate', 'board');
    });

    // Invite links: path-only signatures that expire with the invitation.
    Route::middleware('signed:relative')->group(function () {
        Route::get('invitations/{invitation}', [InvitationAcceptanceController::class, 'show'])->name('invitations.show');
        Route::post('invitations/{invitation}/accept', [InvitationAcceptanceController::class, 'store'])->name('invitations.accept');
    });
});

require __DIR__.'/settings.php';
