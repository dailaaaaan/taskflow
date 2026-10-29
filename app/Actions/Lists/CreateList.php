<?php

namespace App\Actions\Lists;

use App\Models\Board;
use App\Models\BoardList;
use App\Support\Position;

final class CreateList
{
    /**
     * Append a list at the end of the board.
     */
    public function handle(Board $board, string $title): BoardList
    {
        $last = $board->lists()->max('position');

        return $board->lists()->create([
            'title' => $title,
            'position' => Position::after(is_numeric($last) ? (int) $last : null),
        ]);
    }
}
