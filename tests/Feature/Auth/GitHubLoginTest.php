<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;

beforeEach(function () {
    config([
        'services.github.enabled' => true,
        'services.github.client_id' => 'test-client-id',
        'services.github.client_secret' => 'test-client-secret',
        'services.github.redirect' => '/auth/github/callback',
    ]);
});

/**
 * Make Socialite return this GitHub identity on the callback.
 *
 * @param  array<string, mixed>  $overrides
 */
function fakeGitHubUser(array $overrides = []): void
{
    Socialite::fake('github', (new SocialiteUser)->map([
        'id' => 4242,
        'nickname' => 'octocat',
        'name' => 'The Octocat',
        'email' => 'octocat@example.com',
        'avatar' => 'https://avatars.githubusercontent.com/u/4242',
        ...$overrides,
    ]));
}

it('redirects guests to GitHub to authorize the app', function () {
    $this->get(route('auth.github.redirect'))
        ->assertRedirectContains('https://github.com/login/oauth/authorize')
        ->assertRedirectContains('client_id=test-client-id');
});

it('offers GitHub login on the login and register pages when configured', function (string $page) {
    $this->get(route($page))
        ->assertInertia(fn (Assert $inertia) => $inertia->where('canLoginWithGitHub', true));
})->with(['login', 'register']);

it('disables GitHub login when it is not configured', function () {
    config(['services.github.enabled' => false]);

    $this->get(route('auth.github.redirect'))->assertNotFound();
    $this->get(route('auth.github.callback'))->assertNotFound();
    $this->get(route('login'))
        ->assertInertia(fn (Assert $inertia) => $inertia->where('canLoginWithGitHub', false));
});

it('creates a verified account for a new GitHub user and signs them in', function () {
    fakeGitHubUser();

    $this->get(route('auth.github.callback'))->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->sole();
    $this->assertAuthenticatedAs($user);
    expect($user)
        ->name->toBe('The Octocat')
        ->email->toBe('octocat@example.com')
        ->github_id->toBe('4242')
        ->avatar->toBe('https://avatars.githubusercontent.com/u/4242')
        ->password->toBeNull()
        ->email_verified_at->not->toBeNull();
});

it('signs in a returning GitHub user by id even after an email change', function () {
    $user = User::factory()->create(['github_id' => '4242', 'email' => 'old@example.com']);
    fakeGitHubUser(['email' => 'new@example.com']);

    $this->get(route('auth.github.callback'));

    $this->assertAuthenticatedAs($user);
    expect(User::query()->count())->toBe(1);
});

it('links an existing verified account with the same email', function () {
    $user = User::factory()->create(['email' => 'OctoCat@Example.com']);
    fakeGitHubUser();

    $this->get(route('auth.github.callback'));

    $this->assertAuthenticatedAs($user);
    $user->refresh();
    expect($user->github_id)->toBe('4242')
        ->and(Hash::check('password', (string) $user->password))->toBeTrue();
});

it('discards the password of an unverified account it takes over', function () {
    $squatter = User::factory()->unverified()->create(['email' => 'octocat@example.com']);
    fakeGitHubUser();

    $this->get(route('auth.github.callback'));

    $this->assertAuthenticatedAs($squatter);
    expect($squatter->refresh())
        ->password->toBeNull()
        ->email_verified_at->not->toBeNull();
});

it('requires the second factor when the user has two-factor authentication enabled', function () {
    $user = User::factory()->withTwoFactor()->create(['github_id' => '4242']);
    fakeGitHubUser();

    $this->get(route('auth.github.callback'))
        ->assertRedirect(route('two-factor.login'))
        ->assertSessionHas('login.id', $user->id);

    $this->assertGuest();
});

it('rejects GitHub accounts without a verified email', function () {
    fakeGitHubUser(['email' => null]);

    $this->get(route('auth.github.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    expect(User::query()->count())->toBe(0);
});

it('handles an authorization cancelled on GitHub', function () {
    $this->get(route('auth.github.callback', ['error' => 'access_denied']))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('handles an expired OAuth state', function () {
    Socialite::fake('github', fn () => throw new InvalidStateException);

    $this->get(route('auth.github.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('is only available to guests', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('auth.github.redirect'))
        ->assertRedirect(route('dashboard', absolute: false));
});
