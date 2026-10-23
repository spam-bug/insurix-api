<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Str;

class StartTwoFactorChallenge
{
    public const TTL_MINUTES = 5;

    /**
     * A correct password with two-factor on earns a short-lived challenge in the session
     * instead of a sign-in; CompleteTwoFactorChallenge finishes it with a code.
     */
    public function handle(User $user): void
    {
        session()->put('two_factor', [
            'id' => Str::random(40),
            'user_id' => $user->id,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES)->timestamp,
        ]);
    }
}
