<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Imports;

use App\Enums\ImportField;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MapImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('import'));
    }

    /**
     * Which column fills which field: each field and column once at most, and a name (whole,
     * or first and last) is needed.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $import = $this->route('import');
        $fields = array_map(fn (ImportField $field) => $field->value, ImportField::for($import->kind));

        return [
            'mapping' => ['required', 'array:'.implode(',', $fields)],
            'mapping.*' => ['integer', 'min:0', 'max:'.(count($import->headers) - 1), 'distinct'],
            'mapping.name' => ['required_without_all:mapping.first_name,mapping.last_name'],
        ];
    }
}
