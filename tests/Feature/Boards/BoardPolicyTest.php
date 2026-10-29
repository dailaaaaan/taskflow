<?php

use App\Enums\TeamRole;
use App\Models\Board;
use App\Models\Team;
use App\Models\User;

/*
| Role × ability matrix for BoardPolicy. Every ability is checked for an admin
| and a member of the board's team, and for an outsider.
*/

dataset('board abilities', [
    //                       admin  member outsider
    'view' => ['view', true, true, false],
    'collaborate' => ['collaborate', true, true, false],
    'update' => ['update', true, false, false],
    'delete' => ['delete', true, false, false],
]);

it('grants each board ability according to the role', function (string $ability, bool $admin, bool $member, bool $outsider) {
    $adminUser = User::factory()->create();
    $memberUser = User::factory()->create();
    $outsiderUser = User::factory()->create();

    $team = Team::factory()
        ->withMember($adminUser, TeamRole::Admin)
        ->withMember($memberUser, TeamRole::Member)
        ->create();
    $board = Board::factory()->for($team)->create();

    expect($adminUser->can($ability, $board))->toBe($admin)
        ->and($memberUser->can($ability, $board))->toBe($member)
        ->and($outsiderUser->can($ability, $board))->toBe($outsider);
})->with('board abilities');

it('lets every member of the team create boards', function () {
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->withMember($admin, TeamRole::Admin)->withMember($member)->create();

    expect($admin->can('create', [Board::class, $team]))->toBeTrue()
        ->and($member->can('create', [Board::class, $team]))->toBeTrue()
        ->and(User::factory()->create()->can('create', [Board::class, $team]))->toBeFalse();
});

it('does not grant access to boards of another team', function () {
    $admin = User::factory()->create();
    Team::factory()->withMember($admin, TeamRole::Admin)->create();
    $foreignBoard = Board::factory()->create();

    expect($admin->can('view', $foreignBoard))->toBeFalse()
        ->and($admin->can('delete', $foreignBoard))->toBeFalse();
});
