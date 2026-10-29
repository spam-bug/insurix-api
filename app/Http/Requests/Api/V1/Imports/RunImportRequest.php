<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Imports;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RunImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('import'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'update_duplicates' => ['required', 'boolean'],
        ];
    }
}
