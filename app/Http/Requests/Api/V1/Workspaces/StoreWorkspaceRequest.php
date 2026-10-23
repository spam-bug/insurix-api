<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Workspaces;

use App\Enums\WorkspaceType;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Workspace::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(WorkspaceType::class)],
        ];
    }
}
