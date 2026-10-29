<?php

namespace App\Actions\Teams;

use App\Enums\TeamRole;
use App\Models\Invitation;
use App\Models\Team;
use App\Models\User;

final class CreateInvitation
{
    /**
     * Create a reusable invite link that grants $role for a limited time.
     */
    public function handle(Team $team, User $inviter, TeamRole $role): Invitation
    {
        $invitation = $team->invitations()->make([
            'role' => $role,
            'expires_at' => now()->addDays(Invitation::LIFETIME_DAYS),
        ]);

        $invitation->creator()->associate($inviter);
        $invitation->save();

        return $invitation;
    }
}
