<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TwoFactorChallengeRequest extends FormRequest
{
    /**
     * The challenge from the first sign-in step is the authorization.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * A code from the authenticator app, or one of the recovery codes.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'challenge' => ['required', 'string'],
            'code' => ['required_without:recovery_code', 'nullable', 'string', 'max:10'],
            'recovery_code' => ['required_without:code', 'nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required_without' => 'Enter the 6-digit code from your authenticator app.',
        ];
    }

    public function code(): ?string
    {
        return $this->filled('code') ? preg_replace('/\s+/', '', $this->string('code')->toString()) : null;
    }

    public function recoveryCode(): ?string
    {
        return $this->filled('recovery_code') ? $this->string('recovery_code')->trim()->toString() : null;
    }
}
