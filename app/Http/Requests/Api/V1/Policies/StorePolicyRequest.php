<?php

namespace App\Http\Requests\Api\V1\Policies;

use App\DataTransferObjects\Policies\PolicyData;
use App\Enums\PaymentFrequency;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Used for recording and editing a policy; an edit sends every field. */
class StorePolicyRequest extends FormRequest
{
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
            'insurer' => ['required', 'string', 'max:255'],
            'product_name' => ['required', 'string', 'max:255'],
            'policy_number' => ['nullable', 'string', 'max:100'],
            'type' => ['required', Rule::enum(PolicyType::class)],
            'status' => ['required', Rule::enum(PolicyStatus::class)],
            'premium' => ['required', 'numeric', 'gt:0', 'max:9999999999.99', 'decimal:0,2'],
            'payment_frequency' => ['required', Rule::enum(PaymentFrequency::class)],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'effective_date' => ['nullable', 'date'],
            'renewal_date' => ['nullable', 'date', 'after_or_equal:effective_date'],
            'commission_received_at' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
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
        ];
    }

    public function toData(): PolicyData
    {
        return new PolicyData(
            insurer: $this->string('insurer')->trim()->toString(),
            productName: $this->string('product_name')->trim()->toString(),
            policyNumber: $this->filled('policy_number') ? $this->string('policy_number')->trim()->toString() : null,
            type: $this->enum('type', PolicyType::class) ?? PolicyType::Other,
            status: $this->enum('status', PolicyStatus::class) ?? PolicyStatus::Pending,
            premium: (string) $this->input('premium'),
            paymentFrequency: $this->enum('payment_frequency', PaymentFrequency::class) ?? PaymentFrequency::Annual,
            commissionRate: (string) $this->input('commission_rate'),
            effectiveDate: $this->filled('effective_date') ? $this->date('effective_date')?->toImmutable() : null,
            renewalDate: $this->filled('renewal_date') ? $this->date('renewal_date')?->toImmutable() : null,
            commissionReceivedAt: $this->filled('commission_received_at') ? $this->date('commission_received_at')?->toImmutable() : null,
            notes: $this->filled('notes') ? $this->string('notes')->trim()->toString() : null,
        );
    }
}
