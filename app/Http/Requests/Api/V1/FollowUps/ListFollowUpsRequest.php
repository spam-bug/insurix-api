<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\FollowUps;

use App\Enums\FollowUpWindow;
use App\Models\FollowUp;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListFollowUpsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', FollowUp::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'window' => ['nullable', Rule::enum(FollowUpWindow::class)],
            'lead_id' => ['nullable', 'uuid'],
            'client_id' => ['nullable', 'uuid'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
