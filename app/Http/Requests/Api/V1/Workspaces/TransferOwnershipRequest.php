<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Workspaces;

use App\Enums\WorkspaceRole;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferOwnershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', app(Workspace::class));
    }

    /**
     * The new owner must already be a member of this workspace.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'member_id' => [
                'required',
                'uuid',
                Rule::exists('workspace_members', 'id')
                    ->where('workspace_id', app(Workspace::class)->id)
                    ->where('role', WorkspaceRole::Member->value),
            ],
        ];
    }
}
