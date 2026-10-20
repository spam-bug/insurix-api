<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Products;

use App\DataTransferObjects\Products\ProductFiltersData;
use App\Enums\PolicyType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListProductsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * A larger page size lets the policy form load the whole catalog for its picker.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::enum(PolicyType::class)],
            'active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function toData(): ProductFiltersData
    {
        return new ProductFiltersData(
            search: $this->filled('search') ? $this->string('search')->trim()->toString() : null,
            type: $this->enum('type', PolicyType::class),
            active: $this->has('active') ? $this->boolean('active') : null,
            perPage: $this->integer('per_page', 10),
        );
    }
}
