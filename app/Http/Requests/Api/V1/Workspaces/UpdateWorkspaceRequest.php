<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Workspaces;

use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', app(Workspace::class));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'timezone:all'],
            'bio' => ['nullable', 'string', 'max:600'],
        ];
    }
}
