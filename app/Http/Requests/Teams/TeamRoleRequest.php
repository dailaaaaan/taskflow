<?php

namespace App\Http\Requests\Teams;

use App\Enums\TeamRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a team role, used when creating invite links and changing a
 * member's role. Authorization happens on the route.
 */
class TeamRoleRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::enum(TeamRole::class)],
        ];
    }

    public function role(): TeamRole
    {
        return TeamRole::from($this->string('role')->toString());
    }
}
