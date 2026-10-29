<?php

namespace App\Http\Requests\Boards;

use App\Models\Board;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the card details modal. Authorization happens on the route.
 */
class UpdateCardRequest extends FormRequest
{
    /**
     * Laravel resolves rules() through the container, so the board bound to
     * the route can be injected with #[RouteParameter].
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(#[RouteParameter('board')] Board $board): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:10000'],
            // Only members of the board's team can be assigned.
            'assignee_id' => [
                'nullable',
                'integer',
                Rule::exists('memberships', 'user_id')->where('team_id', $board->team_id),
            ],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
