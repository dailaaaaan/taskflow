<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * A new email has to be verified, which would lock every other visitor out
     * of a shared demo account.
     */
    public function authorize(): bool
    {
        return ! config()->boolean('demo.enabled')
            || ! in_array($this->user()?->email, config()->array('demo.accounts'), true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->profileRules($this->user()?->id);
    }
}
