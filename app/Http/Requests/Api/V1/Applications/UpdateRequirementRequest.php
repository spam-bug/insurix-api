<?php

namespace App\Http\Requests\Api\V1\Applications;

use App\Models\ApplicationRequirement;
use App\Models\Document;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Ticks a requirement off, and links the client's document that covers it. */
class UpdateRequirementRequest extends FormRequest
{
    /**
     * Ownership is checked in the controller with the policy policy.
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
        $requirement = $this->route('requirement');
        $clientId = $requirement instanceof ApplicationRequirement ? $requirement->policy->client_id : null;

        return [
            'received' => ['required', 'boolean'],
            'document_id' => ['nullable', 'uuid', Rule::exists(Document::class, 'id')->where('client_id', $clientId)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document_id.exists' => 'Pick one of this client’s documents.',
        ];
    }

    public function document(): ?Document
    {
        return $this->filled('document_id') ? Document::find($this->string('document_id')->toString()) : null;
    }
}
