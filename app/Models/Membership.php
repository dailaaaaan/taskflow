<?php

namespace App\Models;

use App\Enums\TeamRole;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * A user's membership in a team, carrying their role.
 *
 * @property int $id
 * @property int $team_id
 * @property int $user_id
 * @property TeamRole $role
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Team $team Never null: team_id is required and cascades on delete.
 * @property-read User $user Never null: user_id is required and cascades on delete.
 */
#[Table(name: 'memberships', incrementing: true)]
#[Fillable(['team_id', 'user_id', 'role'])]
class Membership extends Pivot
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => TeamRole::class,
        ];
    }

    /**
     * Admins first, then members, each in joining order.
     *
     * @param  Builder<Membership>  $query
     */
    public function scopeAdminsFirst(Builder $query): void
    {
        $query->orderByRaw('role = ? desc', [TeamRole::Admin->value])->oldest()->orderBy('id');
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
