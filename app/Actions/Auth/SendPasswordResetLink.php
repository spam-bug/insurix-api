<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class SendPasswordResetLink
{
    /**
     * Emails a reset link when the address belongs to an agent. The caller answers the same
     * either way, so the form can't be used to find out who has an account.
     */
    public function handle(string $email): void
    {
        Password::sendResetLink(['email' => Str::lower(trim($email))]);
    }
}
