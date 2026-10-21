<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Renewals;

use App\Enums\RenewalOutcome;
use App\Enums\RenewalTerm;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordRenewalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('policy'));
    }

    /**
     * A renewed custom-date policy needs the date it renews next.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isCustom = $this->route('policy')->renewal_term === RenewalTerm::Custom;

        return [
            'due_on' => ['required', 'date'],
            'outcome' => ['required', Rule::enum(RenewalOutcome::class)],
            'next_renewal_date' => [Rule::requiredIf($isCustom && $this->input('outcome') === RenewalOutcome::Renewed->value), 'nullable', 'date', 'after:due_on'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
