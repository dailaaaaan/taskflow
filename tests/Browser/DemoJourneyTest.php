<?php

/*
|--------------------------------------------------------------------------
| Public demo (real browser)
|--------------------------------------------------------------------------
*/

use Database\Seeders\DemoSeeder;

beforeEach(function () {
    config(['demo.enabled' => true]);

    $this->seed(DemoSeeder::class);
});

it('takes a visitor from the landing page to a board in one click', function () {
    visit('/')
        ->assertSee('Try the live demo')
        ->click('@try-demo-button')
        ->assertPathBeginsWith('/boards/')
        ->assertSee('Product Roadmap')
        ->assertSee('Write the onboarding guide')
        ->assertNoJavaScriptErrors();
});

it('lets a second visitor join as the teammate', function () {
    visit('/')
        ->click('@join-as-sam-button')
        ->assertPathBeginsWith('/boards/')
        ->assertNoJavaScriptErrors();

    $this->assertAuthenticated();
});

it('has no accessibility issues on the landing page of the demo', function () {
    visit('/')->inLightMode()->assertNoAccessibilityIssues(3);
    visit('/')->inDarkMode()->assertNoAccessibilityIssues(3);
});

it('explains why a demo profile cannot be changed', function () {
    visit('/')
        ->click('@try-demo-button')
        ->assertPathBeginsWith('/boards/')
        ->navigate('/settings/profile')
        ->fill('name', 'Mine now')
        ->click('@update-profile-button')
        ->assertSee("You don't have permission to do that.")
        ->assertNoJavaScriptErrors();
});
