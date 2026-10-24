<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Clients;

use App\Enums\CivilStatus;
use App\Models\Client;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Client::class);
    }

    /**
     * A client added directly, without a lead: an agent bringing over their existing book.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'birthdate' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'civil_status' => ['nullable', Rule::enum(CivilStatus::class)],
            'occupation' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
