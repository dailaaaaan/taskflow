<?php

namespace App\Actions\Cards;

use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use App\Support\Position;

final class CreateCard
{
    /**
     * Append a card at the bottom of $list, remembering who created it.
     */
    public function handle(BoardList $list, User $creator, string $title): Card
    {
        $last = $list->cards()->max('position');

        $card = $list->cards()->make([
            'title' => $title,
            'position' => Position::after(is_numeric($last) ? (int) $last : null),
        ]);

        $card->creator()->associate($creator);
        $card->save();

        return $card;
    }
}
