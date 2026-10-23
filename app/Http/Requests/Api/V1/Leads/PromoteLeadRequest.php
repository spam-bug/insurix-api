<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Leads;

use App\Enums\CivilStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PromoteLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('lead'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'birthdate' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'civil_status' => ['nullable', Rule::enum(CivilStatus::class)],
            'occupation' => ['nullable', 'string', 'max:255'],
        ];
    }
}
