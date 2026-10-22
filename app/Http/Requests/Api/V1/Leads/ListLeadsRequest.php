<?php

namespace App\Http\Requests\Api\V1\Leads;

use App\DataTransferObjects\Leads\LeadFiltersData;
use App\Enums\LeadInterest;
use App\Enums\LeadSort;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListLeadsRequest extends FormRequest
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

    public function toData(): LeadFiltersData
    {
        return new LeadFiltersData(
            search: $this->filled('search') ? $this->string('search')->trim()->toString() : null,
            status: $this->enum('status', LeadStatus::class),
            open: $this->boolean('open'),
            interest: $this->enum('interest', LeadInterest::class),
            source: $this->enum('source', LeadSource::class),
            sort: $this->enum('sort', LeadSort::class) ?? LeadSort::Latest,
            perPage: $this->integer('per_page', 10),
        );
    }
}
