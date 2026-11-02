<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Models\Staff;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;

/** Two-factor for staff: a secret to scan, then confirmed with the first code from the app. */
class ConfirmStaffTwoFactor
{
    public function __construct(
        private EnableTwoFactorAuthentication $enable,
        private ConfirmTwoFactorAuthentication $confirm,
    ) {}

    /** Makes the secret to scan, once; a new one each time would break a half-done setup. */
    public function prepare(Staff $staff): void
    {
        if (! $staff->two_factor_secret) {
            ($this->enable)($staff);
        }
    }

    public function handle(Staff $staff, string $code): void
    {
        try {
            ($this->confirm)($staff, $code);
        } catch (ValidationException) {
            // Fortify reports it in a named error bag; the form shows plain field errors.
            throw ValidationException::withMessages(['code' => 'That code isn’t valid. Check the time on your phone and try the newest code.']);
        }
    }
}
