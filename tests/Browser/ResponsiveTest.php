<?php

/*
|--------------------------------------------------------------------------
| Phone screens (real browser)
|--------------------------------------------------------------------------
|
| The only thing allowed to scroll sideways is the board itself, inside its
| own container. A page wider than the screen is the classic mobile bug.
|
*/

use App\Models\BoardList;
use App\Models\Card;
use Illuminate\Support\Carbon;

it('fits the screen of a phone', function (string $page) {
    [$board, $admin] = boardWithAdminAndMember();

    foreach (['Backlog', 'In progress', 'Done'] as $title) {
        $list = BoardList::factory()->for($board)->create(['title' => $title]);
        Card::factory()->for($list, 'boardList')->create([
            'title' => 'A long card title that needs to wrap over several lines',
            'assignee_id' => $admin->id,
            'due_date' => Carbon::tomorrow(),
        ]);
    }

    $this->actingAs($admin);

    $path = match ($page) {
        'landing' => '/',
        'dashboard' => '/dashboard',
        'team' => "/teams/{$board->team_id}",
        'team settings' => "/teams/{$board->team_id}/settings",
        'board' => "/boards/{$board->id}",
        'profile' => '/settings/profile',
    };

    $browser = visit($path)->on()->mobile();

    expect($browser->script('() => document.documentElement.scrollWidth <= window.innerWidth'))->toBeTrue();
})->with(['landing', 'dashboard', 'team', 'team settings', 'board', 'profile']);

it('keeps the sideways scroll of the board inside the board', function () {
    [$board, $admin] = boardWithAdminAndMember();
    BoardList::factory()->count(4)->for($board)->create();

    $this->actingAs($admin);

    $browser = visit("/boards/{$board->id}")->on()->mobile();

    expect($browser->script('() => {
        const lists = document.querySelector("[data-test=board-lists]");

        return lists.scrollWidth > lists.clientWidth;
    }'))->toBeTrue();
});
