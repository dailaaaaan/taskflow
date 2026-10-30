<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;

/**
 * A change on a board, sent to everyone else viewing it. The payload is a
 * `BoardAction` of resources/js/lib/board-reducer.ts, so the browser applies
 * remote changes with the same reducer as its own moves.
 *
 * Broadcast synchronously because free hosting has no queue worker
 * (docs/adr/0002-pusher-for-realtime.md). ShouldRescue reports a failed
 * broadcast without failing the request: the change is already saved.
 */
abstract class BoardEvent implements ShouldBroadcastNow, ShouldRescue
{
    use InteractsWithSockets;

    /**
     * Pusher rejects events over 10 KB, which only a long card description
     * can reach. Those clients reload the lists instead.
     */
    public const int MAX_PAYLOAD_BYTES = 9000;

    public function __construct(public readonly int $boardId) {}

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel("board.{$this->boardId}");
    }

    public function broadcastAs(): string
    {
        return 'board.changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $action = $this->action();

        return strlen(json_encode($action, JSON_THROW_ON_ERROR)) > self::MAX_PAYLOAD_BYTES
            ? ['type' => 'board/stale']
            : $action;
    }

    /**
     * @return array<string, mixed>
     */
    abstract protected function action(): array;
}
