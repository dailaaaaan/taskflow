<?php

namespace Database\Seeders;

use App\Enums\TeamRole;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed a ready-to-explore workspace: a demo account that administers a
     * team with one teammate and two boards full of lists and cards.
     */
    public function run(): void
    {
        $demo = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@taskflow.test',
        ]);

        $teammate = User::factory()->create([
            'name' => 'Sam Taylor',
            'email' => 'sam@taskflow.test',
        ]);

        $team = Team::factory()
            ->withMember($demo, TeamRole::Admin)
            ->withMember($teammate, TeamRole::Member)
            ->create(['name' => 'Acme Product Team']);

        $boards = [
            'Product Roadmap' => 'indigo',
            'Website Relaunch' => 'emerald',
        ];

        foreach ($boards as $title => $color) {
            $board = Board::factory()->for($team)->create([
                'title' => $title,
                'color' => $color,
                'created_by' => $demo->id,
            ]);

            $this->seedLists($board, [$demo, $teammate]);
        }
    }

    /**
     * @param  list<User>  $assignees
     */
    private function seedLists(Board $board, array $assignees): void
    {
        foreach (['To do', 'In progress', 'Done'] as $index => $title) {
            $list = BoardList::factory()->for($board)->create([
                'title' => $title,
                'position' => ($index + 1) * 1024,
            ]);

            Card::factory()
                ->count(3)
                ->for($list, 'boardList')
                ->state(new Sequence(fn (Sequence $sequence): array => [
                    'position' => ($sequence->index + 1) * 1024,
                    'assignee_id' => $assignees[$sequence->index % count($assignees)]->id,
                    'created_by' => $assignees[0]->id,
                ]))
                ->create();
        }
    }
}
