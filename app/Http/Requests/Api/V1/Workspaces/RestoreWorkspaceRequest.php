<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Workspaces;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RestoreWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('restore', $this->route('workspace'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
