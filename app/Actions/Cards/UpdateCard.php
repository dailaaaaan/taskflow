<?php

namespace App\Actions\Cards;

use App\Events\CardUpdated;
use App\Models\Board;
use App\Models\Card;

final class UpdateCard
{
    /**
     * Save the details of a card and tell the other viewers of the board.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Board $board, Card $card, array $attributes): void
    {
        $card->update($attributes);

        broadcast(new CardUpdated($board->id, $card))->toOthers();
    }
}
