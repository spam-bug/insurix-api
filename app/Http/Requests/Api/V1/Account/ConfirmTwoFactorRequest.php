<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Account;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmTwoFactorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->user());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:10'],
        ];
    }
}
