<?php

/*
|--------------------------------------------------------------------------
| Accessibility audit (real browser)
|--------------------------------------------------------------------------
|
| axe checks every page in both colour schemes, because contrast problems
| usually appear in only one of them. Level 3 fails on any impact, minor
| included.
|
*/

use App\Models\BoardList;
use App\Models\Card;
use Illuminate\Support\Carbon;

function audit(string $path, string $scheme): void
{
    $page = visit($path);

    ($scheme === 'dark' ? $page->inDarkMode() : $page->inLightMode())
        ->assertNoAccessibilityIssues(3);
}

dataset('color schemes', ['light', 'dark']);

it('has no accessibility issues on the public pages', function (string $path, string $scheme) {
    audit($path, $scheme);
})->with([
    'landing' => '/',
    'login' => '/login',
    'register' => '/register',
    'forgot password' => '/forgot-password',
    'not found' => '/this-page-does-not-exist',
])->with('color schemes');

it('has no accessibility issues on the pages of a member', function (string $page, string $scheme) {
    [$board, $admin] = boardWithAdminAndMember();
    $list = BoardList::factory()->for($board)->create(['title' => 'In progress']);
    Card::factory()->for($list, 'boardList')->create([
        'title' => 'Overdue and assigned',
        'description' => 'Details.',
        'assignee_id' => $admin->id,
        'due_date' => Carbon::yesterday(),
    ]);
    BoardList::factory()->for($board)->create(['title' => 'Empty']);

    $this->actingAs($admin);

    audit(match ($page) {
        'dashboard' => '/dashboard',
        'team' => "/teams/{$board->team_id}",
        'team settings' => "/teams/{$board->team_id}/settings",
        'board' => "/boards/{$board->id}",
        'profile' => '/settings/profile',
        'security' => '/settings/security',
        'appearance' => '/settings/appearance',
    }, $scheme);
})->with([
    'dashboard',
    'team',
    'team settings',
    'board',
    'profile',
    'security',
    'appearance',
])->with('color schemes');
