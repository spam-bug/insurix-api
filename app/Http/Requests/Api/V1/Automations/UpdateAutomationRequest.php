<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Automations;

use Illuminate\Contracts\Validation\ValidationRule;

class UpdateAutomationRequest extends StoreAutomationRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('automation'));
    }

    /**
     * An edit sends the full automation, including whether it's on.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'enabled' => ['required', 'boolean'],
        ];
    }
}
