<?php

/*
|--------------------------------------------------------------------------
| Team journeys (real browser)
|--------------------------------------------------------------------------
*/

use App\Enums\TeamRole;
use App\Models\Invitation;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\URL;

it('creates a team from the dashboard', function () {
    $this->actingAs(User::factory()->create());

    visit('/dashboard')
        ->assertSee('No teams yet')
        ->click('@create-team-button')
        ->fill('name', 'Platform Team')
        ->click('@submit-team-button')
        ->assertPathBeginsWith('/teams/')
        ->assertSee('Platform Team')
        ->assertSee('Team created.')
        ->assertNoJavaScriptErrors();

    expect(Team::query()->where('name', 'Platform Team')->exists())->toBeTrue();
});

it('joins a team through an invite link after logging in', function () {
    $team = Team::factory()
        ->withMember(User::factory()->create(['name' => 'Ada Lovelace']), TeamRole::Admin)
        ->create(['name' => 'Acme Product Team']);
    $invitation = Invitation::factory()->for($team)->create();
    $invitee = User::factory()->create(['email' => 'grace@example.com']);

    $inviteUrl = URL::temporarySignedRoute('invitations.show', $invitation->expires_at, ['invitation' => $invitation->id], absolute: false);

    visit($inviteUrl)
        ->assertPathIs('/login')
        ->fill('email', 'grace@example.com')
        ->fill('password', 'password')
        ->press('Log in')
        ->assertSee('Join Acme Product Team')
        ->click('@accept-invitation-button')
        ->assertPathIs("/teams/{$team->id}")
        ->assertSee('Welcome to Acme Product Team!')
        ->assertNoJavaScriptErrors();

    expect($team->roleOf($invitee))->toBe(TeamRole::Member);
});

it('renders the team pages without accessibility issues', function () {
    $admin = User::factory()->create();
    $team = Team::factory()->withMember($admin, TeamRole::Admin)->withMember(User::factory()->create())->create();
    Invitation::factory()->for($team)->create();

    $this->actingAs($admin);

    foreach (['/dashboard', "/teams/{$team->id}", "/teams/{$team->id}/settings"] as $path) {
        visit($path)->assertNoSmoke()->assertNoAccessibilityIssues();
    }
});
