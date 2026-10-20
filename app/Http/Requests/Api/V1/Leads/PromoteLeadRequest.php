<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Leads;

use App\DataTransferObject\Clients\ClientProfileData;
use App\Enums\CivilStatus;
use App\Enums\LeadStatus;
use App\Models\Lead;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PromoteLeadRequest extends FormRequest
{
    /**
     * Ownership is checked in the controller with the lead policy.
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
            'birthdate' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'civil_status' => ['nullable', Rule::enum(CivilStatus::class)],
            'occupation' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * A lead can be promoted once.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $lead = $this->route('lead');

                if ($lead instanceof Lead && $lead->status === LeadStatus::Converted) {
                    $validator->errors()->add('lead', 'This lead is already a client.');
                }
            },
        ];
    }

    public function toData(): ClientProfileData
    {
        return new ClientProfileData(
            birthdate: $this->filled('birthdate') ? $this->date('birthdate')?->toImmutable() : null,
            civilStatus: $this->enum('civil_status', CivilStatus::class),
            occupation: $this->filled('occupation') ? $this->string('occupation')->trim()->toString() : null,
        );
    }
}
