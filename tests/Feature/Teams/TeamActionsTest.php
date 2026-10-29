<?php

use App\Actions\Teams\AcceptInvitation;
use App\Actions\Teams\ChangeMemberRole;
use App\Actions\Teams\CreateInvitation;
use App\Actions\Teams\CreateTeam;
use App\Actions\Teams\RemoveTeamMember;
use App\Enums\TeamRole;
use App\Exceptions\TeamMustKeepAnAdmin;
use App\Models\Invitation;
use App\Models\Membership;
use App\Models\Team;
use App\Models\User;

function membershipOf(Team $team, User $user): Membership
{
    return $team->memberships()->where('user_id', $user->id)->sole();
}

describe('CreateTeam', function () {
    it('makes the creator the first admin', function () {
        $creator = User::factory()->create();

        $team = app(CreateTeam::class)->handle($creator, 'Platform');

        expect($team->name)->toBe('Platform')
            ->and($team->roleOf($creator))->toBe(TeamRole::Admin)
            ->and($team->memberships()->count())->toBe(1);
    });
});

describe('CreateInvitation', function () {
    it('creates an invite link that expires after the configured lifetime', function () {
        // Timestamps are stored with second precision.
        $this->freezeSecond();
        $admin = User::factory()->create();
        $team = Team::factory()->withMember($admin, TeamRole::Admin)->create();

        $invitation = app(CreateInvitation::class)->handle($team, $admin, TeamRole::Admin);

        expect($invitation->team_id)->toBe($team->id)
            ->and($invitation->role)->toBe(TeamRole::Admin)
            ->and($invitation->created_by)->toBe($admin->id)
            ->and($invitation->expires_at->equalTo(now()->addDays(Invitation::LIFETIME_DAYS)))->toBeTrue();
    });
});

describe('AcceptInvitation', function () {
    it('adds the user with the role of the invitation', function () {
        $invitation = Invitation::factory()->role(TeamRole::Admin)->create();
        $user = User::factory()->create();

        $joined = app(AcceptInvitation::class)->handle($invitation, $user);

        expect($joined)->toBeTrue()
            ->and($invitation->team->roleOf($user))->toBe(TeamRole::Admin);
    });

    it('never changes the role of an existing member', function () {
        $admin = User::factory()->create();
        $team = Team::factory()->withMember($admin, TeamRole::Admin)->create();
        $invitation = Invitation::factory()->for($team)->role(TeamRole::Member)->create();

        $joined = app(AcceptInvitation::class)->handle($invitation, $admin);

        expect($joined)->toBeFalse()
            ->and($team->roleOf($admin))->toBe(TeamRole::Admin)
            ->and($team->memberships()->count())->toBe(1);
    });
});

describe('ChangeMemberRole', function () {
    it('promotes and demotes members', function () {
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->withMember($admin, TeamRole::Admin)->withMember($member)->create();

        app(ChangeMemberRole::class)->handle(membershipOf($team, $member), TeamRole::Admin);
        app(ChangeMemberRole::class)->handle(membershipOf($team, $admin), TeamRole::Member);

        expect($team->roleOf($member))->toBe(TeamRole::Admin)
            ->and($team->roleOf($admin))->toBe(TeamRole::Member);
    });

    it('refuses to demote the last admin', function () {
        $admin = User::factory()->create();
        $team = Team::factory()->withMember($admin, TeamRole::Admin)->withMember(User::factory()->create())->create();

        expect(fn () => app(ChangeMemberRole::class)->handle(membershipOf($team, $admin), TeamRole::Member))
            ->toThrow(TeamMustKeepAnAdmin::class);

        expect($team->roleOf($admin))->toBe(TeamRole::Admin);
    });
});

describe('RemoveTeamMember', function () {
    it('removes a member', function () {
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->withMember($admin, TeamRole::Admin)->withMember($member)->create();

        app(RemoveTeamMember::class)->handle(membershipOf($team, $member));

        expect($team->roleOf($member))->toBeNull();
    });

    it('lets an admin leave when another admin remains', function () {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $team = Team::factory()->withMember($first, TeamRole::Admin)->withMember($second, TeamRole::Admin)->create();

        app(RemoveTeamMember::class)->handle(membershipOf($team, $first));

        expect($team->roleOf($first))->toBeNull()
            ->and($team->roleOf($second))->toBe(TeamRole::Admin);
    });

    it('refuses to remove the last admin', function () {
        $admin = User::factory()->create();
        $team = Team::factory()->withMember($admin, TeamRole::Admin)->create();

        expect(fn () => app(RemoveTeamMember::class)->handle(membershipOf($team, $admin)))
            ->toThrow(TeamMustKeepAnAdmin::class);

        expect($team->roleOf($admin))->toBe(TeamRole::Admin);
    });
});
