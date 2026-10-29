<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Notes;

use App\Enums\NoteLink;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListNotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', app(Workspace::class));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'link' => ['nullable', Rule::enum(NoteLink::class)],
            'mine' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:100'],
            'lead_id' => ['nullable', 'uuid'],
            'client_id' => ['nullable', 'uuid'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
