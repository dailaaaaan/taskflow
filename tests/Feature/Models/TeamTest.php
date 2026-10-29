<?php

use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

it('exposes each member with their role', function () {
    $admin = User::factory()->create();
    $member = User::factory()->create();

    $team = Team::factory()
        ->withMember($admin, TeamRole::Admin)
        ->withMember($member)
        ->create();

    $roles = $team->members()->get()->mapWithKeys(
        fn (User $user) => [$user->id => $user->membership->role],
    );

    expect($roles->all())->toBe([
        $admin->id => TeamRole::Admin,
        $member->id => TeamRole::Member,
    ]);
});

it('lists the teams a user belongs to', function () {
    $user = User::factory()->create();
    $teams = Team::factory()->count(2)->withMember($user)->create();
    Team::factory()->create();

    expect($user->teams()->pluck('teams.id')->sort()->values()->all())
        ->toBe($teams->pluck('id')->sort()->values()->all());
});

it('stores memberships as an auto-incrementing pivot model', function () {
    $user = User::factory()->create();
    $team = Team::factory()->withMember($user, TeamRole::Admin)->create();

    $membership = $team->memberships()->sole();

    expect($membership)->toBeInstanceOf(Membership::class)
        ->and($membership->id)->toBeInt()
        ->and($membership->role)->toBe(TeamRole::Admin)
        ->and($membership->user->is($user))->toBeTrue();
});

it('rejects a second membership for the same user and team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->withMember($user)->create();

    $team->members()->attach($user, ['role' => TeamRole::Admin]);
})->throws(UniqueConstraintViolationException::class);

it('removes memberships when the user is deleted', function () {
    $user = User::factory()->create();
    $team = Team::factory()->withMember($user)->create();

    $user->delete();

    expect($team->memberships()->count())->toBe(0);
});
