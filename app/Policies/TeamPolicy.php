<?php

namespace App\Policies;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

/**
 * Team permissions. Members collaborate; only admins manage the team itself.
 * The full role × ability matrix is covered by tests/Feature/Teams/TeamPolicyTest.php.
 */
final class TeamPolicy
{
    public function view(User $user, Team $team): bool
    {
        return $team->roleOf($user) !== null;
    }

    public function update(User $user, Team $team): bool
    {
        return $this->isAdmin($user, $team);
    }

    public function delete(User $user, Team $team): bool
    {
        return $this->isAdmin($user, $team);
    }

    public function invite(User $user, Team $team): bool
    {
        return $this->isAdmin($user, $team);
    }

    public function manageMembers(User $user, Team $team): bool
    {
        return $this->isAdmin($user, $team);
    }

    /**
     * Any member may try to leave; the "at least one admin" invariant is
     * enforced by the action, not here, so the user gets a precise error.
     */
    public function leave(User $user, Team $team): bool
    {
        return $team->roleOf($user) !== null;
    }

    private function isAdmin(User $user, Team $team): bool
    {
        return $team->roleOf($user) === TeamRole::Admin;
    }
}
