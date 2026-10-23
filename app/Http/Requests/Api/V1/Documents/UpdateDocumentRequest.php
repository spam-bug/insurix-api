<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Documents;

use App\DataTransferObjects\Documents\DocumentData;
use App\Enums\DocumentCategory;
use App\Models\Client;
use App\Models\Document;
use App\Models\Policy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A document's details. The policy, when given, must be one of the same client's.
 */
class UpdateDocumentRequest extends FormRequest
{
    /**
     * Ownership is checked in the controller with the client and document policies.
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
            'name' => [$this->nameRule(), 'nullable', 'string', 'max:255'],
            'category' => ['required', Rule::enum(DocumentCategory::class)],
            'policy_id' => ['nullable', 'uuid', Rule::exists(Policy::class, 'id')->where('client_id', $this->clientId())],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'policy_id.exists' => 'Pick one of this client’s policies.',
        ];
    }

    public function toData(): DocumentData
    {
        return new DocumentData(
            name: $this->filled('name') ? $this->string('name')->trim()->toString() : null,
            category: $this->enum('category', DocumentCategory::class) ?? DocumentCategory::Other,
            policyId: $this->filled('policy_id') ? $this->string('policy_id')->toString() : null,
            notes: $this->filled('notes') ? $this->string('notes')->trim()->toString() : null,
        );
    }

    /**
     * An edit keeps a name; an upload may leave it out and use the file's name.
     */
    protected function nameRule(): string
    {
        return 'required';
    }

    /**
     * The client the document is (or will be) kept for.
     */
    protected function clientId(): ?string
    {
        $document = $this->route('document');

        return $document instanceof Document ? $document->client_id : null;
    }

    protected function routeClient(): ?Client
    {
        $client = $this->route('client');

        return $client instanceof Client ? $client : null;
    }
}
