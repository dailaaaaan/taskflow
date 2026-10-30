<?php

namespace App\Http\Requests\Boards;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a drag & drop move of a list. Authorization happens on the route.
 */
class MoveListRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Index in the board, counted without the moved list.
            'index' => ['required', 'integer', 'min:0'],
        ];
    }
}
