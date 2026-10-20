<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Leads;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'status' => ['required', Rule::enum(LeadStatus::class)->only(LeadStatus::manual())],
        ];
    }

    /**
     * A promoted lead's stage is settled; its client is where the work continues.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $lead = $this->route('lead');

                if ($lead instanceof Lead && $lead->status === LeadStatus::Converted) {
                    $validator->errors()->add('status', 'This lead is already a client.');
                }
            },
        ];
    }

    public function status(): LeadStatus
    {
        return $this->enum('status', LeadStatus::class) ?? LeadStatus::Prospect;
    }
}
