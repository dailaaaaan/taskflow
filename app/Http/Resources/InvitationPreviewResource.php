<?php

namespace App\Http\Resources;

use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What an invited user sees before joining: no member list, no emails.
 *
 * @mixin Invitation
 */
final class InvitationPreviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'team' => $this->team->name,
            'role' => $this->role,
            'invited_by' => $this->creator?->name,
            'members_count' => $this->team->memberships()->count(),
            'expires_at' => $this->expires_at->toIso8601String(),
        ];
    }
}
