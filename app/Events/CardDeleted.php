<?php

namespace App\Events;

final class CardDeleted extends BoardEvent
{
    public function __construct(int $boardId, private readonly int $cardId)
    {
        parent::__construct($boardId);
    }

    protected function action(): array
    {
        return [
            'type' => 'card/deleted',
            'cardId' => $this->cardId,
        ];
    }
}
