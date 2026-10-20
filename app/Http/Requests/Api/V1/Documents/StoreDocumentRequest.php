<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Documents;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * An upload for a client: the file plus its details. Without a name, the file's name is used.
 */
class StoreDocumentRequest extends UpdateDocumentRequest
{
    /** Largest file accepted, in kilobytes (10 MB). */
    public const MAX_KILOBYTES = 10240;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:'.self::MAX_KILOBYTES, 'mimes:pdf,jpg,jpeg,png,webp,heic,doc,docx,xls,xlsx'],
            ...parent::rules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'file.max' => 'Pick a file under 10 MB.',
            'file.mimes' => 'Use a PDF, an image (JPG, PNG, WebP, HEIC), or a Word or Excel file.',
        ];
    }

    protected function nameRule(): string
    {
        return 'sometimes';
    }

    protected function clientId(): ?string
    {
        return $this->routeClient()?->id;
    }
}
