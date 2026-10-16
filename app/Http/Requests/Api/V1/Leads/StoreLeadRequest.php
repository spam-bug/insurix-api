<?php

namespace App\Http\Requests\Api\V1\Leads;

use App\DataTransferObjects\Leads\LeadData;
use App\Enums\LeadInterest;
use App\Enums\LeadSource;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'required_without:phone', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', Rule::enum(LeadSource::class)],
            'interest' => ['nullable', Rule::enum(LeadInterest::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required_without' => 'Enter an email or a phone number.',
            'phone.required_without' => 'Enter a phone number or an email.',
        ];
    }

    public function toData(): LeadData
    {
        return new LeadData(
            name: $this->string('name')->toString(),
            email: $this->filled('email') ? $this->string('email')->lower()->toString() : null,
            phone: $this->filled('phone') ? $this->string('phone')->toString() : null,
            address: $this->filled('address') ? $this->string('address')->toString() : null,
            source: $this->enum('source', LeadSource::class) ?? LeadSource::Manual,
            interest: $this->enum('interest', LeadInterest::class),
            notes: $this->filled('notes') ? $this->string('notes')->toString() : null,
        );
    }
}
