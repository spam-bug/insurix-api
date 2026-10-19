<?php

namespace App\Http\Requests\Api\V1\Account;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/** Sensitive security changes ask for the agent's password again. */
class ConfirmPasswordRequest extends FormRequest
{
    /**
     * Agents only ever change their own security settings.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'current_password:sanctum'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.current_password' => 'That isn’t your password.',
        ];
    }
}
