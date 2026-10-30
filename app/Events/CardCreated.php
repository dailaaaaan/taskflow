<?php

namespace App\Events;

use App\Http\Resources\CardResource;
use App\Models\BoardList;
use App\Models\Card;

final class CardCreated extends BoardEvent
{
    public function __construct(private readonly BoardList $list, private readonly Card $card)
    {
        parent::__construct($list->board_id);
    }

    protected function action(): array
    {
        return [
            'type' => 'card/created',
            'listId' => $this->list->id,
            'card' => CardResource::make($this->card->loadMissing('assignee'))->resolve(),
        ];
    }
}
