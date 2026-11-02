<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use App\Enums\Plan;
use App\Models\Staff;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListStaffWorkspacesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('staff') instanceof Staff;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'plan' => ['nullable', Rule::enum(Plan::class)],
            'deleted' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
