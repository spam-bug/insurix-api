<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Applications;

use App\Enums\ApplicationStage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveApplicationStageRequest extends FormRequest
{
    /**
     * Ownership is checked in the controller with the policy policy.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'stage' => ['required', Rule::enum(ApplicationStage::class)],
        ];
    }

    public function stage(): ApplicationStage
    {
        return $this->enum('stage', ApplicationStage::class) ?? ApplicationStage::Submitted;
    }
}
