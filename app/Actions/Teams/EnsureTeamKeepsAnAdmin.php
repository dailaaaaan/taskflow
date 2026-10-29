<?php

namespace App\Actions\Teams;

use App\Enums\TeamRole;
use App\Exceptions\TeamMustKeepAnAdmin;
use App\Models\Membership;

/**
 * Guards the invariant "a team always has at least one admin".
 *
 * Must run inside the same transaction as the demotion or removal it guards:
 * the team's admin rows are locked (SELECT ... FOR UPDATE), so two admins
 * demoting each other concurrently cannot both succeed.
 */
final class EnsureTeamKeepsAnAdmin
{
    /**
     * @throws TeamMustKeepAnAdmin when $membership is the team's only admin
     */
    public function handle(Membership $membership): void
    {
        // PostgreSQL rejects FOR UPDATE with aggregates, so fetch ids and count in PHP.
        $adminIds = Membership::query()
            ->where('team_id', $membership->team_id)
            ->where('role', TeamRole::Admin)
            ->lockForUpdate()
            ->pluck('id');

        if ($adminIds->count() === 1 && $adminIds->contains($membership->id)) {
            throw new TeamMustKeepAnAdmin;
        }
    }
}
