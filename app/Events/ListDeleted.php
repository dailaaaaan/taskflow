<?php

namespace App\Events;

final class ListDeleted extends BoardEvent
{
    public function __construct(int $boardId, private readonly int $listId)
    {
        parent::__construct($boardId);
    }

    protected function action(): array
    {
        return [
            'type' => 'list/deleted',
            'listId' => $this->listId,
        ];
    }
}
