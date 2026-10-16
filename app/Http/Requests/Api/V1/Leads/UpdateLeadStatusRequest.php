<?php

namespace App\Http\Requests\Api\V1\Leads;

use App\Enums\LeadStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadStatusRequest extends FormRequest
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
            'status' => ['required', Rule::enum(LeadStatus::class)],
        ];
    }

    public function status(): LeadStatus
    {
        return $this->enum('status', LeadStatus::class) ?? LeadStatus::Prospect;
    }
}
