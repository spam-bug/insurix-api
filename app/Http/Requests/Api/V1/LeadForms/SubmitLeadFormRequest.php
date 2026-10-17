<?php

namespace App\Http\Requests\Api\V1\LeadForms;

use App\DataTransferObjects\Leads\LeadData;
use App\Enums\LeadInterest;
use App\Enums\LeadSource;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitLeadFormRequest extends FormRequest
{
    /**
     * Anyone with the link may submit; the token in the URL identifies the agent.
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
            'interest' => ['nullable', Rule::enum(LeadInterest::class)],
            'message' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required_without' => 'Enter an email or a phone number so we can reach you.',
            'phone.required_without' => 'Enter a phone number or an email so we can reach you.',
        ];
    }

    /**
     * The hidden "website" field is a honeypot: people never see it, simple bots fill it in.
     */
    public function isLikelySpam(): bool
    {
        return $this->filled('website');
    }

    public function toData(): LeadData
    {
        return new LeadData(
            name: $this->string('name')->trim()->toString(),
            email: $this->filled('email') ? $this->string('email')->trim()->lower()->toString() : null,
            phone: $this->filled('phone') ? $this->string('phone')->trim()->toString() : null,
            address: null,
            source: LeadSource::ShareableForm,
            interest: $this->enum('interest', LeadInterest::class),
            notes: $this->filled('message') ? $this->string('message')->trim()->toString() : null,
        );
    }
}
