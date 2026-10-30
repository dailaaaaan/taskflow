<?php

use App\Models\BoardList;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Facades\Broadcast;

/*
| Events are not faked here: a broadcaster that always fails stands in for a
| Pusher outage, and the request must still succeed.
*/
beforeEach(function () {
    Broadcast::extend('failing', fn (): Broadcaster => new class extends Broadcaster
    {
        public function auth($request): mixed
        {
            return null;
        }

        public function validAuthenticationResponse($request, $result): mixed
        {
            return null;
        }

        public function broadcast(array $channels, $event, array $payload = []): void
        {
            throw new BroadcastException('Pusher is down.');
        }
    });

    config([
        'broadcasting.default' => 'failing',
        'broadcasting.connections.failing' => ['driver' => 'failing'],
    ]);
});

it('keeps the change when the broadcast fails', function () {
    [$board, , $member] = boardWithAdminAndMember();
    $list = BoardList::factory()->for($board)->create(['title' => 'To do']);

    $this->actingAs($member)
        ->patch(route('boards.lists.update', [$board, $list]), ['title' => 'Saved'])
        ->assertRedirect();

    expect($list->fresh()?->title)->toBe('Saved');
});
