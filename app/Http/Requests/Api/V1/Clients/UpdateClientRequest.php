<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Clients;

use App\Enums\CivilStatus;
use App\Enums\ContactChannel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('client'));
    }

    /**
     * An edit sends the full client.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'required_if:preferred_channel,email', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'required_if:preferred_channel,call,sms,viber,whatsapp', 'string', 'max:50'],
            'preferred_channel' => ['nullable', Rule::enum(ContactChannel::class)],
            'messenger_username' => ['nullable', 'required_if:preferred_channel,messenger', 'string', 'max:255'],
            'telegram_username' => ['nullable', 'required_if:preferred_channel,telegram', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'birthdate' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'civil_status' => ['nullable', Rule::enum(CivilStatus::class)],
            'occupation' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
