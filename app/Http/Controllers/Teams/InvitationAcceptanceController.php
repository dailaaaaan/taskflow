<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Teams\AcceptInvitation;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvitationPreviewResource;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Reached through a signed invite link (the `signed` middleware rejects
 * tampered or expired links with 403; revoked links resolve to 404).
 */
final class InvitationAcceptanceController extends Controller
{
    public function show(Invitation $invitation, #[CurrentUser] User $user): Response|RedirectResponse
    {
        if ($invitation->team->roleOf($user) !== null) {
            return $this->alreadyMember($invitation);
        }

        return Inertia::render('invitations/show', [
            'invitation' => InvitationPreviewResource::make($invitation),
            'acceptUrl' => URL::temporarySignedRoute(
                'invitations.accept',
                $invitation->expires_at,
                ['invitation' => $invitation->id],
                absolute: false,
            ),
        ]);
    }

    public function store(Invitation $invitation, AcceptInvitation $acceptInvitation, #[CurrentUser] User $user): RedirectResponse
    {
        if (! $acceptInvitation->handle($invitation, $user)) {
            return $this->alreadyMember($invitation);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Welcome to :team!', ['team' => $invitation->team->name])]);

        return to_route('teams.show', $invitation->team);
    }

    private function alreadyMember(Invitation $invitation): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'info', 'message' => __('You are already a member of :team.', ['team' => $invitation->team->name])]);

        return to_route('teams.show', $invitation->team);
    }
}
