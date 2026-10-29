<?php

/*
|--------------------------------------------------------------------------
| Board journeys (real browser)
|--------------------------------------------------------------------------
*/

use App\Enums\TeamRole;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\Team;
use App\Models\User;

it('creates a board, adds a list and a card, and edits the card details', function () {
    $admin = User::factory()->create();
    $team = Team::factory()->withMember($admin, TeamRole::Admin)->create();

    $this->actingAs($admin);

    visit("/teams/{$team->id}")
        ->assertSee('No boards yet')
        ->click('@create-board-button')
        ->fill('title', 'Website Relaunch')
        ->click('@submit-board-button')
        ->assertPathBeginsWith('/boards/')
        ->assertSee('Board created.')
        ->click('@add-list-button')
        ->fill('@new-list-title', 'To do')
        ->click('@submit-list-button')
        ->assertSee('To do')
        ->click('@add-card-button')
        ->fill('@new-card-title', 'Write the README')
        ->click('@submit-card-button')
        ->click('@card-item')
        ->assertSee('Card details')
        ->fill('description', 'Explain how to run the project locally.')
        ->fill('due_date', '2026-11-15')
        ->click('@save-card-button')
        ->assertDontSee('Card details')
        ->assertNoJavaScriptErrors();

    $board = Board::query()->sole();
    $card = Card::query()->sole();
    expect($board->title)->toBe('Website Relaunch')
        ->and($card->title)->toBe('Write the README')
        ->and($card->description)->toBe('Explain how to run the project locally.')
        ->and($card->due_date?->toDateString())->toBe('2026-11-15');
});

it('hides the admin controls of a board from members', function () {
    [$board, , $member] = boardWithAdminAndMember();

    $this->actingAs($member);

    visit("/boards/{$board->id}")
        ->assertSee($board->title)
        ->assertMissing('@edit-board-button')
        ->assertMissing('@delete-board-button')
        ->assertVisible('@add-list-button');
});

it('renders the board page and the card modal without accessibility issues', function () {
    [$board, $admin] = boardWithAdminAndMember();
    $list = BoardList::factory()->for($board)->create(['title' => 'In progress']);
    Card::factory()->for($list, 'boardList')->create([
        'title' => 'Design the board page',
        'description' => 'Columns, cards and the details modal.',
        'assignee_id' => $admin->id,
        'due_date' => now()->subDay(),
    ]);

    $this->actingAs($admin);

    visit("/boards/{$board->id}")
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues()
        ->click('@card-item')
        ->assertSee('Card details')
        ->assertNoAccessibilityIssues();
});
