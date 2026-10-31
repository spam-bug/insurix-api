<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Automations;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAutomationEnabledRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('automation'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
        ];
    }
}
