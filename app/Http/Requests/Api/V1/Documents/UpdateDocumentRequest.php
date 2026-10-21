<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Documents;

use App\Enums\DocumentCategory;
use App\Models\Policy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('document'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(DocumentCategory::class)],
            'policy_id' => ['nullable', 'uuid', Rule::exists(Policy::class, 'id')->where('client_id', $this->route('document')->client_id)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
