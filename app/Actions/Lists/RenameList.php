<?php

namespace App\Actions\Lists;

use App\Events\ListRenamed;
use App\Models\BoardList;

final class RenameList
{
    /**
     * Rename a list and tell the other viewers of the board.
     */
    public function handle(BoardList $list, string $title): void
    {
        $list->update(['title' => $title]);

        broadcast(new ListRenamed($list))->toOthers();
    }
}
