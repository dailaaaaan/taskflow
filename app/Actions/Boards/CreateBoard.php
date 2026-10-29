<?php

namespace App\Actions\Boards;

use App\Enums\BoardColor;
use App\Models\Board;
use App\Models\Team;
use App\Models\User;

final class CreateBoard
{
    /**
     * Create an empty board in $team, remembering who created it.
     */
    public function handle(Team $team, User $creator, string $title, BoardColor $color): Board
    {
        $board = $team->boards()->make(['title' => $title, 'color' => $color]);

        $board->creator()->associate($creator);
        $board->save();

        return $board;
    }
}
