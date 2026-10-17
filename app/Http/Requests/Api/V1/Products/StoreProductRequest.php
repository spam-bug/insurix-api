<?php

namespace App\Http\Requests\Api\V1\Products;

use App\DataTransferObjects\Products\ProductData;
use App\Enums\PaymentFrequency;
use App\Enums\PolicyType;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Used for adding and editing a catalog product; an edit sends every field. */
class StoreProductRequest extends FormRequest
{
    /**
     * Ownership of an edited product is checked in the controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * A product name is unique per insurer within the agent's catalog.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'insurer' => ['required', 'string', 'max:255'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Product::class)
                    ->where('user_id', $this->user()?->id)
                    ->where('insurer', $this->string('insurer')->trim()->toString())
                    ->ignore($product instanceof Product ? $product->id : null),
            ],
            'type' => ['required', Rule::enum(PolicyType::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'payment_frequency' => ['nullable', Rule::enum(PaymentFrequency::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'You already have this product for that insurer.',
        ];
    }

    public function toData(): ProductData
    {
        return new ProductData(
            insurer: $this->string('insurer')->trim()->toString(),
            name: $this->string('name')->trim()->toString(),
            type: $this->enum('type', PolicyType::class) ?? PolicyType::Other,
            description: $this->filled('description') ? $this->string('description')->trim()->toString() : null,
            commissionRate: (string) $this->input('commission_rate'),
            paymentFrequency: $this->enum('payment_frequency', PaymentFrequency::class),
            isActive: $this->boolean('is_active', true),
        );
    }
}
