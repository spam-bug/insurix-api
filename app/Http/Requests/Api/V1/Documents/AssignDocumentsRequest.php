<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Documents;

use App\Models\Client;
use App\Models\Document;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Document::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $workspaceId = app(Workspace::class)->id;

        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['uuid', Rule::exists(Document::class, 'id')->where('workspace_id', $workspaceId)],
            'client_id' => ['required', 'uuid', Rule::exists(Client::class, 'id')->where('workspace_id', $workspaceId)],
        ];
    }
}
