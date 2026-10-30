<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Forms;

use App\Enums\FormResponseStatus;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListFormResponsesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', app(Workspace::class));
    }

    /**
     * Responses of one form, or those that became a given lead or client.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'form_id' => ['nullable', 'uuid'],
            'lead_id' => ['nullable', 'uuid'],
            'client_id' => ['nullable', 'uuid'],
            'status' => ['nullable', Rule::enum(FormResponseStatus::class)],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
