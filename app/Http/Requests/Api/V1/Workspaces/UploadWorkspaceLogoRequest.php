<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Workspaces;

use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadWorkspaceLogoRequest extends FormRequest
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
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=64,min_height=64,max_width=4000,max_height=4000'],
        ];
    }
}
