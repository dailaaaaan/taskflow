<?php

namespace App\Http\Requests\Boards;

use App\Enums\BoardColor;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates creating and editing a board. Authorization happens on the route.
 */
class SaveBoardRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'color' => ['required', Rule::enum(BoardColor::class)],
        ];
    }

    public function color(): BoardColor
    {
        return BoardColor::from($this->string('color')->toString());
    }
}
