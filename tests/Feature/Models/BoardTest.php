<?php

use App\Enums\BoardColor;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\Membership;
use App\Models\Team;
use App\Models\User;

it('returns lists ordered by position, then by id', function () {
    $board = Board::factory()->create();
    $third = BoardList::factory()->for($board)->create(['position' => 3072]);
    $first = BoardList::factory()->for($board)->create(['position' => 1024]);
    $tieA = BoardList::factory()->for($board)->create(['position' => 2048]);
    $tieB = BoardList::factory()->for($board)->create(['position' => 2048]);

    expect($board->lists()->pluck('id')->all())
        ->toBe([$first->id, $tieA->id, $tieB->id, $third->id]);
});

it('returns cards ordered by position, then by id', function () {
    $list = BoardList::factory()->create();
    $second = Card::factory()->for($list, 'boardList')->create(['position' => 2048]);
    $first = Card::factory()->for($list, 'boardList')->create(['position' => 1024]);

    expect($list->cards()->pluck('id')->all())->toBe([$first->id, $second->id]);
});

it('reaches the cards of all its lists, and only those', function () {
    $board = Board::factory()->create();
    $todo = BoardList::factory()->for($board)->create();
    $done = BoardList::factory()->for($board)->create();
    $cards = Card::factory()->for($todo, 'boardList')->count(2)->create()
        ->merge(Card::factory()->for($done, 'boardList')->count(1)->create());
    Card::factory()->create();

    expect($board->cards()->pluck('cards.id')->sort()->values()->all())
        ->toBe($cards->pluck('id')->sort()->values()->all());
});

it('casts the color to the BoardColor enum', function () {
    $board = Board::factory()->create(['color' => 'rose']);

    expect($board->fresh()?->color)->toBe(BoardColor::Rose);
});

it('cascades a team deletion to its boards, lists and cards', function () {
    $team = Team::factory()->withMember(User::factory()->create())->create();
    $board = Board::factory()->for($team)->create();
    $list = BoardList::factory()->for($board)->create();
    $card = Card::factory()->for($list, 'boardList')->create();

    $team->delete();

    $this->assertModelMissing($board);
    $this->assertModelMissing($list);
    $this->assertModelMissing($card);
    expect(Membership::query()->count())->toBe(0);
});

it('keeps cards and boards when their assignee or creator is deleted', function () {
    $user = User::factory()->create();
    $board = Board::factory()->create(['created_by' => $user->id]);
    $card = Card::factory()->create([
        'assignee_id' => $user->id,
        'created_by' => $user->id,
    ]);

    $user->delete();

    expect($card->fresh())
        ->assignee_id->toBeNull()
        ->created_by->toBeNull()
        ->and($board->fresh()->created_by)->toBeNull();
});

it('casts the due date to an immutable date', function () {
    $card = Card::factory()->create(['due_date' => '2026-11-15']);

    expect($card->fresh()->due_date?->toDateString())->toBe('2026-11-15');
});
