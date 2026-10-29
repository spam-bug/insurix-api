<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Notes;

use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', app(Workspace::class));
    }

    /**
     * A note is about one record of this workspace at most.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $workspaceId = app(Workspace::class)->id;

        return [
            'body' => ['required', 'string', 'max:5000'],
            'lead_id' => ['nullable', 'uuid', 'prohibits:client_id,policy_id', Rule::exists('leads', 'id')->where('workspace_id', $workspaceId)],
            'client_id' => ['nullable', 'uuid', 'prohibits:lead_id,policy_id', Rule::exists('clients', 'id')->where('workspace_id', $workspaceId)],
            'policy_id' => ['nullable', 'uuid', 'prohibits:lead_id,client_id', Rule::exists('policies', 'id')->where('workspace_id', $workspaceId)],
        ];
    }
}
