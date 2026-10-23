<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Applications;

use App\Models\Document;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Ticks a requirement off, and links the client's document that covers it. */
class UpdateRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('requirement'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'received' => ['required', 'boolean'],
            'document_id' => ['nullable', 'uuid', Rule::exists(Document::class, 'id')->where('client_id', $this->route('requirement')->policy->client_id)],
        ];
    }
}
