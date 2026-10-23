<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TwoFactorChallengeRequest extends FormRequest
{
    /**
     * The challenge in the session from the first sign-in step is the authorization.
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
            'code' => ['required_without:recovery_code', 'nullable', 'string', 'max:10'],
            'recovery_code' => ['required_without:code', 'nullable', 'string', 'max:50'],
        ];
    }
}
