<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GivePremiumRequest extends FormRequest
{
    /** Admins only, and not on a deleted workspace. */
    public function authorize(): bool
    {
        /** @var Workspace $workspace */
        $workspace = $this->route('workspace');

        return $this->user('staff')?->can('change-accounts') && ! $workspace->trashed();
    }

    /**
     * No days means Premium with no end date.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
