<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Imports;

use App\Enums\ImportRowStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListImportRowsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('import'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(ImportRowStatus::class)],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
