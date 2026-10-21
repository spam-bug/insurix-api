<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DataTransferObject\Auth\LoginData;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LogIn
{
    public function __construct(
        private AuthenticateUser $authenticateUser,
        private StartTwoFactorChallenge $startTwoFactorChallenge,
    ) {}

    /**
     * Signs the agent in, or returns null when two-factor is on and a code is needed first.
     *
     * @throws ValidationException
     */
    public function handle(LoginData $data): ?User
    {
        $user = $this->authenticateUser->handle($data);

        if ($user->hasEnabledTwoFactorAuthentication()) {
            $this->startTwoFactorChallenge->handle($user);

            return null;
        }

        Auth::guard('web')->login($user);
        session()->regenerate();

        return $user;
    }
}
