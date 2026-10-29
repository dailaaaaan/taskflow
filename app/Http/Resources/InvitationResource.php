<?php

namespace App\Http\Resources;

use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * An active invite link, as listed to team admins.
 *
 * @mixin Invitation
 */
final class InvitationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role,
            'expires_at' => $this->expires_at->toIso8601String(),
            'created_by' => $this->creator?->name,
            // The signature covers the path only, so the link survives host or
            // proxy differences; it expires together with the invitation.
            'url' => url(URL::temporarySignedRoute(
                'invitations.show',
                $this->expires_at,
                ['invitation' => $this->id],
                absolute: false,
            )),
        ];
    }
}
