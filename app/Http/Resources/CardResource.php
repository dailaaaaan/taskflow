<?php

namespace App\Http\Resources;

use App\Models\Card;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Card
 */
final class CardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'position' => $this->position,
            'due_date' => $this->due_date?->toDateString(),
            // null when the card is unassigned.
            'assignee' => $this->whenLoaded('assignee', fn (User $assignee): array => [
                'id' => $assignee->id,
                'name' => $assignee->name,
                'avatar' => $assignee->avatar,
            ]),
        ];
    }
}
