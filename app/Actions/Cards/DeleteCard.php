<?php

namespace App\Actions\Cards;

use App\Events\CardDeleted;
use App\Models\Board;
use App\Models\Card;

final class DeleteCard
{
    /**
     * Delete a card and tell the other viewers of the board.
     */
    public function handle(Board $board, Card $card): void
    {
        $card->delete();

        broadcast(new CardDeleted($board->id, $card->id))->toOthers();
    }
}
