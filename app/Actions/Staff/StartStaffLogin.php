<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\DataTransferObject\Staff\StaffLoginData;
use App\Models\Staff;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class StartStaffLogin
{
    /** Where a staff member who passed the password step waits for the two-factor challenge. */
    public const PENDING_KEY = 'staff.login';

    /**
     * Checks the password. Someone with two-factor set up isn't signed in yet: they're kept
     * pending until the challenge. Someone without it is signed in, but can only set it up.
     */
    public function handle(StaffLoginData $data, Session $session): Staff
    {
        $staff = Staff::query()->where('email', $data->email)->first();

        if (! $staff || ! Hash::check($data->password, $staff->password)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        if ($staff->hasEnabledTwoFactorAuthentication()) {
            $session->put(self::PENDING_KEY, ['id' => $staff->id, 'expires_at' => now()->addMinutes(5)->getTimestamp()]);

            return $staff;
        }

        Auth::guard('staff')->login($staff);
        $session->migrate(true);

        return $staff;
    }
}
