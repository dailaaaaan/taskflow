<?php

use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Team;
use App\Models\User;

/**
 * @return array{Team, User, User, Membership}
 */
function teamWithAdminAndMember(): array
{
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->withMember($admin, TeamRole::Admin)->withMember($member)->create();

    return [$team, $admin, $member, $team->memberships()->where('user_id', $member->id)->sole()];
}

describe('changing roles', function () {
    it('lets admins promote a member', function () {
        [$team, $admin, $member, $membership] = teamWithAdminAndMember();

        $this->actingAs($admin)
            ->patch(route('teams.members.update', [$team, $membership]), ['role' => 'admin'])
            ->assertRedirect();

        expect($team->roleOf($member))->toBe(TeamRole::Admin);
    });

    it('keeps the last admin and explains why', function () {
        [$team, $admin] = teamWithAdminAndMember();
        $adminMembership = $team->memberships()->where('user_id', $admin->id)->sole();

        $this->actingAs($admin)
            ->from(route('teams.edit', $team))
            ->patch(route('teams.members.update', [$team, $adminMembership]), ['role' => 'member'])
            ->assertRedirect(route('teams.edit', $team))
            ->assertInertiaFlash('toast.type', 'error');

        expect($team->roleOf($admin))->toBe(TeamRole::Admin);
    });

    it('rejects unknown roles', function () {
        [$team, $admin, , $membership] = teamWithAdminAndMember();

        $this->actingAs($admin)
            ->patch(route('teams.members.update', [$team, $membership]), ['role' => 'owner'])
            ->assertSessionHasErrors('role');
    });

    it('forbids members', function () {
        [$team, , $member, $membership] = teamWithAdminAndMember();

        $this->actingAs($member)
            ->patch(route('teams.members.update', [$team, $membership]), ['role' => 'admin'])
            ->assertForbidden();

        expect($team->roleOf($member))->toBe(TeamRole::Member);
    });

    it('does not resolve a membership of another team', function () {
        [$team, $admin] = teamWithAdminAndMember();
        [, , , $foreignMembership] = teamWithAdminAndMember();

        $this->actingAs($admin)
            ->patch(route('teams.members.update', [$team, $foreignMembership]), ['role' => 'admin'])
            ->assertNotFound();
    });
});

describe('removing members', function () {
    it('lets admins remove a member', function () {
        [$team, $admin, $member, $membership] = teamWithAdminAndMember();

        $this->actingAs($admin)->delete(route('teams.members.destroy', [$team, $membership]))->assertRedirect();

        expect($team->roleOf($member))->toBeNull();
    });

    it('forbids members', function () {
        [$team, $admin, $member] = teamWithAdminAndMember();
        $adminMembership = $team->memberships()->where('user_id', $admin->id)->sole();

        $this->actingAs($member)->delete(route('teams.members.destroy', [$team, $adminMembership]))->assertForbidden();

        expect($team->roleOf($admin))->toBe(TeamRole::Admin);
    });
});

describe('leaving a team', function () {
    it('lets a member leave', function () {
        [$team, , $member] = teamWithAdminAndMember();

        $this->actingAs($member)->delete(route('teams.leave', $team))->assertRedirect(route('dashboard'));

        expect($team->roleOf($member))->toBeNull();
    });

    it('keeps the last admin in the team', function () {
        [$team, $admin] = teamWithAdminAndMember();

        $this->actingAs($admin)
            ->from(route('teams.edit', $team))
            ->delete(route('teams.leave', $team))
            ->assertRedirect(route('teams.edit', $team))
            ->assertInertiaFlash('toast.type', 'error');

        expect($team->roleOf($admin))->toBe(TeamRole::Admin);
    });

    it('forbids users who are not members', function () {
        [$team] = teamWithAdminAndMember();

        $this->actingAs(User::factory()->create())->delete(route('teams.leave', $team))->assertForbidden();
    });
});
