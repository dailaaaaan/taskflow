<?php

use App\Enums\BoardColor;
use App\Enums\TeamRole;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

describe('team page', function () {
    it('lists the boards of the team with their counts', function () {
        [$board, , $member] = boardWithAdminAndMember();
        $board->update(['title' => 'Alpha']);
        $lists = BoardList::factory()->for($board)->count(2)->create();
        Card::factory()->for($lists[0], 'boardList')->count(3)->create();
        Board::factory()->for($board->team)->create(['title' => 'Beta']);
        Board::factory()->create(['title' => 'Not ours']);

        $this->actingAs($member)
            ->get(route('teams.show', $board->team))
            ->assertInertia(fn (Assert $page) => $page
                ->component('teams/show')
                ->has('boards', 2)
                ->where('boards.0.title', 'Alpha')
                ->where('boards.0.lists_count', 2)
                ->where('boards.0.cards_count', 3)
                ->where('boards.1.title', 'Beta')
                ->where('can.createBoard', true));
    });
});

describe('creating a board', function () {
    it('lets any member create a board', function () {
        $member = User::factory()->create();
        $team = Team::factory()->withMember(User::factory()->create(), TeamRole::Admin)->withMember($member)->create();

        $response = $this->actingAs($member)
            ->post(route('teams.boards.store', $team), ['title' => 'Roadmap', 'color' => 'rose']);

        $board = $team->boards()->sole();
        $response->assertRedirect(route('boards.show', $board));
        expect($board->title)->toBe('Roadmap')
            ->and($board->color)->toBe(BoardColor::Rose)
            ->and($board->created_by)->toBe($member->id);
    });

    it('validates the title and color', function (array $payload, string $field) {
        $admin = User::factory()->create();
        $team = Team::factory()->withMember($admin, TeamRole::Admin)->create();

        $this->actingAs($admin)
            ->post(route('teams.boards.store', $team), $payload)
            ->assertSessionHasErrors($field);

        expect($team->boards()->count())->toBe(0);
    })->with([
        'missing title' => [['title' => '', 'color' => 'indigo'], 'title'],
        'title too long' => [['title' => str_repeat('a', 101), 'color' => 'indigo'], 'title'],
        'unknown color' => [['title' => 'Roadmap', 'color' => 'black'], 'color'],
    ]);

    it('forbids non-members before validating their input', function () {
        $team = Team::factory()->withMember(User::factory()->create(), TeamRole::Admin)->create();

        $this->actingAs(User::factory()->create())
            ->post(route('teams.boards.store', $team), ['title' => ''])
            ->assertForbidden();

        expect($team->boards()->count())->toBe(0);
    });
});

describe('viewing a board', function () {
    it('shows lists and cards in position order, with the abilities of the viewer', function () {
        [$board, $admin, $member] = boardWithAdminAndMember();
        $done = BoardList::factory()->for($board)->create(['title' => 'Done', 'position' => 2048]);
        $todo = BoardList::factory()->for($board)->create(['title' => 'To do', 'position' => 1024]);
        Card::factory()->for($todo, 'boardList')->create(['title' => 'Second', 'position' => 2048]);
        Card::factory()->for($todo, 'boardList')->create([
            'title' => 'First',
            'position' => 1024,
            'assignee_id' => $admin->id,
            'due_date' => '2026-11-15',
        ]);
        Card::factory()->for($done, 'boardList')->create(['title' => 'Shipped']);

        $this->actingAs($member)
            ->get(route('boards.show', $board))
            ->assertInertia(fn (Assert $page) => $page
                ->component('boards/show')
                ->where('team.name', $board->team->name)
                ->where('board.title', $board->title)
                ->where('lists.0.title', 'To do')
                ->where('lists.0.cards.0.title', 'First')
                ->where('lists.0.cards.0.assignee.name', $admin->name)
                ->where('lists.0.cards.0.due_date', '2026-11-15')
                ->where('lists.0.cards.1.title', 'Second')
                ->where('lists.0.cards.1.assignee', null)
                ->where('lists.1.title', 'Done')
                ->has('members', 2)
                ->where('can.collaborate', true)
                ->where('can.update', false)
                ->where('can.delete', false));
    });

    it('forbids non-members', function () {
        [$board] = boardWithAdminAndMember();

        $this->actingAs(User::factory()->create())->get(route('boards.show', $board))->assertForbidden();
    });
});

describe('editing and deleting a board', function () {
    it('lets admins edit the title and color', function () {
        [$board, $admin] = boardWithAdminAndMember();

        $this->actingAs($admin)
            ->patch(route('boards.update', $board), ['title' => 'Renamed', 'color' => 'sky'])
            ->assertRedirect();

        expect($board->fresh())
            ->title->toBe('Renamed')
            ->color->toBe(BoardColor::Sky);
    });

    it('lets admins delete the board with its lists and cards', function () {
        [$board, $admin] = boardWithAdminAndMember();
        $card = Card::factory()->for(BoardList::factory()->for($board), 'boardList')->create();

        $this->actingAs($admin)
            ->delete(route('boards.destroy', $board))
            ->assertRedirect(route('teams.show', $board->team_id));

        $this->assertModelMissing($board);
        $this->assertModelMissing($card);
    });

    it('forbids members before validating their input', function () {
        [$board, , $member] = boardWithAdminAndMember();

        // Invalid payload on purpose: authorization must answer first (403, not 422).
        $this->actingAs($member)->patch(route('boards.update', $board), ['title' => ''])->assertForbidden();
        $this->actingAs($member)->delete(route('boards.destroy', $board))->assertForbidden();

        $this->assertModelExists($board);
    });

    it('forbids non-members', function () {
        [$board] = boardWithAdminAndMember();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)->patch(route('boards.update', $board), ['title' => 'Mine', 'color' => 'rose'])->assertForbidden();
        $this->actingAs($outsider)->delete(route('boards.destroy', $board))->assertForbidden();

        $this->assertModelExists($board);
    });
});

it('redirects guests to the login page from every board endpoint', function () {
    $card = Card::factory()->create();
    $list = $card->boardList;
    $board = $list->board;

    $endpoints = [
        ['POST', route('teams.boards.store', $board->team_id)],
        ['GET', route('boards.show', $board)],
        ['PATCH', route('boards.update', $board)],
        ['DELETE', route('boards.destroy', $board)],
        ['POST', route('boards.lists.store', $board)],
        ['PATCH', route('boards.lists.update', [$board, $list])],
        ['DELETE', route('boards.lists.destroy', [$board, $list])],
        ['PATCH', route('boards.lists.move', [$board, $list])],
        ['POST', route('boards.lists.cards.store', [$board, $list])],
        ['PATCH', route('boards.cards.update', [$board, $card])],
        ['PATCH', route('boards.cards.move', [$board, $card])],
        ['DELETE', route('boards.cards.destroy', [$board, $card])],
    ];

    foreach ($endpoints as [$method, $url]) {
        $this->call($method, $url)->assertRedirect(route('login'));
    }
});
