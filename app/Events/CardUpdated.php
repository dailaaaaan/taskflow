<?php

namespace App\Events;

use App\Http\Resources\CardResource;
use App\Models\Card;

final class CardUpdated extends BoardEvent
{
    public function __construct(int $boardId, private readonly Card $card)
    {
        parent::__construct($boardId);
    }

    protected function action(): array
    {
        return [
            'type' => 'card/updated',
            'card' => CardResource::make($this->card->load('assignee'))->resolve(),
        ];
    }
}
