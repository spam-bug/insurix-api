<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use App\Models\Staff;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmStaffTwoFactorRequest extends FormRequest
{
    /** Only while two-factor isn't confirmed yet: a confirmed setup isn't redone here. */
    public function authorize(): bool
    {
        $staff = $this->user('staff');

        return $staff instanceof Staff && ! $staff->hasEnabledTwoFactorAuthentication();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'digits:6'],
        ];
    }
}
