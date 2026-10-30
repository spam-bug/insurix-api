<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Forms;

use App\Enums\FormResponseStatus;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateResponseStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', app(Workspace::class));
    }

    /**
     * Dismiss responses, or bring dismissed ones back. Added responses stay added.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'response_ids' => ['required', 'array', 'min:1', 'max:500'],
            'response_ids.*' => ['uuid', Rule::exists('form_responses', 'id')
                ->where('workspace_id', app(Workspace::class)->id)
                ->whereIn('status', [FormResponseStatus::New->value, FormResponseStatus::Dismissed->value])],
            'status' => ['required', Rule::in([FormResponseStatus::New->value, FormResponseStatus::Dismissed->value])],
        ];
    }
}
