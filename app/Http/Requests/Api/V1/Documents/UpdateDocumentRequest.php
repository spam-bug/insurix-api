<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Documents;

use App\Enums\DocumentCategory;
use App\Models\Client;
use App\Models\Policy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('document'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Moving a document checks its policy against the new client; leaving client_id out keeps the current one.
        $clientId = $this->exists('client_id') ? $this->input('client_id') : $this->route('document')->client_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(DocumentCategory::class)],
            'client_id' => ['sometimes', 'nullable', 'uuid', Rule::exists(Client::class, 'id')->where('workspace_id', $this->route('document')->workspace_id)],
            'policy_id' => ['nullable', 'uuid', Rule::prohibitedIf($clientId === null), Rule::exists(Policy::class, 'id')->where('client_id', $clientId)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
