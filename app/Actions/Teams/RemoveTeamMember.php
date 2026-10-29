<?php

namespace App\Actions\Teams;

use App\Models\Card;
use App\Models\Membership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Removes a member from a team. Used both when an admin removes someone and
 * when a member leaves, so the "at least one admin" rule applies to both.
 */
final class RemoveTeamMember
{
    public function __construct(private readonly EnsureTeamKeepsAnAdmin $ensureTeamKeepsAnAdmin) {}

    public function handle(Membership $membership): void
    {
        DB::transaction(function () use ($membership): void {
            $this->ensureTeamKeepsAnAdmin->handle($membership);

            // Only teammates can be assigned, so their cards in this team go back to unassigned.
            Card::query()
                ->where('assignee_id', $membership->user_id)
                ->whereHas('boardList.board', fn (Builder $board) => $board->where('team_id', $membership->team_id))
                ->update(['assignee_id' => null]);

            $membership->delete();
        });
    }
}
