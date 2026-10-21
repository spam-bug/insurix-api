<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Policies;

use App\Enums\PolicyStatus;
use App\Models\Policy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPoliciesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Policy::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(PolicyStatus::class)],
            'client_id' => ['nullable', 'uuid'],
            'product_id' => ['nullable', 'uuid'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
