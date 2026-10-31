<?php

/*
|--------------------------------------------------------------------------
| Failure journeys (real browser)
|--------------------------------------------------------------------------
*/

use App\Models\BoardList;
use App\Models\User;

it('shows the error page of a missing board and leads back to the teams', function () {
    $this->actingAs(User::factory()->create());

    visit('/boards/999999')
        ->assertSee('Page not found')
        ->click('@error-page-link')
        ->assertPathIs('/dashboard')
        ->assertNoJavaScriptErrors();
});

it('explains a clash with another user in a toast and stays on the board', function () {
    [$board, $admin] = boardWithAdminAndMember();
    $list = BoardList::factory()->for($board)->create(['title' => 'Already gone']);

    $this->actingAs($admin);

    $page = visit("/boards/{$board->id}")->assertSee('Already gone');

    $list->delete();

    $page->click('[aria-label="Delete list Already gone"]')
        ->click('@confirm-button')
        ->assertSee('That no longer exists')
        ->assertSee($board->title)
        ->assertNoJavaScriptErrors();
});
