<?php

namespace App\Http\Requests\Api\V1\FollowUps;

use App\DataTransferObjects\FollowUps\FollowUpFiltersData;
use App\Enums\FollowUpWindow;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListFollowUpsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            'window' => ['nullable', Rule::enum(FollowUpWindow::class)],
            'lead_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function toData(): FollowUpFiltersData
    {
        return new FollowUpFiltersData(
            window: $this->enum('window', FollowUpWindow::class) ?? FollowUpWindow::Open,
            leadId: $this->filled('lead_id') ? $this->integer('lead_id') : null,
            perPage: $this->integer('per_page', 20),
        );
    }
}
