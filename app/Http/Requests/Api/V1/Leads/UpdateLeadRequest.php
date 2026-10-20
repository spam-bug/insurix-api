<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Leads;

use App\Enums\LeadSource;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateLeadRequest extends StoreLeadRequest
{
    /**
     * An edit sends the full lead, so the source must be there; otherwise it would quietly reset to manual.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'source' => ['required', Rule::enum(LeadSource::class)],
        ];
    }
}
