<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Policies;

use App\Enums\PaymentFrequency;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Enums\RenewalTerm;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('client'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['nullable', 'uuid', Rule::exists(Product::class, 'id')->where('user_id', $this->user()->id)],
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
}
