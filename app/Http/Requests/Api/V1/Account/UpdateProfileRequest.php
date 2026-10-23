<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Account;

use App\DataTransferObjects\Account\ProfileData;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Agents only ever edit their own profile.
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user()?->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'timezone' => ['required', 'timezone:all'],
        ];
    }

    public function toData(): ProfileData
    {
        return new ProfileData(
            name: $this->string('name')->trim()->toString(),
            email: $this->string('email')->trim()->lower()->toString(),
            phone: $this->filled('phone') ? $this->string('phone')->trim()->toString() : null,
            timezone: $this->string('timezone')->toString(),
        );
    }
}
