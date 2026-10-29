<?php

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
