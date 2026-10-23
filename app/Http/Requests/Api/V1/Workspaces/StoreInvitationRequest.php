<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Workspaces;

use App\Enums\WorkspaceRole;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', app(Workspace::class));
    }

    /**
     * Members only for now: a workspace has one owner.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => ['sometimes', Rule::enum(WorkspaceRole::class)->only([WorkspaceRole::Member])],
        ];
    }
}
