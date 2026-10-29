<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Teams\RemoveTeamMember;
use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class LeaveTeamController extends Controller
{
    public function __invoke(Team $team, #[CurrentUser] User $user, RemoveTeamMember $removeTeamMember): RedirectResponse
    {
        $removeTeamMember->handle($team->memberships()->where('user_id', $user->id)->firstOrFail());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('You left :team.', ['team' => $team->name])]);

        return to_route('dashboard');
    }
}
