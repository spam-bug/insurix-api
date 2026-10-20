<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Leads;

use App\Models\Lead;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkDeleteLeadsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Lead::class);
    }

    /**
     * Every lead must be the agent's own, so one foreign id rejects the whole batch.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['uuid', 'distinct', Rule::exists(Lead::class, 'id')->where('user_id', $this->user()->id)],
        ];
    }
}
