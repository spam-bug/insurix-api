<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Documents;

use App\Enums\DocumentCategory;
use App\Models\Document;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Document::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', Rule::enum(DocumentCategory::class)],
            'unassigned' => ['nullable', 'boolean'],
            'client_id' => ['nullable', 'uuid'],
            'policy_id' => ['nullable', 'uuid'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
