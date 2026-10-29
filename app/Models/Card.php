<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $board_list_id
 * @property string $title
 * @property string|null $description
 * @property int $position
 * @property int|null $assignee_id
 * @property CarbonImmutable|null $due_date
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['title', 'description', 'position', 'assignee_id', 'due_date'])]
class Card extends Model
{
    /** @use HasFactory<CardFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<BoardList, $this>
     */
    public function boardList(): BelongsTo
    {
        return $this->belongsTo(BoardList::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
