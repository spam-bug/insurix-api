<?php

namespace App\Http\Requests\Api\V1\FollowUps;

use App\DataTransferObjects\FollowUps\FollowUpData;
use App\Enums\FollowUpType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Used for creating and editing a follow-up; an edit sends every field. */
class StoreFollowUpRequest extends FormRequest
{
    /**
     * Ownership is checked in the controller with the lead and follow-up policies.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Past due times are allowed, so an agent can log something they meant to do earlier.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(FollowUpType::class)],
            'title' => ['required', 'string', 'max:255'],
            'due_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function toData(): FollowUpData
    {
        return new FollowUpData(
            type: $this->enum('type', FollowUpType::class) ?? FollowUpType::Task,
            title: $this->string('title')->trim()->toString(),
            dueAt: $this->date('due_at')->toImmutable()->utc(),
            notes: $this->filled('notes') ? $this->string('notes')->trim()->toString() : null,
        );
    }
}
