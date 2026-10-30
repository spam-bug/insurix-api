<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\DB;

class VerifyEmailAddress
{
    /**
     * Marks the email as confirmed. The signed link already proves it came from our email; the
     * hash makes sure it's for the address the account has now, not one it changed away from.
     */
    public function handle(User $user, string $hash): User
    {
        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        if ($user->hasVerifiedEmail()) {
            return $user;
        }

        DB::transaction(fn () => $user->markEmailAsVerified());

        event(new Verified($user));

        return $user;
    }
}
