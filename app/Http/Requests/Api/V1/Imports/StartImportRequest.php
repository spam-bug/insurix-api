<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Imports;

use App\Enums\ImportKind;
use App\Models\Workspace;
use App\Support\Spreadsheet;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', app(Workspace::class));
    }

    /**
     * CSV or Excel, up to 10 MB (tens of thousands of rows).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::enum(ImportKind::class)],
            'file' => ['required', 'file', 'extensions:'.implode(',', Spreadsheet::EXTENSIONS), 'max:10240'],
        ];
    }
}
