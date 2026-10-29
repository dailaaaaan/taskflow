<?php

use App\Actions\Boards\CreateBoard;
use App\Actions\Cards\CreateCard;
use App\Actions\Lists\CreateList;
use App\Enums\BoardColor;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\Team;
use App\Models\User;
use App\Support\Position;

describe('CreateBoard', function () {
    it('creates an empty board and remembers its creator', function () {
        $team = Team::factory()->create();
        $creator = User::factory()->create();

        $board = app(CreateBoard::class)->handle($team, $creator, 'Roadmap', BoardColor::Emerald);

        expect($board->team_id)->toBe($team->id)
            ->and($board->title)->toBe('Roadmap')
            ->and($board->fresh()?->color)->toBe(BoardColor::Emerald)
            ->and($board->created_by)->toBe($creator->id)
            ->and($board->lists()->count())->toBe(0);
    });
});

describe('CreateList', function () {
    it('places the first list one gap from zero', function () {
        $list = app(CreateList::class)->handle(Board::factory()->create(), 'To do');

        expect($list->position)->toBe(Position::GAP);
    });

    it('appends lists after the last one', function () {
        $board = Board::factory()->create();
        BoardList::factory()->for($board)->create(['position' => 5000]);
        BoardList::factory()->for($board)->create(['position' => 3000]);

        $list = app(CreateList::class)->handle($board, 'Done');

        expect($list->position)->toBe(5000 + Position::GAP)
            ->and($board->lists()->get()->last()?->is($list))->toBeTrue();
    });

    it('ignores lists of other boards', function () {
        BoardList::factory()->create(['position' => 90_000]);

        $list = app(CreateList::class)->handle(Board::factory()->create(), 'To do');

        expect($list->position)->toBe(Position::GAP);
    });
});

describe('CreateCard', function () {
    it('appends cards at the bottom of the list and remembers the creator', function () {
        $list = BoardList::factory()->create();
        Card::factory()->for($list, 'boardList')->create(['position' => 2048]);
        $creator = User::factory()->create();

        $card = app(CreateCard::class)->handle($list, $creator, 'Write the README');

        expect($card->board_list_id)->toBe($list->id)
            ->and($card->title)->toBe('Write the README')
            ->and($card->position)->toBe(2048 + Position::GAP)
            ->and($card->created_by)->toBe($creator->id)
            ->and($list->cards()->get()->last()?->is($card))->toBeTrue();
    });

    it('places the first card one gap from zero', function () {
        $card = app(CreateCard::class)->handle(BoardList::factory()->create(), User::factory()->create(), 'First');

        expect($card->position)->toBe(Position::GAP);
    });
});
