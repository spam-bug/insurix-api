<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\ServiceRequests;

use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceRequestType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('client'));
    }

    /**
     * The policy, when given, must be one of the client's. The claim amount and the insurer's
     * reference only apply to claims; for other types they're dropped.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $clientId = $this->route('client')?->id ?? $this->route('serviceRequest')?->client_id;

        return [
            'type' => ['required', Rule::enum(ServiceRequestType::class)],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(ServiceRequestStatus::class)],
            'details' => ['nullable', 'string', 'max:5000'],
            'policy_id' => ['nullable', 'uuid', Rule::exists('policies', 'id')->where('client_id', $clientId)],
            'due_on' => ['nullable', 'date'],
            'claim_amount' => ['exclude_unless:type,claim', 'nullable', 'numeric', 'min:0', 'max:999999999'],
            'insurer_reference' => ['exclude_unless:type,claim', 'nullable', 'string', 'max:100'],
        ];
    }
}
