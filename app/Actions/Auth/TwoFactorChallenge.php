<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

/**
 * The second step of signing in with two-factor on. A correct password earns a short-lived
 * challenge instead of a token; the token comes once a code from the app (or a recovery code)
 * checks out.
 */
class TwoFactorChallenge
{
    private const TTL_MINUTES = 5;

    private const MAX_ATTEMPTS = 5;

    public function __construct(private TwoFactorAuthenticationProvider $provider) {}

    public function start(User $agent, string $deviceName): string
    {
        $challenge = Str::random(48);

        Cache::put($this->key($challenge), ['user_id' => $agent->id, 'device_name' => $deviceName], now()->addMinutes(self::TTL_MINUTES));

        return $challenge;
    }

    /**
     * @return array{user: User, device_name: string}
     *
     * @throws ValidationException
     */
    public function complete(string $challenge, ?string $code, ?string $recoveryCode): array
    {
        $pending = Cache::get($this->key($challenge));
        $user = $pending ? User::find($pending['user_id']) : null;

        if (! $user) {
            throw ValidationException::withMessages(['code' => 'This sign-in has expired. Enter your email and password again.']);
        }

        $throttleKey = "two-factor:{$challenge}";
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            Cache::forget($this->key($challenge));

            throw ValidationException::withMessages(['code' => 'Too many wrong codes. Enter your email and password again.']);
        }

        if (! $this->passes($user, $code, $recoveryCode)) {
            RateLimiter::hit($throttleKey, self::TTL_MINUTES * 60);

            throw ValidationException::withMessages([$recoveryCode ? 'recovery_code' : 'code' => 'That code didn’t work. Try again.']);
        }

        Cache::forget($this->key($challenge));
        RateLimiter::clear($throttleKey);

        return ['user' => $user, 'device_name' => $pending['device_name']];
    }

    private function passes(User $user, ?string $code, ?string $recoveryCode): bool
    {
        if ($recoveryCode) {
            $match = collect($user->recoveryCodes())->first(fn (string $saved) => hash_equals($saved, $recoveryCode));
            if ($match) {
                // Each recovery code works once.
                $user->replaceRecoveryCode($match);

                return true;
            }

            return false;
        }

        return $code !== null && $this->provider->verify(Fortify::currentEncrypter()->decrypt($user->two_factor_secret), $code);
    }

    private function key(string $challenge): string
    {
        return "two-factor-challenge:{$challenge}";
    }
}
