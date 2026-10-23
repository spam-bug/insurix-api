<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Applications;

use App\Enums\ApplicationStage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveApplicationStageRequest extends FormRequest
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
            'stage' => ['required', Rule::enum(ApplicationStage::class)],
        ];
    }
}
