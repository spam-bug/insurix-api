<?php

namespace App\Http\Requests\Api\V1\Account;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBioRequest extends FormRequest
{
    /**
     * Agents only ever edit their own form.
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
            'bio' => ['nullable', 'string', 'max:600'],
        ];
    }

    public function bio(): ?string
    {
        return $this->filled('bio') ? $this->string('bio')->trim()->toString() : null;
    }
}
