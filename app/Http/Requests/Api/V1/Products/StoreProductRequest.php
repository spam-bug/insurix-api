<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Products;

use App\Enums\PaymentFrequency;
use App\Enums\PolicyType;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Product::class);
    }

    /**
     * A product name is unique within the agent's catalog.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(Product::class)->where('user_id', $this->user()->id)->ignore($this->route('product'))],
            'type' => ['required', Rule::enum(PolicyType::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'payment_frequency' => ['nullable', Rule::enum(PaymentFrequency::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
