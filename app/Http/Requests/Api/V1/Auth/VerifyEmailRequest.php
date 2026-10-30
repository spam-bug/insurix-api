<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Opened from the email, possibly on a device that isn't signed in. The signed URL (checked by
 * the route's middleware) is what grants it, so anyone holding a valid link may use it.
 */
class VerifyEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->hasValidRelativeSignature();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
