<?php

namespace App\Http\Requests\Api\V1\Policies;

use App\DataTransferObjects\Policies\PolicyData;
use App\Enums\PaymentFrequency;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Enums\RenewalTerm;
use App\Models\Policy;
use App\Models\Product;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Used for recording and editing a policy; an edit sends every field. Picking a catalog
 * product supplies the product name and type; without one they're typed in.
 */
class StorePolicyRequest extends FormRequest
{
    private ?Product $product = null;

    /**
     * Ownership is checked in the controller with the client and policy policies.
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
            'product_id' => ['nullable', 'integer', Rule::exists(Product::class, 'id')->where('user_id', $this->user()?->id)],
            'product_name' => ['required_without:product_id', 'nullable', 'string', 'max:255'],
            'policy_number' => ['nullable', 'string', 'max:100'],
            'type' => ['required_without:product_id', 'nullable', Rule::enum(PolicyType::class)],
            'status' => ['required', Rule::enum(PolicyStatus::class)],
            'premium' => ['required', 'numeric', 'gt:0', 'max:9999999999.99', 'decimal:0,2'],
            'coverage_amount' => ['nullable', 'numeric', 'gt:0', 'max:999999999999.99', 'decimal:0,2'],
            'payment_frequency' => ['required', Rule::enum(PaymentFrequency::class)],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'renewal_term' => ['nullable', Rule::enum(RenewalTerm::class)],
            // Set terms count from the effective date, so it's needed for them; a custom term needs its date.
            'effective_date' => ['nullable', 'required_if:renewal_term,monthly,quarterly,annual', 'date'],
            'renewal_date' => ['nullable', 'required_if:renewal_term,custom', 'date', 'after_or_equal:effective_date'],
            'commission_received_at' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * An inactive product can't be picked for a policy, though a policy already on it keeps it.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $product = $this->product();
                $current = $this->route('policy');

                if ($product && ! $product->is_active && ! ($current instanceof Policy && $current->product_id === $product->id)) {
                    $validator->errors()->add('product_id', 'This product is inactive. Reactivate it in your catalog to use it.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'premium.gt' => 'Enter the premium per payment.',
            'renewal_date.after_or_equal' => 'The renewal date can’t be before the policy takes effect.',
            'renewal_date.required_if' => 'Pick the date it renews.',
            'effective_date.required_if' => 'Add the effective date so renewals can be worked out from it.',
            'product_name.required_without' => 'Pick a product or enter the product name.',
        ];
    }

    public function toData(): PolicyData
    {
        $product = $this->product();

        return new PolicyData(
            productId: $product?->id,
            productName: $product?->name ?? $this->string('product_name')->trim()->toString(),
            policyNumber: $this->filled('policy_number') ? $this->string('policy_number')->trim()->toString() : null,
            type: $product?->type ?? $this->enum('type', PolicyType::class) ?? PolicyType::Other,
            status: $this->enum('status', PolicyStatus::class) ?? PolicyStatus::Pending,
            premium: (string) $this->input('premium'),
            coverageAmount: $this->filled('coverage_amount') ? (string) $this->input('coverage_amount') : null,
            paymentFrequency: $this->enum('payment_frequency', PaymentFrequency::class) ?? PaymentFrequency::Annual,
            commissionRate: (string) $this->input('commission_rate'),
            effectiveDate: $this->filled('effective_date') ? $this->date('effective_date')?->toImmutable() : null,
            renewalTerm: $this->enum('renewal_term', RenewalTerm::class),
            // Only a custom term keeps a fixed date; set terms are worked out from the effective date.
            renewalDate: $this->enum('renewal_term', RenewalTerm::class) === RenewalTerm::Custom && $this->filled('renewal_date')
                ? $this->date('renewal_date')?->toImmutable()
                : null,
            commissionReceivedAt: $this->filled('commission_received_at') ? $this->date('commission_received_at')?->toImmutable() : null,
            notes: $this->filled('notes') ? $this->string('notes')->trim()->toString() : null,
        );
    }

    /**
     * The picked catalog product, scoped to the agent. Validation has already checked it exists.
     */
    private function product(): ?Product
    {
        if (! $this->filled('product_id')) {
            return null;
        }

        return $this->product ??= $this->user()?->products()->find($this->integer('product_id'));
    }
}
