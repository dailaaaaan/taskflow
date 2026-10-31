<?php

use App\Enums\TeamRole;
use App\Models\Board;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * JavaScript that resolves once the condition is true in the page. Keys are
 * pressed much faster than React renders, so each step waits for its result.
 */
function waitUntil(string $condition): string
{
    return "() => new Promise((resolve) => {
        const check = () => ({$condition}) ? resolve(true) : setTimeout(check, 25);
        check();
    })";
}

/**
 * A board whose team has one admin and one member.
 *
 * @return array{Board, User, User}
 */
function boardWithAdminAndMember(): array
{
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->withMember($admin, TeamRole::Admin)->withMember($member)->create();

    return [Board::factory()->for($team)->create(), $admin, $member];
}
