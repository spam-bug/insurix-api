<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Workspaces;

use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', app(Workspace::class));
    }

    /**
     * The owner types the workspace's name to confirm, so it can't be deleted by a stray click.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', Rule::in([app(Workspace::class)->name])],
        ];
    }
}
