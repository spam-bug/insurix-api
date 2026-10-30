<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Premium;

use App\Enums\WorkspaceRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClaimPremiumLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('claim', $this->route('premiumLink'));
    }

    /**
     * Only a workspace the user owns; one they were invited into can't get Premium this way.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'workspace_id' => [
                'nullable',
                'uuid',
                Rule::exists('workspace_members', 'workspace_id')
                    ->where('user_id', $this->user()->id)
                    ->where('role', WorkspaceRole::Owner->value),
            ],
        ];
    }
}
