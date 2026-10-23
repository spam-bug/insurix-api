<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Policies;

use App\Enums\FollowUpType;
use App\Models\Policy;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkStorePolicyFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Policy::class);
    }

    /**
     * Every policy must be the agent's own, so one foreign id rejects the whole batch.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['uuid', 'distinct', Rule::exists(Policy::class, 'id')->where('workspace_id', app(Workspace::class)->id)],
            'type' => ['required', Rule::enum(FollowUpType::class)],
            'title' => ['required', 'string', 'max:255'],
            'due_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
