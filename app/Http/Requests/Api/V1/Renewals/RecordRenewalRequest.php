<?php

namespace App\Http\Requests\Api\V1\Renewals;

use App\Enums\RenewalOutcome;
use App\Enums\RenewalTerm;
use App\Models\Policy;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The outcome of the renewal that's waiting for one. A renewed custom-date policy needs the
 * date it renews next.
 */
class RecordRenewalRequest extends FormRequest
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
        $isCustom = $this->policy()?->renewal_term === RenewalTerm::Custom;

        return [
            'due_on' => ['required', 'date'],
            'outcome' => ['required', Rule::enum(RenewalOutcome::class)],
            'next_renewal_date' => [Rule::requiredIf($isCustom && $this->input('outcome') === RenewalOutcome::Renewed->value), 'nullable', 'date', 'after:due_on'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Only the renewal that's waiting for an outcome can be recorded.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $policy = $this->policy();
                if ($policy === null || $validator->errors()->has('due_on')) {
                    return;
                }

                $awaiting = $policy->renewalAwaitingOutcome(CarbonImmutable::now($policy->user->timezone)->startOfDay());
                if ($awaiting === null || $awaiting->toDateString() !== $this->date('due_on')?->toDateString()) {
                    $validator->errors()->add('due_on', 'This renewal isn’t waiting for an outcome.');
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
            'next_renewal_date.required' => 'Pick the date it renews next.',
            'next_renewal_date.after' => 'The next renewal has to come after this one.',
        ];
    }

    public function dueOn(): CarbonImmutable
    {
        return $this->date('due_on')->toImmutable();
    }

    public function outcome(): RenewalOutcome
    {
        return $this->enum('outcome', RenewalOutcome::class) ?? RenewalOutcome::Renewed;
    }

    public function nextRenewalDate(): ?CarbonImmutable
    {
        return $this->filled('next_renewal_date') ? $this->date('next_renewal_date')?->toImmutable() : null;
    }

    public function notes(): ?string
    {
        return $this->filled('notes') ? $this->string('notes')->trim()->toString() : null;
    }

    private function policy(): ?Policy
    {
        $policy = $this->route('policy');

        return $policy instanceof Policy ? $policy : null;
    }
}
