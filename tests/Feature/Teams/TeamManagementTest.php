<?php

use App\Enums\TeamRole;
use App\Models\Board;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

describe('dashboard', function () {
    it('lists only the teams of the user, with role and counts', function () {
        $user = User::factory()->create();
        $mine = Team::factory()->withMember($user, TeamRole::Admin)->withMember(User::factory()->create())->create(['name' => 'Alpha']);
        Board::factory()->for($mine)->count(2)->create();
        Team::factory()->create(['name' => 'Not mine']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->has('teams', 1)
                ->where('teams.0.name', 'Alpha')
                ->where('teams.0.role', 'admin')
                ->where('teams.0.members_count', 2)
                ->where('teams.0.boards_count', 2));
    });
});

describe('creating a team', function () {
    it('creates the team with the creator as admin', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('teams.store'), ['name' => 'Platform']);

        $team = Team::query()->sole();
        $response->assertRedirect(route('teams.show', $team));
        expect($team->name)->toBe('Platform')
            ->and($team->roleOf($user))->toBe(TeamRole::Admin);
    });

    it('validates the name', function (mixed $name) {
        $this->actingAs(User::factory()->create())
            ->post(route('teams.store'), ['name' => $name])
            ->assertSessionHasErrors('name');

        expect(Team::query()->count())->toBe(0);
    })->with([
        'missing' => [''],
        'too long' => [str_repeat('a', 101)],
    ]);

    it('requires authentication', function () {
        $this->post(route('teams.store'), ['name' => 'Platform'])->assertRedirect(route('login'));
    });
});

describe('viewing a team', function () {
    it('shows the team, its members and the abilities of the viewer', function () {
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->withMember($admin, TeamRole::Admin)->withMember($member)->create();

        $this->actingAs($member)
            ->get(route('teams.show', $team))
            ->assertInertia(fn (Assert $page) => $page
                ->component('teams/show')
                ->where('team.name', $team->name)
                ->has('members', 2)
                ->where('members.0.role', 'admin')
                ->where('members.1.is_current_user', true)
                ->where('can.update', false)
                ->where('can.leave', true));
    });

    it('forbids non-members', function (string $route) {
        $team = Team::factory()->withMember(User::factory()->create(), TeamRole::Admin)->create();

        $this->actingAs(User::factory()->create())->get(route($route, $team))->assertForbidden();
    })->with(['teams.show', 'teams.edit']);

    it('only lists active invite links to admins', function () {
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->withMember($admin, TeamRole::Admin)->withMember($member)->create();
        $team->invitations()->create(['role' => TeamRole::Member, 'expires_at' => now()->addDay()]);
        $team->invitations()->create(['role' => TeamRole::Member, 'expires_at' => now()->subDay()]);

        $this->actingAs($admin)->get(route('teams.edit', $team))
            ->assertInertia(fn (Assert $page) => $page->component('teams/settings')->has('invitations', 1));

        $this->actingAs($member)->get(route('teams.edit', $team))
            ->assertInertia(fn (Assert $page) => $page->has('invitations', 0));
    });
});

describe('renaming and deleting a team', function () {
    it('lets admins rename the team', function () {
        $admin = User::factory()->create();
        $team = Team::factory()->withMember($admin, TeamRole::Admin)->create();

        $this->actingAs($admin)->patch(route('teams.update', $team), ['name' => 'Renamed'])->assertRedirect();

        expect($team->fresh()?->name)->toBe('Renamed');
    });

    it('lets admins delete the team', function () {
        $admin = User::factory()->create();
        $team = Team::factory()->withMember($admin, TeamRole::Admin)->create();

        $this->actingAs($admin)->delete(route('teams.destroy', $team))->assertRedirect(route('dashboard'));

        $this->assertModelMissing($team);
    });

    it('forbids members before validating their input', function () {
        $member = User::factory()->create();
        $team = Team::factory()->withMember(User::factory()->create(), TeamRole::Admin)->withMember($member)->create();

        // Invalid payload on purpose: authorization must answer first (403, not 422).
        $this->actingAs($member)->patch(route('teams.update', $team), ['name' => ''])->assertForbidden();
        $this->actingAs($member)->delete(route('teams.destroy', $team))->assertForbidden();

        $this->assertModelExists($team);
    });
});
