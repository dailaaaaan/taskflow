<?php

namespace App\Events;

use App\Models\BoardList;

final class ListRenamed extends BoardEvent
{
    public function __construct(private readonly BoardList $list)
    {
        parent::__construct($list->board_id);
    }

    protected function action(): array
    {
        return [
            'type' => 'list/renamed',
            'listId' => $this->list->id,
            'title' => $this->list->title,
        ];
    }
}
