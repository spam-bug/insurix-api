<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use App\Actions\Staff\StartStaffLogin;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StaffTwoFactorChallengeRequest extends FormRequest
{
    /** Only someone who just passed the password step. */
    public function authorize(): bool
    {
        return $this->user('staff') === null && $this->session()->has(StartStaffLogin::PENDING_KEY);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['nullable', 'required_without:recovery_code', 'string', 'digits:6'],
            'recovery_code' => ['nullable', 'required_without:code', 'string', 'max:100'],
        ];
    }
}
