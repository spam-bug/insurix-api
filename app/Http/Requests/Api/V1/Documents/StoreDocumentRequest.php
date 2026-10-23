<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Documents;

use App\Enums\DocumentCategory;
use App\Models\Policy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An upload for a client: the file plus its details. Without a name, the file's name is used.
 */
class StoreDocumentRequest extends FormRequest
{
    /** Largest file accepted, in kilobytes (10 MB). */
    public const MAX_KILOBYTES = 10240;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('client'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:'.self::MAX_KILOBYTES, 'mimes:pdf,jpg,jpeg,png,webp,heic,doc,docx,xls,xlsx'],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'category' => ['required', Rule::enum(DocumentCategory::class)],
            'policy_id' => ['nullable', 'uuid', Rule::exists(Policy::class, 'id')->where('client_id', $this->route('client')->id)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
