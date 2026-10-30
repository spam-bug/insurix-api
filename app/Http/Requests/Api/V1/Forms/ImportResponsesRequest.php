<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Forms;

use App\Enums\FormResponseStatus;
use App\Enums\ImportKind;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportResponsesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', app(Workspace::class));
    }

    /**
     * New responses of this form only; added ones are already a lead or client.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::enum(ImportKind::class)],
            'response_ids' => ['required', 'array', 'min:1', 'max:500'],
            'response_ids.*' => ['uuid', 'distinct', Rule::exists('form_responses', 'id')
                ->where('form_id', $this->route('form')->id)
                ->where('status', FormResponseStatus::New->value)],
        ];
    }
}
