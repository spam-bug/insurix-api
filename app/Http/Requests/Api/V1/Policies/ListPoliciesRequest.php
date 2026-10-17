<?php

namespace App\Http\Requests\Api\V1\Policies;

use App\DataTransferObjects\Policies\PolicyFiltersData;
use App\Enums\PolicyStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPoliciesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(PolicyStatus::class)],
            'client_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function toData(): PolicyFiltersData
    {
        return new PolicyFiltersData(
            search: $this->filled('search') ? $this->string('search')->trim()->toString() : null,
            status: $this->enum('status', PolicyStatus::class),
            clientId: $this->filled('client_id') ? $this->integer('client_id') : null,
            perPage: $this->integer('per_page', 10),
        );
    }
}
