<?php

/*
|--------------------------------------------------------------------------
| Authentication journey (real browser)
|--------------------------------------------------------------------------
|
| Runs Chromium through Playwright against the real app: JavaScript,
| Inertia navigation and the built assets are exercised end to end.
|
*/

use App\Models\User;

it('registers a new account and asks to verify the email', function () {
    visit('/register')
        ->fill('name', 'Ada Lovelace')
        ->fill('email', 'ada@example.com')
        ->fill('password', 'correct-horse-battery-staple')
        ->fill('password_confirmation', 'correct-horse-battery-staple')
        ->press('Create account')
        ->assertPathIs('/email/verify')
        ->assertNoJavaScriptErrors();

    expect(User::query()->where('email', 'ada@example.com')->exists())->toBeTrue();
});

it('logs in, reaches the dashboard and logs out', function () {
    User::factory()->create(['name' => 'Grace Hopper', 'email' => 'grace@example.com']);

    visit('/login')
        ->fill('email', 'grace@example.com')
        ->fill('password', 'password')
        ->press('Log in')
        ->assertPathIs('/dashboard')
        ->assertSee('Grace Hopper')
        ->click('@sidebar-menu-button')
        ->click('@logout-button')
        ->assertPathIs('/')
        ->navigate('/dashboard')
        ->assertPathIs('/login')
        ->assertNoJavaScriptErrors();
});

it('rejects invalid credentials without leaving the login page', function () {
    User::factory()->create(['email' => 'grace@example.com']);

    visit('/login')
        ->fill('email', 'grace@example.com')
        ->fill('password', 'wrong-password')
        ->press('Log in')
        ->assertPathIs('/login')
        ->assertSee('These credentials do not match our records.');
});

it('renders the auth pages without JavaScript errors or accessibility issues', function (string $path) {
    visit($path)
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues();
})->with(['/login', '/register', '/forgot-password']);
