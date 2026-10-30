<?php

namespace App\Events;

final class ListMoved extends BoardEvent
{
    public function __construct(
        int $boardId,
        private readonly int $listId,
        private readonly int $toIndex,
    ) {
        parent::__construct($boardId);
    }

    protected function action(): array
    {
        return [
            'type' => 'list/moved',
            'listId' => $this->listId,
            'toIndex' => $this->toIndex,
        ];
    }
}
