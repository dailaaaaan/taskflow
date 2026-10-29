<?php

namespace App\Actions\Teams;

use App\Models\Membership;
use Illuminate\Support\Facades\DB;

/**
 * Removes a member from a team. Used both when an admin removes someone and
 * when a member leaves, so the "at least one admin" rule applies to both.
 */
final class RemoveTeamMember
{
    public function __construct(private readonly EnsureTeamKeepsAnAdmin $ensureTeamKeepsAnAdmin) {}

    public function handle(Membership $membership): void
    {
        DB::transaction(function () use ($membership): void {
            $this->ensureTeamKeepsAnAdmin->handle($membership);

            $membership->delete();
        });
    }
}
