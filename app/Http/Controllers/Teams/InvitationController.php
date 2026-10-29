<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Teams\CreateInvitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\TeamRoleRequest;
use App\Models\Invitation;
use App\Models\Team;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Admin-only management of a team's invite links.
 */
final class InvitationController extends Controller
{
    public function store(TeamRoleRequest $request, Team $team, CreateInvitation $createInvitation, #[CurrentUser] User $user): RedirectResponse
    {
        $createInvitation->handle($team, $user, $request->role());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invite link created.')]);

        return back();
    }

    public function destroy(Team $team, Invitation $invitation): RedirectResponse
    {
        $invitation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invite link revoked.')]);

        return back();
    }
}
