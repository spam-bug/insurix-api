<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;

class ConfirmTwoFactorSetup
{
    public function __construct(private ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication) {}

    /**
     * Turns two-factor on once the agent proves their app works, and hands back the recovery
     * codes to save.
     *
     * @return array<int, string>
     *
     * @throws ValidationException
     */
    public function handle(User $agent, string $code): array
    {
        try {
            ($this->confirmTwoFactorAuthentication)($agent, $code);
        } catch (ValidationException) {
            throw ValidationException::withMessages(['code' => 'That code didn’t match. Check your authenticator app and try again.']);
        }

        return $agent->recoveryCodes();
    }
}
