<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Applications;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/** The details that come with an approval: when the policy takes effect, and its number. */
class ApproveApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('policy'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'effective_date' => ['nullable', 'date'],
            'policy_number' => ['nullable', 'string', 'max:100'],
        ];
    }
}
