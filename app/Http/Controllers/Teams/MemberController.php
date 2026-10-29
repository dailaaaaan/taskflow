<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Teams\ChangeMemberRole;
use App\Actions\Teams\RemoveTeamMember;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\TeamRoleRequest;
use App\Models\Membership;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Admin-only member management. `$team` scopes the membership binding, so a
 * membership of another team resolves to 404.
 */
final class MemberController extends Controller
{
    public function update(TeamRoleRequest $request, Team $team, Membership $membership, ChangeMemberRole $changeMemberRole): RedirectResponse
    {
        $changeMemberRole->handle($membership, $request->role());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role updated.')]);

        return back();
    }

    public function destroy(Team $team, Membership $membership, RemoveTeamMember $removeTeamMember): RedirectResponse
    {
        $removeTeamMember->handle($membership);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member removed.')]);

        return back();
    }
}
