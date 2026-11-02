<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePremiumLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user('staff')?->can('change-accounts');
    }

    /**
     * No days means the link gives Premium with no end date.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'max_claims' => ['required', 'integer', 'min:1', 'max:1000'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:today'],
            'email' => ['nullable', 'email', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
