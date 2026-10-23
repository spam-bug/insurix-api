<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Leads;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ScheduleLeadMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('lead'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
