<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\DataTransferObject\Staff\StaffTwoFactorCodeData;
use App\Models\Staff;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class PassStaffTwoFactorChallenge
{
    public function __construct(private TwoFactorAuthenticationProvider $provider) {}

    /** Signs in the pending staff member when the code checks out. A recovery code works once. */
    public function handle(StaffTwoFactorCodeData $data, Session $session): Staff
    {
        $pending = $session->get(StartStaffLogin::PENDING_KEY);
        $staff = $pending && $pending['expires_at'] > now()->getTimestamp() ? Staff::find($pending['id']) : null;

        if (! $staff) {
            $session->forget(StartStaffLogin::PENDING_KEY);

            throw ValidationException::withMessages(['code' => 'Your sign-in expired. Start again with your password.']);
        }

        if ($data->recoveryCode !== null) {
            if (! in_array($data->recoveryCode, $staff->recoveryCodes(), true)) {
                throw ValidationException::withMessages(['recovery_code' => 'That recovery code isn’t valid.']);
            }

            $staff->replaceRecoveryCode($data->recoveryCode);
        } elseif (! $this->provider->verify(decrypt($staff->two_factor_secret), (string) $data->code)) {
            throw ValidationException::withMessages(['code' => 'That code isn’t valid.']);
        }

        $session->forget(StartStaffLogin::PENDING_KEY);
        Auth::guard('staff')->login($staff);
        $session->migrate(true);

        return $staff;
    }
}
