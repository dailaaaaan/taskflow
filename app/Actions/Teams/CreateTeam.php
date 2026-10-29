<?php

namespace App\Actions\Teams;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateTeam
{
    /**
     * Create a team whose first member, and admin, is its creator.
     */
    public function handle(User $creator, string $name): Team
    {
        return DB::transaction(function () use ($creator, $name): Team {
            $team = Team::query()->create(['name' => $name]);

            $team->members()->attach($creator, ['role' => TeamRole::Admin]);

            return $team;
        });
    }
}
