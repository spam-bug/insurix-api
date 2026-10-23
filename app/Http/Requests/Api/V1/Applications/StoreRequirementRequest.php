<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Applications;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('policy'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
        ];
    }
}
