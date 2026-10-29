<?php

namespace App\Models;

use App\Enums\BoardColor;
use Carbon\CarbonImmutable;
use Database\Factories\BoardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $id
 * @property int $team_id
 * @property string $title
 * @property BoardColor $color
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Team $team Never null: team_id is required and cascades on delete.
 * @property-read int|null $lists_count Present after withCount('lists').
 * @property-read int|null $cards_count Present after withCount('cards').
 */
#[Fillable(['title', 'color'])]
class Board extends Model
{
    /** @use HasFactory<BoardFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'color' => BoardColor::class,
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Lists in display order; `id` breaks ties deterministically.
     *
     * @return HasMany<BoardList, $this>
     */
    public function lists(): HasMany
    {
        return $this->hasMany(BoardList::class)
            ->orderBy('position')
            ->orderBy('id');
    }

    /**
     * Every card of the board, whatever its list. Also scopes `{card}` route
     * bindings, so a card of another board resolves to 404.
     *
     * @return HasManyThrough<Card, BoardList, $this>
     */
    public function cards(): HasManyThrough
    {
        return $this->hasManyThrough(Card::class, BoardList::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
