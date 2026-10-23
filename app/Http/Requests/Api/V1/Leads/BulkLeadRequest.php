<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Leads;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Every id must be one of the agent's own leads.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['uuid', 'distinct', Rule::exists(Lead::class, 'id')->where('user_id', $this->user()?->id)],
            'status' => [Rule::requiredIf($this->routeIs('api.v1.leads.bulk.status')), Rule::enum(LeadStatus::class)->only(LeadStatus::manual())],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function leadIds(): array
    {
        return array_map('strval', $this->array('ids'));
    }

    public function status(): LeadStatus
    {
        return $this->enum('status', LeadStatus::class) ?? LeadStatus::Prospect;
    }
}
