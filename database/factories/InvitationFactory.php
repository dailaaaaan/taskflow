<?php

namespace Database\Factories;

use App\Enums\TeamRole;
use App\Models\Invitation;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'role' => TeamRole::Member,
            'created_by' => null,
            'expires_at' => now()->addDays(Invitation::LIFETIME_DAYS),
        ];
    }

    /**
     * Indicate that the invite link is for the given role.
     */
    public function role(TeamRole $role): static
    {
        return $this->state(fn (array $attributes) => ['role' => $role]);
    }
}
