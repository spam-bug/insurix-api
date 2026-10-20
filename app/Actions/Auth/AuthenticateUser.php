<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DataTransferObject\Auth\LoginData;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthenticateUser
{
    public const MAX_ATTEMPTS = 5;

    /**
     * @throws ValidationException
     */
    public function handle(LoginData $data): User
    {
        $throttleKey = Str::transliterate(Str::lower($data->email).'|'.$data->ipAddress);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', ['seconds' => RateLimiter::availableIn($throttleKey), 'minutes' => ceil(RateLimiter::availableIn($throttleKey) / 60)]),
            ]);
        }

        $user = User::where('email', $data->email)->first();

        if (! $user || ! Hash::check($data->password, $user->password)) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages(['email' => trans('auth.failed')]);
        }

        RateLimiter::clear($throttleKey);

        return $user;
    }
}
