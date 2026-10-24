<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Insights;

use App\Enums\InsightPeriod;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowInsightReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', app(Workspace::class));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'period' => ['nullable', Rule::enum(InsightPeriod::class)],
            'offset' => ['nullable', 'integer', 'min:0', 'max:120'],
        ];
    }
}
