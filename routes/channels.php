<?php

use App\Models\Board;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
| One presence channel per board. Access follows the same policy as the board
| page, and the returned array is what the other viewers see of this user.
*/
Broadcast::channel('board.{board}', function (User $user, Board $board): array|false {
    if ($user->cannot('view', $board)) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->avatar,
    ];
});
