<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

/*
| Role × ability matrix for TeamPolicy. Every ability is checked for an admin,
| a member and an outsider, so a permission change is always a test change.
*/

dataset('abilities', [
    //                admin  member outsider
    'view' => ['view', true, true, false],
    'update' => ['update', true, false, false],
    'delete' => ['delete', true, false, false],
    'invite' => ['invite', true, false, false],
    'manageMembers' => ['manageMembers', true, false, false],
    'leave' => ['leave', true, true, false],
]);

it('grants each ability according to the role', function (string $ability, bool $admin, bool $member, bool $outsider) {
    $adminUser = User::factory()->create();
    $memberUser = User::factory()->create();
    $outsiderUser = User::factory()->create();

    $team = Team::factory()
        ->withMember($adminUser, TeamRole::Admin)
        ->withMember($memberUser, TeamRole::Member)
        ->create();

    expect($adminUser->can($ability, $team))->toBe($admin)
        ->and($memberUser->can($ability, $team))->toBe($member)
        ->and($outsiderUser->can($ability, $team))->toBe($outsider);
})->with('abilities');

it('resolves the role of members and returns null for outsiders', function () {
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()
        ->withMember($admin, TeamRole::Admin)
        ->withMember($member)
        ->create();

    expect($team->roleOf($admin))->toBe(TeamRole::Admin)
        ->and($team->roleOf($member))->toBe(TeamRole::Member)
        ->and($team->roleOf(User::factory()->create()))->toBeNull();
});
