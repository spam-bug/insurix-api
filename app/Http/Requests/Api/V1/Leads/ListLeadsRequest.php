<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Leads;

use App\Enums\LeadInterest;
use App\Enums\LeadSort;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListLeadsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Lead::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(LeadStatus::class)],
            'open' => ['nullable', 'boolean'],
            'interest' => ['nullable', Rule::enum(LeadInterest::class)],
            'source' => ['nullable', Rule::enum(LeadSource::class)],
            'sort' => ['nullable', Rule::enum(LeadSort::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
