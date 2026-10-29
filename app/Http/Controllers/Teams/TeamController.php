<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Teams\CreateTeam;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\SaveTeamRequest;
use App\Http\Resources\BoardResource;
use App\Http\Resources\InvitationResource;
use App\Http\Resources\MemberResource;
use App\Http\Resources\TeamResource;
use App\Models\Board;
use App\Models\Team;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Authorization is declared on the routes (->can()), so it runs before
 * request validation and these methods only coordinate.
 */
final class TeamController extends Controller
{
    public function store(SaveTeamRequest $request, CreateTeam $createTeam, #[CurrentUser] User $user): RedirectResponse
    {
        $team = $createTeam->handle($user, $request->string('name')->toString());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team created.')]);

        return to_route('teams.show', $team);
    }

    public function show(Team $team, #[CurrentUser] User $user): Response
    {
        return Inertia::render('teams/show', [
            'team' => TeamResource::make($team),
            'members' => MemberResource::collection($team->memberships()->with('user')->adminsFirst()->get()),
            'boards' => BoardResource::collection($team->boards()->withCount(['lists', 'cards'])->orderBy('title')->get()),
            'can' => $this->abilities($team, $user),
        ]);
    }

    public function edit(Team $team, #[CurrentUser] User $user): Response
    {
        $abilities = $this->abilities($team, $user);

        $invitations = $abilities['invite']
            ? $team->invitations()->with('creator')->where('expires_at', '>', now())->latest()->get()
            : collect();

        return Inertia::render('teams/settings', [
            'team' => TeamResource::make($team),
            'members' => MemberResource::collection($team->memberships()->with('user')->adminsFirst()->get()),
            'invitations' => InvitationResource::collection($invitations),
            'can' => $abilities,
        ]);
    }

    public function update(SaveTeamRequest $request, Team $team): RedirectResponse
    {
        $team->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team renamed.')]);

        return back();
    }

    public function destroy(Team $team): RedirectResponse
    {
        $team->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team deleted.')]);

        return to_route('dashboard');
    }

    /**
     * What the current user may do, so the UI can hide unavailable controls.
     * The server still enforces every ability on its own route.
     *
     * @return array<string, bool>
     */
    private function abilities(Team $team, User $user): array
    {
        return [
            'update' => $user->can('update', $team),
            'delete' => $user->can('delete', $team),
            'invite' => $user->can('invite', $team),
            'manageMembers' => $user->can('manageMembers', $team),
            'leave' => $user->can('leave', $team),
            'createBoard' => $user->can('create', [Board::class, $team]),
        ];
    }
}
