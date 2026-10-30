<?php

namespace App\Actions\Cards;

use App\Models\BoardList;
use App\Models\Card;
use App\Support\Position;
use Illuminate\Support\Facades\DB;

final class MoveCard
{
    /**
     * Move $card to $index of $list, counted without the card itself, within
     * the same list or across lists of the same board.
     */
    public function handle(Card $card, BoardList $list, int $index): void
    {
        DB::transaction(function () use ($card, $list, $index): void {
            // Lock the destination's cards so two concurrent moves into the
            // same list cannot compute a position from the same snapshot.
            $siblings = $list->cards()->whereKeyNot($card->id)->lockForUpdate()->get();

            $placement = Position::insert($siblings->map(fn (Card $sibling): int => $sibling->position)->all(), $index);

            if ($placement['renumbered'] !== null) {
                foreach ($siblings->values() as $i => $sibling) {
                    $sibling->update(['position' => $placement['renumbered'][$i]]);
                }
            }

            $card->boardList()->associate($list);
            $card->position = $placement['position'];
            $card->save();
        });
    }
}
