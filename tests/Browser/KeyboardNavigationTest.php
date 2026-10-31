<?php

/*
|--------------------------------------------------------------------------
| Keyboard and screen reader journeys (real browser)
|--------------------------------------------------------------------------
|
| Closing something removes the focused element, and the browser would then
| send focus back to the top of the page. These journeys prove it returns to
| where the person was.
|
*/

use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use Pest\Browser\Api\AwaitableWebpage;

/** Waits for the focus to land, then describes the focused element. */
function focused(AwaitableWebpage $page): string
{
    // Radix returns the focus a moment after the modal is gone.
    $page->script(waitUntil('document.activeElement !== document.body'));

    return $page->script('() => document.activeElement.getAttribute("aria-label") ?? document.activeElement.textContent.trim()');
}

beforeEach(function () {
    [$this->board, $this->admin] = boardWithAdminAndMember();
    $this->list = BoardList::factory()->for($this->board)->create(['title' => 'To do']);
    // Empty details, because they are part of what a screen reader says of a card.
    $this->card = Card::factory()->for($this->list, 'boardList')->create([
        'title' => 'Write tests',
        'description' => null,
        'due_date' => null,
    ]);

    $this->actingAs($this->admin);
});

it('lets keyboard users skip the navigation', function () {
    $page = visit("/boards/{$this->board->id}")->keys('#app', 'Tab');

    expect(focused($page))->toBe('Skip to main content');

    $page->keys('@skip-link', 'Enter');
    $page->script(waitUntil('document.activeElement.id === "main-content"'));

    expect($page->url())->toEndWith('#main-content');
});

it('returns the focus to the card after closing its details', function () {
    $page = visit("/boards/{$this->board->id}")
        ->click('@card-item')
        ->assertSee('Card details');

    $page->keys('[name=title]', 'Escape');
    $page->assertDontSee('Card details');

    expect(focused($page))->toBe('Write tests');
});

it('returns the focus after closing the forms inside a list', function (string $open, string $input, string $button) {
    $page = visit("/boards/{$this->board->id}")->click($open);

    $page->keys($input, 'Escape');

    expect(focused($page))->toBe($button);
})->with([
    'add a card' => ['@add-card-button', '@new-card-title', 'Add a card'],
    'add a list' => ['@add-list-button', '@new-list-title', 'Add a list'],
    'rename a list' => ['[aria-label="Rename list To do"]', '[aria-label="List title"]', 'Rename list To do'],
]);

it('announces each new page to screen readers', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/settings/profile')->click('Appearance')->assertPathIs('/settings/appearance');

    // Fails on a timeout if the title never reaches the live region.
    $page->script(waitUntil('document.querySelector("[data-test=route-announcer]").textContent.includes("Appearance")'));

    $page->assertPathIs('/settings/appearance');
});
