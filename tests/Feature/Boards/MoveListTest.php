<?php

use App\Actions\Lists\MoveList;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\User;

/**
 * A board with lists titled $titles, spaced by the default gap.
 *
 * @param  list<string>  $titles
 */
function withLists(Board $board, array $titles): Board
{
    foreach ($titles as $i => $title) {
        BoardList::factory()->for($board)->create(['title' => $title, 'position' => ($i + 1) * 1024]);
    }

    return $board;
}

/**
 * @return list<string>
 */
function listTitles(Board $board): array
{
    return $board->lists()->pluck('title')->all();
}

describe('MoveList', function () {
    it('moves a list to another position of its board', function () {
        $board = withLists(Board::factory()->create(), ['To do', 'Doing', 'Done']);

        app(MoveList::class)->handle($board->lists()->where('title', 'Done')->sole(), 0);

        expect(listTitles($board))->toBe(['Done', 'To do', 'Doing']);
    });

    it('renumbers the board when the neighbours have no gap left', function () {
        $board = Board::factory()->create();
        BoardList::factory()->for($board)->create(['title' => 'A', 'position' => 1]);
        $moved = BoardList::factory()->for($board)->create(['title' => 'B', 'position' => 2]);

        app(MoveList::class)->handle($moved, 0);

        expect(listTitles($board))->toBe(['B', 'A'])
            ->and($board->lists()->pluck('position')->all())->toBe([512, 1024]);
    });

    it('leaves the lists of other boards untouched', function () {
        $board = withLists(Board::factory()->create(), ['A', 'B']);
        $other = withLists(Board::factory()->create(), ['X', 'Y']);

        app(MoveList::class)->handle($board->lists()->where('title', 'B')->sole(), 0);

        expect(listTitles($board))->toBe(['B', 'A'])
            ->and($other->lists()->pluck('position')->all())->toBe([1024, 2048]);
    });
});

describe('moving lists over HTTP', function () {
    it('lets members reorder the lists of the board', function () {
        [$board, , $member] = boardWithAdminAndMember();
        withLists($board, ['To do', 'Doing', 'Done']);

        $this->actingAs($member)
            ->patch(route('boards.lists.move', [$board, $board->lists()->where('title', 'To do')->sole()]), ['index' => 2])
            ->assertRedirect();

        expect(listTitles($board))->toBe(['Doing', 'Done', 'To do']);
    });

    it('validates the index', function (mixed $index) {
        [$board, , $member] = boardWithAdminAndMember();
        withLists($board, ['A']);

        $this->actingAs($member)
            ->patch(route('boards.lists.move', [$board, $board->lists()->sole()]), ['index' => $index])
            ->assertSessionHasErrors('index');
    })->with([
        'missing' => [null],
        'negative' => [-1],
    ]);

    it('forbids non-members before validating their input', function () {
        [$board] = boardWithAdminAndMember();
        withLists($board, ['A', 'B']);

        $this->actingAs(User::factory()->create())
            ->patch(route('boards.lists.move', [$board, $board->lists()->first()]), ['index' => -1])
            ->assertForbidden();

        expect(listTitles($board))->toBe(['A', 'B']);
    });

    it('does not resolve a list of another board', function () {
        [$board, , $member] = boardWithAdminAndMember();
        $foreignList = BoardList::factory()->create();

        $this->actingAs($member)
            ->patch(route('boards.lists.move', [$board, $foreignList]), ['index' => 0])
            ->assertNotFound();
    });
});
