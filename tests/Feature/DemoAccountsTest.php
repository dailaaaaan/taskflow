<?php

use App\Enums\TeamRole;
use App\Models\Board;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['demo.enabled' => true]);
});

function demoUser(string $account = 'demo'): User
{
    return User::query()->where('email', config()->string("demo.accounts.{$account}"))->sole();
}

it('is off unless it is turned on', function () {
    config(['demo.enabled' => false]);

    $this->post(route('demo.login', 'demo'))->assertNotFound();

    $this->assertGuest();
});

it('signs visitors in with one click and opens the first board', function (string $account) {
    $this->seed(DemoSeeder::class);

    $this->post(route('demo.login', $account))
        ->assertRedirect(route('boards.show', Board::query()->where('title', 'Product Roadmap')->sole()));

    $this->assertAuthenticatedAs(demoUser($account));
})->with(['demo', 'sam']);

it('rejects accounts that are not part of the demo', function () {
    $this->seed(DemoSeeder::class);

    $this->post('/demo/admin')->assertNotFound();

    $this->assertGuest();
});

it('leaves people who are already signed in alone', function () {
    $this->seed(DemoSeeder::class);
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('demo.login', 'demo'))->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('builds the workspace again if a visitor deleted it', function () {
    $this->seed(DemoSeeder::class);
    demoUser()->teams()->sole()->delete();

    $this->post(route('demo.login', 'sam'))->assertRedirect();

    expect(demoUser('sam')->teams()->sole()->boards()->count())->toBe(2);
});

it('builds the workspace on the first visit', function () {
    $this->post(route('demo.login', 'demo'))->assertRedirect();

    $this->assertAuthenticatedAs(demoUser());
});

it('sends visitors to their teams if every board was deleted', function () {
    $this->seed(DemoSeeder::class);
    Board::query()->delete();

    $this->post(route('demo.login', 'demo'))->assertRedirect(route('dashboard'));
});

it('puts the workspace back to its original state', function () {
    $this->seed(DemoSeeder::class);

    $visitorTeam = Team::factory()->withMember(demoUser(), TeamRole::Admin)->create(['name' => 'Left behind']);
    Board::query()->delete();
    demoUser()->update(['name' => 'Changed by a visitor']);

    $this->seed(DemoSeeder::class);

    expect(Team::query()->find($visitorTeam->id))->toBeNull()
        ->and(demoUser()->name)->toBe('Demo User')
        ->and(demoUser()->teams()->sole()->boards()->count())->toBe(2)
        ->and(User::query()->count())->toBe(2);
});

it('keeps real users and their teams', function () {
    $this->seed(DemoSeeder::class);
    $user = User::factory()->create();
    $team = Team::factory()->withMember($user, TeamRole::Admin)->create();

    $this->seed(DemoSeeder::class);

    expect(Team::query()->find($team->id))->not->toBeNull()
        ->and(User::query()->find($user->id))->not->toBeNull();
});

it('uses a known password outside production, to help development', function () {
    $this->seed(DemoSeeder::class);

    expect(Hash::check('password', demoUser()->password))->toBeTrue();
});

it('uses a random password in production, so a public account cannot be taken over', function () {
    app()->detectEnvironment(fn (): string => 'production');

    $this->artisan('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);

    expect(Hash::check('password', demoUser()->password))->toBeFalse()
        ->and(demoUser()->email_verified_at)->not->toBeNull();
});

it('forbids changing a demo profile, because a new email would lock everyone out', function () {
    $this->seed(DemoSeeder::class);
    $user = demoUser();

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => 'Mine now', 'email' => 'mine@example.com'])
        ->assertForbidden();

    expect($user->fresh()?->email_verified_at)->not->toBeNull()
        ->and($user->fresh()?->name)->toBe('Demo User');
});

it('lets the same accounts change their profile while developing', function () {
    config(['demo.enabled' => false]);
    $this->seed(DemoSeeder::class);

    $this->actingAs(demoUser())
        ->patch(route('profile.update'), ['name' => 'Changed', 'email' => 'demo@taskflow.test'])
        ->assertRedirect();

    expect(demoUser()->name)->toBe('Changed');
});

it('tells the pages whether the demo and registration are available', function () {
    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page
        ->component('welcome')
        ->where('demoEnabled', true)
        ->where('canRegister', true));

    config(['demo.enabled' => false, 'fortify.features' => []]);

    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page
        ->where('demoEnabled', false)
        ->where('canRegister', false));
});
