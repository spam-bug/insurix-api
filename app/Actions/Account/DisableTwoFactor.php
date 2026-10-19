<?php

namespace App\Actions\Account;

use App\Models\User;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;

class DisableTwoFactor
{
    public function __construct(private DisableTwoFactorAuthentication $disableTwoFactorAuthentication) {}

    public function handle(User $agent): void
    {
        ($this->disableTwoFactorAuthentication)($agent);
    }
}
