<?php

namespace Database\Seeders;

use App\Enums\TeamRole;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * The shared demo workspace. It runs in production, where Faker and the model
 * factories are not installed, and on every start, so it replaces whatever
 * visitors left behind.
 */
class DemoSeeder extends Seeder
{
    /** @var array<string, array{color: string, lists: array<string, list<string>>}> */
    private const array BOARDS = [
        'Product Roadmap' => [
            'color' => 'indigo',
            'lists' => [
                'To do' => ['Write the onboarding guide', 'Plan the beta launch', 'Interview five early users'],
                'In progress' => ['Design the pricing page', 'Add team invitations', 'Improve the mobile layout'],
                'Done' => ['Set up the project', 'Build the first prototype', 'Define the product vision'],
            ],
        ],
        'Website Relaunch' => [
            'color' => 'emerald',
            'lists' => [
                'To do' => ['Update the about page', 'Translate the help center', 'Add customer stories'],
                'In progress' => ['Rewrite the home page copy', 'Compress the images', 'Review the contact form'],
                'Done' => ['Choose the new typography', 'Audit the current pages', 'Agree on the sitemap'],
            ],
        ],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->removePreviousWorkspace();

            // Nobody needs this password in production: visitors enter with
            // one click, and without it they cannot change the account.
            $password = Hash::make(app()->isProduction() ? Str::random(64) : 'password');

            $demo = $this->account('demo', 'Demo User', $password);
            $teammate = $this->account('sam', 'Sam Taylor', $password);

            $team = Team::query()->create(['name' => 'Acme Product Team']);
            $team->members()->attach($demo, ['role' => TeamRole::Admin]);
            $team->members()->attach($teammate, ['role' => TeamRole::Member]);

            foreach (self::BOARDS as $title => $board) {
                $this->createBoard($team, $title, $board['color'], $board['lists'], [$demo, $teammate]);
            }
        });
    }

    private function removePreviousWorkspace(): void
    {
        $users = User::query()->whereIn('email', config()->array('demo.accounts'));

        Team::query()->whereHas('members', fn ($members) => $members->whereIn('users.id', $users->clone()->select('id')))->delete();
        $users->delete();
    }

    private function account(string $key, string $name, string $password): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => config()->string("demo.accounts.{$key}"),
            'password' => $password,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @param  array<string, list<string>>  $lists
     * @param  list<User>  $assignees
     */
    private function createBoard(Team $team, string $title, string $color, array $lists, array $assignees): void
    {
        $board = Board::query()->create([
            'team_id' => $team->id,
            'title' => $title,
            'color' => $color,
            'created_by' => $assignees[0]->id,
        ]);

        foreach (array_keys($lists) as $index => $listTitle) {
            $list = BoardList::query()->create([
                'board_id' => $board->id,
                'title' => $listTitle,
                'position' => ($index + 1) * 1024,
            ]);

            foreach ($lists[$listTitle] as $cardIndex => $cardTitle) {
                Card::query()->create([
                    'board_list_id' => $list->id,
                    'title' => $cardTitle,
                    'position' => ($cardIndex + 1) * 1024,
                    'assignee_id' => $assignees[$cardIndex % count($assignees)]->id,
                    'created_by' => $assignees[0]->id,
                    'due_date' => $cardIndex === 0 ? now()->addDays(7) : null,
                ]);
            }
        }
    }
}
