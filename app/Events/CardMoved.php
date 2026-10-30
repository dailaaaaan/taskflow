<?php

namespace App\Events;

final class CardMoved extends BoardEvent
{
    public function __construct(
        int $boardId,
        private readonly int $cardId,
        private readonly int $toListId,
        private readonly int $toIndex,
    ) {
        parent::__construct($boardId);
    }

    protected function action(): array
    {
        return [
            'type' => 'card/moved',
            'cardId' => $this->cardId,
            'toListId' => $this->toListId,
            'toIndex' => $this->toIndex,
        ];
    }
}
