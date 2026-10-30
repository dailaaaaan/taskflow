<?php

use App\Events\BoardEvent;
use App\Events\CardCreated;
use App\Events\CardDeleted;
use App\Events\CardMoved;
use App\Events\CardUpdated;
use App\Events\ListCreated;
use App\Events\ListDeleted;
use App\Events\ListMoved;
use App\Events\ListRenamed;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use Illuminate\Support\Facades\Event;

/**
 * @param  class-string<BoardEvent>  $event
 * @param  array<string, mixed>  $payload
 */
function assertBroadcastToOthers(string $event, Board $board, array $payload): void
{
    Event::assertDispatched($event, function (BoardEvent $event) use ($board, $payload): bool {
        expect($event->broadcastOn()->name)->toBe("presence-board.{$board->id}")
            ->and($event->broadcastAs())->toBe('board.changed')
            ->and($event->broadcastWith())->toMatchArray($payload)
            // toOthers(): the socket that made the change gets no echo of it.
            ->and($event->socket)->toBe('123.456');

        return true;
    });
}

beforeEach(function () {
    Event::fake([
        CardCreated::class, CardUpdated::class, CardMoved::class, CardDeleted::class,
        ListCreated::class, ListRenamed::class, ListMoved::class, ListDeleted::class,
    ]);

    [$this->board, $this->admin, $this->member] = boardWithAdminAndMember();
    $this->list = BoardList::factory()->for($this->board)->create(['title' => 'To do', 'position' => 1024]);
    $this->card = Card::factory()->for($this->list, 'boardList')->create(['title' => 'Write tests', 'position' => 1024]);

    $this->actingAs($this->member)->withHeader('X-Socket-ID', '123.456');
});

it('broadcasts a new card', function () {
    $this->post(route('boards.lists.cards.store', [$this->board, $this->list]), ['title' => 'Ship it']);

    assertBroadcastToOthers(CardCreated::class, $this->board, [
        'type' => 'card/created',
        'listId' => $this->list->id,
        'card' => ['id' => Card::query()->latest('id')->value('id'), 'title' => 'Ship it', 'description' => null, 'position' => 2048, 'due_date' => null, 'assignee' => null],
    ]);
});

it('broadcasts the details of an updated card, with its assignee', function () {
    $assignee = $this->admin->fresh();

    $this->patch(route('boards.cards.update', [$this->board, $this->card]), [
        'title' => 'Write more tests',
        'description' => 'All of them.',
        'assignee_id' => $assignee->id,
        'due_date' => '2026-11-15',
    ]);

    assertBroadcastToOthers(CardUpdated::class, $this->board, [
        'type' => 'card/updated',
        'card' => [
            'id' => $this->card->id,
            'title' => 'Write more tests',
            'description' => 'All of them.',
            'position' => 1024,
            'due_date' => '2026-11-15',
            'assignee' => ['id' => $assignee->id, 'name' => $assignee->name, 'avatar' => null],
        ],
    ]);
});

it('broadcasts a moved card', function () {
    $done = BoardList::factory()->for($this->board)->create();

    $this->patch(route('boards.cards.move', [$this->board, $this->card]), ['list_id' => $done->id, 'index' => 0]);

    assertBroadcastToOthers(CardMoved::class, $this->board, [
        'type' => 'card/moved',
        'cardId' => $this->card->id,
        'toListId' => $done->id,
        'toIndex' => 0,
    ]);
});

it('broadcasts a deleted card', function () {
    $this->delete(route('boards.cards.destroy', [$this->board, $this->card]));

    assertBroadcastToOthers(CardDeleted::class, $this->board, ['type' => 'card/deleted', 'cardId' => $this->card->id]);
});

it('broadcasts a new list', function () {
    $this->post(route('boards.lists.store', $this->board), ['title' => 'Done']);

    assertBroadcastToOthers(ListCreated::class, $this->board, [
        'type' => 'list/created',
        'list' => ['id' => BoardList::query()->latest('id')->value('id'), 'title' => 'Done', 'position' => 2048],
    ]);
});

it('broadcasts a renamed list', function () {
    $this->patch(route('boards.lists.update', [$this->board, $this->list]), ['title' => 'Backlog']);

    assertBroadcastToOthers(ListRenamed::class, $this->board, ['type' => 'list/renamed', 'listId' => $this->list->id, 'title' => 'Backlog']);
});

it('broadcasts a moved list', function () {
    BoardList::factory()->for($this->board)->create(['position' => 2048]);

    $this->patch(route('boards.lists.move', [$this->board, $this->list]), ['index' => 1]);

    assertBroadcastToOthers(ListMoved::class, $this->board, ['type' => 'list/moved', 'listId' => $this->list->id, 'toIndex' => 1]);
});

it('broadcasts a deleted list', function () {
    $this->delete(route('boards.lists.destroy', [$this->board, $this->list]));

    assertBroadcastToOthers(ListDeleted::class, $this->board, ['type' => 'list/deleted', 'listId' => $this->list->id]);
});

it('tells clients to reload instead of exceeding the Pusher payload limit', function () {
    $this->card->update(['description' => str_repeat('a', 9500)]);

    $event = new CardUpdated($this->board->id, $this->card);

    expect($event->broadcastWith())->toBe(['type' => 'board/stale']);
});

it('counts escaped characters towards the payload limit', function () {
    // 3 000 characters, but json_encode writes each one as a 6-byte \uXXXX.
    $this->card->update(['description' => str_repeat('ñ', 3000)]);

    $event = new CardUpdated($this->board->id, $this->card);

    expect($event->broadcastWith())->toBe(['type' => 'board/stale']);
});

it('does not broadcast rejected changes', function () {
    $this->actingAs(User::factory()->create())
        ->patch(route('boards.cards.update', [$this->board, $this->card]), ['title' => 'Mine'])
        ->assertForbidden();

    Event::assertNotDispatched(CardUpdated::class);
});
