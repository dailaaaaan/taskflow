<?php

namespace App\Http\Requests\Boards;

use App\Models\Board;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a drag & drop move of a card. Authorization happens on the route.
 */
class MoveCardRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(#[RouteParameter('board')] Board $board): array
    {
        return [
            // Cards only move between lists of their own board (ADR-0007).
            'list_id' => ['required', 'integer', Rule::exists('board_lists', 'id')->where('board_id', $board->id)],
            // Index in the destination list, counted without the moved card.
            'index' => ['required', 'integer', 'min:0'],
        ];
    }
}
