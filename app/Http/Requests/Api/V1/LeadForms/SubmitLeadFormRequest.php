<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\LeadForms;

use App\Enums\LeadInterest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitLeadFormRequest extends FormRequest
{
    /**
     * A public form: anyone with the link may submit. An unknown link is a 404 from route binding.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
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
}
