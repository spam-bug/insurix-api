<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Clients;

use App\DataTransferObjects\Clients\ClientData;
use App\DataTransferObjects\Clients\ClientProfileData;
use App\Enums\CivilStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    /**
     * Ownership is checked in the controller with the client policy.
     */
    public function authorize(): bool
    {
        return true;
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
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'birthdate' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'civil_status' => ['nullable', Rule::enum(CivilStatus::class)],
            'occupation' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function toData(): ClientData
    {
        return new ClientData(
            name: $this->string('name')->trim()->toString(),
            email: $this->filled('email') ? $this->string('email')->trim()->lower()->toString() : null,
            phone: $this->filled('phone') ? $this->string('phone')->trim()->toString() : null,
            address: $this->filled('address') ? $this->string('address')->trim()->toString() : null,
            profile: new ClientProfileData(
                birthdate: $this->filled('birthdate') ? $this->date('birthdate')?->toImmutable() : null,
                civilStatus: $this->enum('civil_status', CivilStatus::class),
                occupation: $this->filled('occupation') ? $this->string('occupation')->trim()->toString() : null,
            ),
            notes: $this->filled('notes') ? $this->string('notes')->trim()->toString() : null,
        );
    }
}
