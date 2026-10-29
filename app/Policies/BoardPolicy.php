<?php

namespace App\Policies;

use App\Enums\TeamRole;
use App\Models\Board;
use App\Models\Team;
use App\Models\User;

/**
 * Board permissions, derived from the user's role in the board's team.
 * Lists and cards belong to the board, so their routes are nested under it
 * and authorized here too. The full role × ability matrix is covered by
 * tests/Feature/Boards/BoardPolicyTest.php.
 */
final class BoardPolicy
{
    public function view(User $user, Board $board): bool
    {
        return $board->team->roleOf($user) !== null;
    }

    /**
     * Called as ->can('create', [Board::class, 'team']): the team is the context.
     */
    public function create(User $user, Team $team): bool
    {
        return $team->roleOf($user) !== null;
    }

    /**
     * Work on the board's content: lists and cards. Every member may today,
     * but it is a separate ability so a read-only role only changes this method.
     */
    public function collaborate(User $user, Board $board): bool
    {
        return $board->team->roleOf($user) !== null;
    }

    public function update(User $user, Board $board): bool
    {
        return $board->team->roleOf($user) === TeamRole::Admin;
    }

    public function delete(User $user, Board $board): bool
    {
        return $board->team->roleOf($user) === TeamRole::Admin;
    }
}
