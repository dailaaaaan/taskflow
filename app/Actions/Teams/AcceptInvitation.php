<?php

namespace App\Actions\Teams;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class AcceptInvitation
{
    /**
     * Add the user to the invitation's team with the invitation's role.
     *
     * Existing members keep their current role: an invite link must never
     * demote an admin. Returns false when the user was already a member.
     */
    public function handle(Invitation $invitation, User $user): bool
    {
        $team = $invitation->team;

        if ($team->roleOf($user) !== null) {
            return false;
        }

        try {
            // A nested transaction (savepoint) keeps an enclosing PostgreSQL
            // transaction usable if the unique constraint fires.
            DB::transaction(fn () => $team->members()->attach($user, ['role' => $invitation->role]));
        } catch (UniqueConstraintViolationException) {
            // Accepted twice concurrently (e.g. a double click): already a member.
            return false;
        }

        return true;
    }
}
