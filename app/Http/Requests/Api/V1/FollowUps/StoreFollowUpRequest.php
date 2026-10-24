<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\FollowUps;

use App\Enums\FollowUpType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('lead') ?? $this->route('client'));
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
}
