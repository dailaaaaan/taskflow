<?php

namespace Database\Factories;

use App\Models\Board;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Board>
 */
class BoardFactory extends Factory
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
            'title' => rtrim(fake()->sentence(3), '.'),
            'color' => fake()->randomElement(['indigo', 'emerald', 'amber', 'rose', 'sky', 'violet']),
            'created_by' => null,
        ];
    }
}
