<?php

use App\Actions\Cards\MoveCard;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Titles of the list's cards in display order.
 *
 * @return list<string>
 */
function cardTitles(BoardList $list): array
{
    return $list->cards()->pluck('title')->all();
}

/**
 * A list with cards titled $titles, spaced by the default gap.
 *
 * @param  list<string>  $titles
 */
function listWithCards(Board $board, array $titles): BoardList
{
    $list = BoardList::factory()->for($board)->create();

    foreach ($titles as $i => $title) {
        Card::factory()->for($list, 'boardList')->create(['title' => $title, 'position' => ($i + 1) * 1024]);
    }

    return $list;
}

describe('MoveCard', function () {
    it('moves a card down within its list', function () {
        $list = listWithCards(Board::factory()->create(), ['A', 'B', 'C']);
        $a = $list->cards()->where('title', 'A')->sole();

        // Index 2 among the other two cards (B, C): after C.
        app(MoveCard::class)->handle($a, $list, 2);

        expect(cardTitles($list))->toBe(['B', 'C', 'A']);
    });

    it('moves a card to a position of another list', function () {
        $board = Board::factory()->create();
        $todo = listWithCards($board, ['A', 'B']);
        $done = listWithCards($board, ['X', 'Y']);
        $b = $todo->cards()->where('title', 'B')->sole();

        app(MoveCard::class)->handle($b, $done, 1);

        expect(cardTitles($todo))->toBe(['A'])
            ->and(cardTitles($done))->toBe(['X', 'B', 'Y']);
    });

    it('moves a card into an empty list', function () {
        $board = Board::factory()->create();
        $todo = listWithCards($board, ['A']);
        $empty = BoardList::factory()->for($board)->create();

        app(MoveCard::class)->handle($todo->cards()->sole(), $empty, 0);

        expect(cardTitles($todo))->toBe([])
            ->and(cardTitles($empty))->toBe(['A']);
    });

    it('writes a single row while there is a gap between the neighbours', function () {
        $list = listWithCards(Board::factory()->create(), ['A', 'B', 'C']);
        $c = $list->cards()->where('title', 'C')->sole();

        DB::enableQueryLog();
        app(MoveCard::class)->handle($c, $list, 1);
        $updates = collect(DB::getQueryLog())->filter(fn (array $query): bool => str_starts_with($query['query'], 'update'));

        expect($updates)->toHaveCount(1)
            ->and(cardTitles($list))->toBe(['A', 'C', 'B']);
    });

    it('renumbers the destination list when the neighbours have no gap left', function () {
        $list = BoardList::factory()->create();
        Card::factory()->for($list, 'boardList')->create(['title' => 'A', 'position' => 1024]);
        Card::factory()->for($list, 'boardList')->create(['title' => 'B', 'position' => 1025]);
        $moved = Card::factory()->for($list, 'boardList')->create(['title' => 'C', 'position' => 9000]);

        app(MoveCard::class)->handle($moved, $list, 1);

        expect(cardTitles($list))->toBe(['A', 'C', 'B'])
            ->and($list->cards()->pluck('position')->all())->toBe([1024, 1536, 2048]);
    });
});

describe('moving cards over HTTP', function () {
    it('lets members move a card within its list', function () {
        [$board, , $member] = boardWithAdminAndMember();
        $list = listWithCards($board, ['A', 'B', 'C']);
        $c = $list->cards()->where('title', 'C')->sole();

        $this->actingAs($member)
            ->patch(route('boards.cards.move', [$board, $c]), ['list_id' => $list->id, 'index' => 0])
            ->assertRedirect();

        expect(cardTitles($list))->toBe(['C', 'A', 'B']);
    });

    it('lets members move a card to another list of the board', function () {
        [$board, , $member] = boardWithAdminAndMember();
        $todo = listWithCards($board, ['A']);
        $done = listWithCards($board, ['X']);

        $this->actingAs($member)
            ->patch(route('boards.cards.move', [$board, $todo->cards()->sole()]), ['list_id' => $done->id, 'index' => 1])
            ->assertRedirect();

        expect(cardTitles($done))->toBe(['X', 'A']);
    });

    it('rejects a destination list of another board', function () {
        [$board, , $member] = boardWithAdminAndMember();
        $list = listWithCards($board, ['A']);
        $foreignList = BoardList::factory()->create();

        $this->actingAs($member)
            ->patch(route('boards.cards.move', [$board, $list->cards()->sole()]), ['list_id' => $foreignList->id, 'index' => 0])
            ->assertSessionHasErrors('list_id');

        expect(cardTitles($list))->toBe(['A']);
    });

    it('validates the index', function (mixed $index) {
        [$board, , $member] = boardWithAdminAndMember();
        $list = listWithCards($board, ['A']);

        $this->actingAs($member)
            ->patch(route('boards.cards.move', [$board, $list->cards()->sole()]), ['list_id' => $list->id, 'index' => $index])
            ->assertSessionHasErrors('index');
    })->with([
        'missing' => [null],
        'negative' => [-1],
        'not a number' => ['first'],
    ]);

    it('forbids non-members before validating their input', function () {
        [$board] = boardWithAdminAndMember();
        $list = listWithCards($board, ['A', 'B']);

        $this->actingAs(User::factory()->create())
            ->patch(route('boards.cards.move', [$board, $list->cards()->first()]), ['index' => -1])
            ->assertForbidden();

        expect(cardTitles($list))->toBe(['A', 'B']);
    });

    it('does not resolve a card of another board', function () {
        [$board, , $member] = boardWithAdminAndMember();
        $list = listWithCards($board, ['A']);
        $foreignCard = Card::factory()->create();

        $this->actingAs($member)
            ->patch(route('boards.cards.move', [$board, $foreignCard]), ['list_id' => $list->id, 'index' => 0])
            ->assertNotFound();

        expect(cardTitles($list))->toBe(['A']);
    });

    it('reloads only the lists of the board after a move', function () {
        [$board, , $member] = boardWithAdminAndMember();
        listWithCards($board, ['A', 'B']);

        $this->actingAs($member)
            ->get(route('boards.show', $board))
            ->assertInertia(fn (Assert $page) => $page
                ->reloadOnly('lists', fn (Assert $reload) => $reload
                    ->where('lists.0.cards.0.title', 'A')
                    ->missing('members')));
    });
});
