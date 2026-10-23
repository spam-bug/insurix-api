<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Products;

use App\Enums\PolicyType;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Product::class);
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
}
