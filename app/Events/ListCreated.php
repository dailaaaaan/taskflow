<?php

namespace App\Events;

use App\Http\Resources\BoardListResource;
use App\Models\BoardList;

final class ListCreated extends BoardEvent
{
    public function __construct(private readonly BoardList $list)
    {
        parent::__construct($list->board_id);
    }

    protected function action(): array
    {
        return [
            'type' => 'list/created',
            'list' => BoardListResource::make($this->list)->resolve(),
        ];
    }
}
