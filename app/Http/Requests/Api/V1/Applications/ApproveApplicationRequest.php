<?php

namespace App\Http\Requests\Api\V1\Applications;

use App\Enums\RenewalTerm;
use App\Models\Policy;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** The details that come with an approval: when the policy takes effect, and its number. */
class ApproveApplicationRequest extends FormRequest
{
    /**
     * Ownership is checked in the controller with the policy policy.
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
            'effective_date' => ['nullable', 'date'],
            'policy_number' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Set renewal terms count from the effective date, so the policy needs one once in force.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $policy = $this->route('policy');
                $needsDate = $policy instanceof Policy
                    && $policy->renewal_term !== null
                    && $policy->renewal_term !== RenewalTerm::Custom
                    && $policy->effective_date === null;

                if ($needsDate && ! $this->filled('effective_date')) {
                    $validator->errors()->add('effective_date', 'Add the date it takes effect, so its renewals can be worked out.');
                }
            },
        ];
    }
}
