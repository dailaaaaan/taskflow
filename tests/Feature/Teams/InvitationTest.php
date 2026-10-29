<?php

use App\Enums\TeamRole;
use App\Models\Invitation;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

function signedInvitationUrl(Invitation $invitation, string $route = 'invitations.show'): string
{
    return URL::temporarySignedRoute($route, $invitation->expires_at, ['invitation' => $invitation->id], absolute: false);
}

describe('managing invite links', function () {
    it('lets admins create an invite link for a role', function () {
        $admin = User::factory()->create();
        $team = Team::factory()->withMember($admin, TeamRole::Admin)->create();

        $this->actingAs($admin)
            ->post(route('teams.invitations.store', $team), ['role' => 'admin'])
            ->assertRedirect();

        $invitation = $team->invitations()->sole();
        expect($invitation->role)->toBe(TeamRole::Admin)
            ->and($invitation->created_by)->toBe($admin->id);
    });

    it('lets admins revoke an invite link', function () {
        $admin = User::factory()->create();
        $team = Team::factory()->withMember($admin, TeamRole::Admin)->create();
        $invitation = Invitation::factory()->for($team)->create();

        $this->actingAs($admin)->delete(route('teams.invitations.destroy', [$team, $invitation]))->assertRedirect();

        $this->assertModelMissing($invitation);
    });

    it('forbids members from creating or revoking invite links', function () {
        $member = User::factory()->create();
        $team = Team::factory()->withMember(User::factory()->create(), TeamRole::Admin)->withMember($member)->create();
        $invitation = Invitation::factory()->for($team)->create();

        $this->actingAs($member)->post(route('teams.invitations.store', $team), ['role' => 'admin'])->assertForbidden();
        $this->actingAs($member)->delete(route('teams.invitations.destroy', [$team, $invitation]))->assertForbidden();

        expect($team->invitations()->count())->toBe(1);
    });
});

describe('accepting an invite link', function () {
    it('shows a preview of the team to invited users', function () {
        $admin = User::factory()->create(['name' => 'Ada']);
        $team = Team::factory()->withMember($admin, TeamRole::Admin)->create(['name' => 'Platform']);
        $invitation = Invitation::factory()->for($team)->create(['created_by' => $admin->id]);

        $this->actingAs(User::factory()->create())
            ->get(signedInvitationUrl($invitation))
            ->assertInertia(fn (Assert $page) => $page
                ->component('invitations/show')
                ->where('invitation.team', 'Platform')
                ->where('invitation.invited_by', 'Ada')
                ->where('invitation.members_count', 1)
                ->has('acceptUrl'));
    });

    it('adds the user to the team with the invited role', function () {
        $invitation = Invitation::factory()->role(TeamRole::Admin)->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(signedInvitationUrl($invitation, 'invitations.accept'))
            ->assertRedirect(route('teams.show', $invitation->team));

        expect($invitation->team->roleOf($user))->toBe(TeamRole::Admin);
    });

    it('sends existing members straight to the team without changing their role', function () {
        $admin = User::factory()->create();
        $team = Team::factory()->withMember($admin, TeamRole::Admin)->create();
        $invitation = Invitation::factory()->for($team)->role(TeamRole::Member)->create();

        $this->actingAs($admin)->get(signedInvitationUrl($invitation))->assertRedirect(route('teams.show', $team));
        $this->actingAs($admin)->post(signedInvitationUrl($invitation, 'invitations.accept'))->assertRedirect(route('teams.show', $team));

        expect($team->roleOf($admin))->toBe(TeamRole::Admin);
    });

    it('rejects links with a tampered signature', function () {
        $invitation = Invitation::factory()->create();
        $url = signedInvitationUrl($invitation).'x';

        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
    });

    it('rejects unsigned accept requests', function () {
        $invitation = Invitation::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('invitations.accept', $invitation))->assertForbidden();

        expect($invitation->team->roleOf($user))->toBeNull();
    });

    it('rejects expired links', function () {
        $invitation = Invitation::factory()->create(['expires_at' => now()->addDay()]);
        $url = signedInvitationUrl($invitation);

        $this->travel(2)->days();

        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
    });

    it('returns 404 for revoked links', function () {
        $invitation = Invitation::factory()->create();
        $url = signedInvitationUrl($invitation);
        $invitation->delete();

        $this->actingAs(User::factory()->create())->get($url)->assertNotFound();
    });

    it('asks guests to log in first and brings them back to the link', function () {
        $invitation = Invitation::factory()->create();
        $url = signedInvitationUrl($invitation);

        $this->get($url)->assertRedirect(route('login'));

        expect(session('url.intended'))->toEndWith($url);
    });
});
