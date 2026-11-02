<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RecoverWorkspaceRequest extends FormRequest
{
    /** Admins only, on a deleted workspace that hasn't been purged yet. */
    public function authorize(): bool
    {
        /** @var Workspace $workspace */
        $workspace = $this->route('workspace');

        return $this->user('staff')?->can('change-accounts') && $workspace->trashed();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
