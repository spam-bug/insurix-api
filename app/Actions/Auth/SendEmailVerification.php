<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;

class SendEmailVerification
{
    /** Sends a fresh confirmation link, unless the email is already confirmed. */
    public function handle(User $user): void
    {
        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }
    }
}
