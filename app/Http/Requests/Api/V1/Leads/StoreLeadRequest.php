<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Leads;

use App\Enums\ContactChannel;
use App\Enums\LeadInterest;
use App\Enums\LeadSource;
use App\Models\Lead;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Lead::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'required_without:phone', 'required_if:preferred_channel,email', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'required_without:email', 'required_if:preferred_channel,call,sms,viber,whatsapp', 'string', 'max:50'],
            'preferred_channel' => ['nullable', Rule::enum(ContactChannel::class)],
            'messenger_username' => ['nullable', 'required_if:preferred_channel,messenger', 'string', 'max:255'],
            'telegram_username' => ['nullable', 'required_if:preferred_channel,telegram', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', Rule::enum(LeadSource::class)],
            'interest' => ['nullable', Rule::enum(LeadInterest::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
