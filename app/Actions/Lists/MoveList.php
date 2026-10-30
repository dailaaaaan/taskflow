<?php

namespace App\Actions\Lists;

use App\Events\ListMoved;
use App\Models\BoardList;
use App\Support\Position;
use Illuminate\Support\Facades\DB;

final class MoveList
{
    /**
     * Move $list to $index of its board, counted without the list itself.
     */
    public function handle(BoardList $list, int $index): void
    {
        DB::transaction(function () use ($list, $index): void {
            // Lock the other lists so concurrent moves on the same board
            // cannot compute a position from the same snapshot.
            $siblings = $list->board->lists()->whereKeyNot($list->id)->lockForUpdate()->get();

            $placement = Position::insert($siblings->map(fn (BoardList $sibling): int => $sibling->position)->all(), $index);

            if ($placement['renumbered'] !== null) {
                foreach ($siblings->values() as $i => $sibling) {
                    $sibling->update(['position' => $placement['renumbered'][$i]]);
                }
            }

            $list->update(['position' => $placement['position']]);
        });

        broadcast(new ListMoved($list->board_id, $list->id, $index))->toOthers();
    }
}
