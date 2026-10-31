<?php

/*
|--------------------------------------------------------------------------
| Drag & drop journeys (real browser)
|--------------------------------------------------------------------------
|
| The keyboard drives dnd-kit precisely from a test, and it proves that the
| board can be reordered without a mouse. After every key the test waits for
| the screen reader announcement, so the announcements are verified too.
|
*/

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use Pest\Browser\Api\AwaitableWebpage;

/**
 * Presses each key on the drag handle, then waits for the announcement that
 * key should produce in dnd-kit's live region. Announcements replace each
 * other quickly, so every one of them is recorded in `window.announcements`.
 *
 * @param  list<array{string, string}>  $steps  [key, expected announcement]
 */
function dragWithKeyboard(AwaitableWebpage $page, string $handle, array $steps): void
{
    $page->script(<<<'JS'
        () => {
            window.announcements = [];
            new MutationObserver(() => {
                document.querySelectorAll('[id^=DndLiveRegion]').forEach((region) => {
                    window.announcements.push(region.textContent);
                });
            }).observe(document.body, { subtree: true, childList: true, characterData: true });
        }
        JS);

    foreach ($steps as [$key, $announcement]) {
        $page->keys($handle, $key);

        $text = json_encode($announcement, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $page->script(waitUntil("window.announcements.some((text) => text.includes({$text}))"));
        // Two frames let dnd-kit measure the new layout before the next key.
        $page->script('() => new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)))');
    }

    // Moves are optimistic: the page changes before the server has saved them.
    $page->script(waitUntil("performance.getEntriesByType('resource').some((entry) => entry.name.includes('/move') && entry.responseEnd > 0)"));
}

it('moves a card to another list with the keyboard', function () {
    [$board, , $member] = boardWithAdminAndMember();
    $todo = BoardList::factory()->for($board)->create(['title' => 'To do', 'position' => 1024]);
    $done = BoardList::factory()->for($board)->create(['title' => 'Done', 'position' => 2048]);
    Card::factory()->for($todo, 'boardList')->create(['title' => 'Write tests', 'position' => 1024]);
    Card::factory()->for($todo, 'boardList')->create(['title' => 'Ship it', 'position' => 2048]);

    $this->actingAs($member);

    $page = visit("/boards/{$board->id}")->assertSee('Write tests');

    dragWithKeyboard($page, '[aria-label="Move card Write tests"]', [
        ['Space', 'Picked up card “Write tests” in list “To do”.'],
        ['ArrowRight', 'over list “Done”'],
        ['Space', 'Dropped card “Write tests” in list “Done”'],
    ]);

    $page->assertNoJavaScriptErrors();

    expect($todo->cards()->pluck('title')->all())->toBe(['Ship it'])
        ->and($done->cards()->pluck('title')->all())->toBe(['Write tests']);
});

it('reorders cards within a list with the keyboard', function () {
    [$board, , $member] = boardWithAdminAndMember();
    $list = BoardList::factory()->for($board)->create(['title' => 'To do']);
    foreach (['First', 'Second', 'Third'] as $i => $title) {
        Card::factory()->for($list, 'boardList')->create(['title' => $title, 'position' => ($i + 1) * 1024]);
    }

    $this->actingAs($member);

    $page = visit("/boards/{$board->id}")->assertSee('Third');

    dragWithKeyboard($page, '[aria-label="Move card First"]', [
        ['Space', 'Picked up card “First”'],
        ['ArrowDown', 'over card “Second”'],
        ['ArrowDown', 'over card “Third”'],
        ['Space', 'Dropped card “First”'],
    ]);

    expect($list->cards()->pluck('title')->all())->toBe(['Second', 'Third', 'First']);
});

it('reorders lists with the keyboard', function () {
    [$board, , $member] = boardWithAdminAndMember();
    foreach (['To do', 'Doing', 'Done'] as $i => $title) {
        BoardList::factory()->for($board)->create(['title' => $title, 'position' => ($i + 1) * 1024]);
    }

    $this->actingAs($member);

    $page = visit("/boards/{$board->id}")->assertSee('Done');

    dragWithKeyboard($page, '[aria-label="Move list Done"]', [
        ['Space', 'Picked up list “Done”'],
        ['ArrowLeft', 'over list “Doing”'],
        ['ArrowLeft', 'over list “To do”'],
        ['Space', 'Dropped list “Done” on list “To do”'],
    ]);

    expect(Board::query()->sole()->lists()->pluck('title')->all())->toBe(['Done', 'To do', 'Doing']);
});

it('restores the board and explains why when the server rejects a move', function () {
    [$board, , $member] = boardWithAdminAndMember();
    $todo = BoardList::factory()->for($board)->create(['title' => 'To do', 'position' => 1024]);
    $done = BoardList::factory()->for($board)->create(['title' => 'Done', 'position' => 2048]);
    Card::factory()->for($todo, 'boardList')->create(['title' => 'Write tests']);

    $this->actingAs($member);

    $page = visit("/boards/{$board->id}")->assertSee('Write tests');

    // Removed from the team while the board is open: the move gets a 403.
    $board->team->memberships()->where('user_id', $member->id)->delete();

    dragWithKeyboard($page, '[aria-label="Move card Write tests"]', [
        ['Space', 'Picked up card “Write tests”'],
        ['ArrowRight', 'over list “Done”'],
        ['Space', 'Dropped card “Write tests”'],
    ]);

    $page->assertSee('The move could not be saved, so the board was restored.')
        ->assertSeeIn('[aria-label="To do"]', 'Write tests')
        ->assertDontSeeIn('[aria-label="Done"]', 'Write tests');

    expect($done->cards()->count())->toBe(0);
});

it('keeps the board accessible with drag & drop enabled', function () {
    [$board, , $member] = boardWithAdminAndMember();
    $list = BoardList::factory()->for($board)->create(['title' => 'To do']);
    Card::factory()->for($list, 'boardList')->create(['title' => 'Write tests']);

    $this->actingAs($member);

    visit("/boards/{$board->id}")
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues();
});
