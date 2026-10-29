<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\BoardListFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A column of a board. Named BoardList because `list` is reserved in PHP.
 *
 * @property int $id
 * @property int $board_id
 * @property string $title
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Table('board_lists')]
#[Fillable(['title', 'position'])]
class BoardList extends Model
{
    /** @use HasFactory<BoardListFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Board, $this>
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * Cards in display order; `id` breaks ties deterministically.
     *
     * @return HasMany<Card, $this>
     */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class)
            ->orderBy('position')
            ->orderBy('id');
    }
}
