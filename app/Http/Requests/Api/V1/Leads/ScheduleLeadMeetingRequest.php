<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Leads;

use App\DataTransferObjects\Leads\LeadMeetingData;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ScheduleLeadMeetingRequest extends FormRequest
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
            'scheduled_at' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'scheduled_at.after' => 'Choose a time in the future.',
        ];
    }

    public function toData(): LeadMeetingData
    {
        return new LeadMeetingData(
            scheduledAt: CarbonImmutable::parse($this->string('scheduled_at')->toString())->utc(),
            notes: $this->filled('notes') ? $this->string('notes')->trim()->toString() : null,
        );
    }
}
