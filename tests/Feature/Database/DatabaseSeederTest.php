<?php

use App\Enums\TeamRole;
use App\Models\Board;
use App\Models\Card;
use App\Models\Team;
use App\Models\User;

it('seeds a demo workspace administered by the demo account', function () {
    $this->seed();

    $demo = User::query()->where('email', 'demo@taskflow.test')->sole();
    $team = $demo->teams()->sole();

    expect($team->membership->role)->toBe(TeamRole::Admin)
        ->and($team->members()->count())->toBe(2)
        ->and(Team::query()->count())->toBe(1);
});

it('seeds boards with three ordered lists of three cards each', function () {
    $this->seed();

    $boards = Board::query()->with('lists.cards')->get();

    expect($boards)->toHaveCount(2);

    foreach ($boards as $board) {
        expect($board->lists->pluck('title')->all())->toBe(['To do', 'In progress', 'Done'])
            ->and($board->lists->pluck('position')->all())->toBe([1024, 2048, 3072]);

        foreach ($board->lists as $list) {
            expect($list->cards->pluck('position')->all())->toBe([1024, 2048, 3072]);
        }
    }

    expect(Card::query()->whereNull('assignee_id')->count())->toBe(0);
});
