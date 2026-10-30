<?php

namespace App\Actions\Lists;

use App\Events\ListDeleted;
use App\Models\BoardList;

final class DeleteList
{
    /**
     * Delete a list with its cards and tell the other viewers of the board.
     */
    public function handle(BoardList $list): void
    {
        $list->delete();

        broadcast(new ListDeleted($list->board_id, $list->id))->toOthers();
    }
}
