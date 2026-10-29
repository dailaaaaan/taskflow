<?php

namespace App\Actions\Teams;

use App\Enums\TeamRole;
use App\Models\Membership;
use Illuminate\Support\Facades\DB;

final class ChangeMemberRole
{
    public function __construct(private readonly EnsureTeamKeepsAnAdmin $ensureTeamKeepsAnAdmin) {}

    public function handle(Membership $membership, TeamRole $role): void
    {
        DB::transaction(function () use ($membership, $role): void {
            if ($role !== TeamRole::Admin) {
                $this->ensureTeamKeepsAnAdmin->handle($membership);
            }

            $membership->update(['role' => $role]);
        });
    }
}
